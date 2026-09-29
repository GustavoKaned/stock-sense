<?php

require_once __DIR__ . '/funcoes.php';

/** Indicadores operacionais: volume real nas prateleiras ativas, sem média simples. */
function resumir_prateleiras(array $prateleiras)
{
    $resumo = ['prateleiras' => 0, 'capacidade' => 0.0, 'volume' => 0.0,
        'itens' => 0, 'livres' => 0, 'atencao' => 0];
    foreach ($prateleiras as $p) {
        if (!(int) $p['ativo']) continue;
        $resumo['prateleiras']++;
        $resumo['capacidade'] += (float) $p['metros_cubicos'];
        $resumo['volume'] += (float) $p['volume_ocupado'];
        $resumo['itens'] += (int) $p['itens_armazenados'];
        $percentual = percentual_volume($p['volume_ocupado'], $p['metros_cubicos']);
        if ($percentual >= 80) $resumo['atencao']++;
        if ((float) $p['volume_ocupado'] === 0.0) $resumo['livres']++;
    }
    $resumo['ocupacao'] = percentual_volume($resumo['volume'], $resumo['capacidade']);
    $resumo['disponivel'] = max(0, $resumo['capacidade'] - $resumo['volume']);
    return $resumo;
}

function percentual_volume($volume, $capacidade)
{
    return (float) $capacidade > 0 ? pct((float) $volume / (float) $capacidade * 100) : 0;
}

/** Inclui inativas para consultas e histórico; quem exibe o painel filtra as ativas. */
function consultar_prateleiras_estoque($conexao, $empresa, $galpaoId = 0)
{
    $stmt = $conexao->prepare("SELECT p.id, p.codigo, p.descricao, p.metros_cubicos,
        p.setor, p.ativo, g.id AS galpao_id, g.nome AS galpao_nome, g.ativo AS galpao_ativo,
        COALESCE((SELECT SUM(o.volume_m3) FROM ocupacoes o
            WHERE o.prateleira_id = p.id AND o.data_saida IS NULL), 0) AS volume_ocupado,
        (SELECT COUNT(*) FROM ocupacoes o
            WHERE o.prateleira_id = p.id AND o.data_saida IS NULL) AS itens_armazenados
        FROM prateleiras p INNER JOIN galpoes g ON g.id = p.galpao_id
        WHERE g.empresa_id = ? AND (? = 0 OR g.id = ?)
        ORDER BY g.nome, p.codigo");
    $stmt->bind_param('iii', $empresa, $galpaoId, $galpaoId);
    $stmt->execute();
    $prateleiras = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($prateleiras as &$p) {
        $p['pct_ocupado'] = percentual_volume($p['volume_ocupado'], $p['metros_cubicos']);
    }
    unset($p);
    return $prateleiras;
}

function consultar_galpoes_estoque($conexao, $empresa, array $prateleiras)
{
    $stmt = $conexao->prepare('SELECT id, nome, descricao, metros_cubicos, ativo
        FROM galpoes WHERE empresa_id = ? ORDER BY nome');
    $stmt->bind_param('i', $empresa);
    $stmt->execute();
    $galpoes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $grupos = [];
    foreach ($prateleiras as $p) $grupos[$p['galpao_id']][] = $p;
    foreach ($galpoes as &$g) {
        $resumo = resumir_prateleiras($grupos[$g['id']] ?? []);
        $g['total_prateleiras'] = $resumo['prateleiras'];
        $g['capacidade_prateleiras'] = $resumo['capacidade'];
        $g['volume_ocupado'] = $resumo['volume'];
        $g['ocupacao_media'] = $resumo['ocupacao'];
    }
    unset($g);
    return $galpoes;
}
