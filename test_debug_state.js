const https = require('https');

https.get('https://khaki-gull-213146.hostingersite.com/debug_state.php', (res) => {
    let data = '';
    res.on('data', chunk => data += chunk);
    res.on('end', () => console.log('Response:', data));
}).on('error', err => console.error(err));
