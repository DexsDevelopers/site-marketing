const http = require('http');
http.get('http://localhost:3002/logs?token=lucastav8012', (res) => {
    let data = '';
    res.on('data', c => data += c);
    res.on('end', () => console.log(JSON.stringify(JSON.parse(data).logs.slice(0, 15), null, 2)));
});
