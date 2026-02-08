const axios = require('axios');

const url = 'https://khaki-gull-213146.hostingersite.com/api_marketing_robusto.php?action=save_members';
const token = 'lucastav8012';

console.log(`Testando conexão com: ${url}`);

axios.post(url, {
    group_jid: 'teste@g.us',
    members: ['551199999999']
}, {
    headers: { 'x-api-token': token }
})
    .then(res => {
        console.log('✅ Sucesso!', res.data);
    })
    .catch(err => {
        console.error('❌ Erro:', err.message);
        if (err.response) {
            console.error('Status:', err.response.status);
            console.error('Data:', err.response.data);
        }
    });
