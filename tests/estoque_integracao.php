<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Somente leitura: compara o resumo com consultas independentes no banco configurado.
require_once __DIR__ . '/estoque.php';
require_once __DIR__ . '/../database/conexao.php';

$empresas = $conexao->query('SELECT id FROM empresas')->fetch_all(MYSQLI_ASSOC);
foreach ($empresas as $empresa) {
    $id = (int) $empresa['id'];
    $prateleiras = consultar_prateleiras_estoque($conexao, $id);
    $galpoes = consultar_galpoes_estoque($conexao, $id, $prateleiras);
    $ids = array_map('intval', array_column($galpoes, 'id'));
    foreach ($prateleiras as $p) verificar(in_array((int) $p['galpao_id'], $ids, true), 'Prateleira fora da empresa.');
    $r = resumir_prateleiras(array_filter($prateleiras, fn($p) => (int) $p['galpao_ativo'] === 1));
    $stmt = $conexao->prepare('SELECT COALESCE(SUM(p.metros_cubicos), 0) AS capacidade
        FROM prateleiras p JOIN galpoes g ON g.id = p.galpao_id
        WHERE g.empresa_id = ? AND g.ativo = 1 AND p.ativo = 1');
    $stmt->bind_param('i', $id); $stmt->execute();
    verificar(abs($r['capacidade'] - (float) $stmt->get_result()->fetch_assoc()['capacidade']) < 0.00001, 'Capacidade divergente.');
    $stmt = $conexao->prepare('SELECT COALESCE(SUM(o.volume_m3), 0) AS volume, COUNT(*) AS itens
        FROM ocupacoes o JOIN prateleiras p ON p.id = o.prateleira_id JOIN galpoes g ON g.id = p.galpao_id
        WHERE g.empresa_id = ? AND g.ativo = 1 AND p.ativo = 1 AND o.data_saida IS NULL');
    $stmt->bind_param('i', $id); $stmt->execute(); $dados = $stmt->get_result()->fetch_assoc();
    verificar(abs($r['volume'] - (float) $dados['volume']) < 0.00001 && $r['itens'] === (int) $dados['itens'], 'Volume ou itens divergentes.');
}
echo 'OK: consultas de leitura e isolamento dos indicadores em ' . count($empresas) . " empresas.\n";
