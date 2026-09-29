<?php
require_once __DIR__ . '/../includes/auth.php';
if (!esta_logado()) responder_json(false, 'Autenticação necessária.', null, 401);
require_once __DIR__ . '/../database/conexao.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    $stmt = $conexao->prepare('SELECT p.id, p.codigo, p.pct_ocupado AS ocupacao_percentual
        FROM prateleiras p INNER JOIN galpoes g ON g.id = p.galpao_id WHERE g.empresa_id = ?');
    $empresa = empresa_id();
    $stmt->bind_param('i', $empresa);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
} catch (Throwable $erro) {
    error_log('StockSense status_estoque: ' . $erro->getMessage());
    responder_json(false, 'Não foi possível consultar o estoque.', null, 500);
}
