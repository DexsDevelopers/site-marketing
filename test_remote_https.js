const https = require('https');

https.get('https://khaki-gull-213146.hostingersite.com/api_marketing.php?action=cron_process', (res) => {
    let data = '';
    res.on('data', chunk => data += chunk);
    res.on('end', () => console.log('Response:', data));
}).on('error', err => console.error(err));
