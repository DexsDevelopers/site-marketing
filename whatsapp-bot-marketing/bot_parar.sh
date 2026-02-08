#!/bin/bash
echo "Parando o bot do Marketing..."
pm2 stop bot-marketing
pm2 delete bot-marketing
pm2 save
echo "Bot Marketing parado e removido."
