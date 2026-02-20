<?php
require 'includes/db_connect.php';

$msgs = $pdo->query("SELECT * FROM marketing_mensagens")->fetchAll(PDO::FETCH_ASSOC);
$campanha = $pdo->query("SELECT * FROM marketing_campanhas WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

echo "Campanha Ativa? " . ($campanha ? $campanha['ativo'] : 'nao existe') . "\n";
echo "Messages:\n";
print_r($msgs);
