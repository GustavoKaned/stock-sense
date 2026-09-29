<?php
// Arquivo: StockSense/simulador.php
require_once 'database/conexao.php';

// Simula uma leitura do sensor ultrassônico gerando um valor aleatório de 0 a 100
$leitura_simulada = rand(0, 100);
$id_prateleira_simulada = 1; // ID da prateleira que você quer testar

// Atualiza o banco de dados
$sql = "UPDATE prateleiras SET ocupacao_percentual = :valor WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindValue(':valor', $leitura_simulada);
$stmt->bindValue(':id', $id_prateleira_simulada);
$stmt->execute();

echo "<h1>Simulador de Sensor ESP32</h1>";
echo "<p>Valor de <b>{$leitura_simulada}%</b> inserido na prateleira {$id_prateleira_simulada}!</p>";
echo "<p>Atualize essa página para gerar um novo valor e observe o dashboard.php mudar.</p>";
?>