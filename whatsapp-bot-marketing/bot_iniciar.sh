cd "$(dirname "$0")"
echo "Iniciando o bot de WhatsApp (Marketing)..."
pm2 start index.js --name bot-marketing --node-args="--max-old-space-size=4096 --expose-gc"
pm2 save
echo "Bot Marketing iniciado em segundo plano."
read -p "Pressione Enter para ver o status..."
pm2 status
