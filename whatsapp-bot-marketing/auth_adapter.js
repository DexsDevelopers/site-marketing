
import mysql from 'mysql2/promise';
import { proto, initAuthCreds, BufferJSON } from '@whiskeysockets/baileys';

/**
 * Adaptador MySQL Ultra-Robusto para Autenticação Baileys
 * Focado em persistência confiável e recuperação automática de falhas.
 */
export const useMySQLAuthState = async (dbConfig) => {
    // 1. Criar Pool de Conexões com configuração otimizada
    const pool = mysql.createPool({
        ...dbConfig,
        charset: 'utf8mb4',
        timezone: '-03:00', // Forçar timezone local
        dateStrings: true,
        connectTimeout: 60000, // Timeout generoso para evitar queda em rede lenta
        waitForConnections: true,
        connectionLimit: 10,
        queueLimit: 0
    });

    // 2. Garantir Tabela (Com Logs Claros)
    try {
        await pool.query(`
            CREATE TABLE IF NOT EXISTS baileys_auth_store (
                \`key\` VARCHAR(191) NOT NULL PRIMARY KEY,
                \`value\` LONGTEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        `);
    } catch (error) {
        console.error('❌ ERRO CRÍTICO: Falha ao criar/verificar tabela de auth:', error.message);
        // Se falhar aqui, o bot provavelmente vai falhar mais pra frente, mas continuamos.
    }

    // 3. Funções Auxiliares de CRUD Resilientes

    // Ler dados com recuperação de falha
    const readData = async (key) => {
        try {
            const [rows] = await pool.query('SELECT value FROM baileys_auth_store WHERE `key` = ?', [key]);

            if (rows.length > 0) {
                const rawValue = rows[0].value;
                try {
                    // Tentar parsear o JSON com o reviver do Baileys (essencial para Buffers)
                    const parsed = JSON.parse(rawValue, BufferJSON.reviver);
                    return parsed;
                } catch (parseError) {
                    console.error(`⚠️ CORRUPÇÃO DETECTADA: Chave '${key}' contém JSON inválido. Resetando chave...`);
                    // Se o JSON estiver quebrado, DELETAR a chave para permitir regeneração limpa
                    await removeData(key);
                    return null;
                }
            }
            // Se não existe, retorna null (Baileys vai criar)
            return null;

        } catch (error) {
            console.error(`❌ Erro de Leitura SQL (${key}):`, error.message);
            // Em caso de erro de conexão, retornamos null para não crashar, 
            // mas isso pode causar logout se for a cred principal.
            return null;
        }
    };

    // Escrever dados com garantia de integridade
    const writeData = async (key, value) => {
        try {
            // Serializar usando o replacer do Baileys (essencial para Buffers)
            const jsonValue = JSON.stringify(value, BufferJSON.replacer);

            // Usar ON DUPLICATE KEY UPDATE para garantir upsert atômico
            await pool.query(
                'INSERT INTO baileys_auth_store (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?',
                [key, jsonValue, jsonValue]
            );
        } catch (error) {
            console.error(`❌ Erro de Escrita SQL (${key}):`, error.message);
        }
    };

    // Remover dados
    const removeData = async (key) => {
        try {
            await pool.query('DELETE FROM baileys_auth_store WHERE `key` = ?', [key]);
        } catch (error) {
            console.error(`❌ Erro de Remoção SQL (${key}):`, error.message);
        }
    };

    // 4. Inicialização de Credenciais
    // Tenta ler 'creds'. Se não existir ou for inválido, inicia novas credenciais limpas.
    let creds;
    try {
        const storedCreds = await readData('creds');
        creds = storedCreds || initAuthCreds();
        if (!storedCreds) {
            console.log('✨ Iniciando NOVA sessão limpa (nenhuma credencial válida encontrada).');
        } else {
            // console.log('🔄 Credenciais carregadas do banco com sucesso.');
        }
    } catch (e) {
        console.error('❌ Falha fatal ao carregar credenciais iniciais:', e);
        creds = initAuthCreds(); // Fallback final
    }

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
        },
        // Método extra para limpar tudo (útil para reset forçado)
        clearAuthState: async () => {
            try {
                await pool.query('TRUNCATE TABLE baileys_auth_store');
                console.log('🧹 Tabela de auth limpa com sucesso (TRUNCATE).');
            } catch (e) {
                console.error('❌ Erro ao limpar tabela de auth:', e);
            }
        }
    };
};
