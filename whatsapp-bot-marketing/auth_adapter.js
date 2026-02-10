
import mysql from 'mysql2/promise';
import { proto, initAuthCreds, BufferJSON } from '@whiskeysockets/baileys';

/**
 * Adaptador MySQL para autenticação Baileys
 * @param {object} dbConfig Configuração de conexão MySQL
 * @returns {Promise<{state: AuthenticationState, saveCreds: () => Promise<void>}>}
 */
export const useMySQLAuthState = async (dbConfig) => {
    // Criar pool de conexões (garantir utf8mb4)
    const pool = mysql.createPool({
        ...dbConfig,
        charset: 'utf8mb4'
    });

    // Garantir que a tabela existe
    try {
        await pool.query(`
            CREATE TABLE IF NOT EXISTS baileys_auth_store (
                \`key\` VARCHAR(191) NOT NULL PRIMARY KEY,
                \`value\` LONGTEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        `);
        // console.log('✅ Tabela baileys_auth_store verificada/criada com sucesso.');
    } catch (error) {
        console.error('❌ Erro ao criar tabela baileys_auth_store:', error.message);
    }

    // Função auxiliar para ler do banco
    const readData = async (key) => {
        try {
            const [rows] = await pool.query('SELECT value FROM baileys_auth_store WHERE `key` = ?', [key]);
            if (rows.length > 0) {
                return JSON.parse(rows[0].value, BufferJSON.reviver);
            }
            return null;
        } catch (error) {
            console.error('❌ Erro ao ler Auth do MySQL:', error.message);
            return null;
        }
    };

    // Função auxiliar para escrever no banco
    const writeData = async (key, value) => {
        try {
            const jsonValue = JSON.stringify(value, BufferJSON.replacer);
            await pool.query(
                'INSERT INTO baileys_auth_store (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?',
                [key, jsonValue, jsonValue]
            );
        } catch (error) {
            console.error('❌ Erro ao escrever Auth no MySQL:', error.message);
        }
    };

    // Função auxiliar para remover do banco
    const removeData = async (key) => {
        try {
            await pool.query('DELETE FROM baileys_auth_store WHERE `key` = ?', [key]);
        } catch (error) {
            console.error('❌ Erro ao remover Auth do MySQL:', error.message);
        }
    };

    // Inicializar credenciais
    const creds = (await readData('creds')) || initAuthCreds();

    return {
        state: {
            creds,
            keys: {
                get: async (type, ids) => {
                    const data = {};
                    await Promise.all(ids.map(async (id) => {
                        let value = await readData(`${type}-${id}`);
                        if (type === 'app-state-sync-key' && value) {
                            value = proto.Message.AppStateSyncKeyData.fromObject(value);
                        }
                        data[id] = value;
                    }));
                    return data;
                },
                set: async (data) => {
                    const tasks = [];
                    for (const category in data) {
                        for (const id in data[category]) {
                            const value = data[category][id];
                            const key = `${category}-${id}`;
                            if (value) {
                                tasks.push(writeData(key, value));
                            } else {
                                tasks.push(removeData(key));
                            }
                        }
                    }
                    await Promise.all(tasks);
                }
            }
        },
        saveCreds: async () => {
            await writeData('creds', creds);
        }
    };
};
