<?php
require 'includes/db_connect.php';

// Ativar SOMENTE a campanha mais recente (feita pelo usuário no painel)
try {
    $pdo->beginTransaction();
    $pdo->exec("UPDATE marketing_campanhas SET ativo = 0");
    $pdo->exec("UPDATE marketing_campanhas SET ativo = 1 ORDER BY id DESC LIMIT 1");
    $pdo->commit();
    echo "Campanhas ajustadas! Apenas a mais recente esta ativa.\n";
}
catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
