<?php
require_once __DIR__ . '/../includes/estoque.php';

function verificar($condicao, $mensagem)
{
    if (!$condicao) throw new RuntimeException($mensagem);
}

$prateleiras = [
    ['ativo' => 1, 'metros_cubicos' => 10, 'volume_ocupado' => 10, 'itens_armazenados' => 2],
    ['ativo' => 1, 'metros_cubicos' => 90, 'volume_ocupado' => 0, 'itens_armazenados' => 0],
    ['ativo' => 0, 'metros_cubicos' => 200, 'volume_ocupado' => 150, 'itens_armazenados' => 9],
];
$r = resumir_prateleiras($prateleiras);
verificar(abs($r['ocupacao'] - 10) < 0.00001, 'Capacidades diferentes devem produzir 10%, não média simples de 50%.');
verificar($r['prateleiras'] === 2 && $r['itens'] === 2, 'Inativas não entram no resumo operacional.');
verificar($r['livres'] === 1 && $r['atencao'] === 1, 'Contagem de livres e atenção.');
verificar($r['disponivel'] === 90.0, 'Espaço livre incorreto.');
verificar(resumir_prateleiras([])['ocupacao'] === 0, 'Estoque vazio não pode dividir por zero.');
verificar(percentual_volume(150, 100) === 100, 'Percentual visual limitado a 100%.');
verificar(status_ocupacao(79.99)['nivel'] === 'parcial', 'Não arredondar antes de classificar.');
verificar(status_ocupacao(80)['nivel'] === 'atencao', '80% deve entrar em atenção.');
verificar(status_ocupacao(100)['nivel'] === 'cheia', '100% deve ser cheia.');
verificar(quantidade(1, 'prateleira', 'prateleiras') === '1 prateleira', 'Singular incorreto.');
verificar(quantidade(0, 'prateleira', 'prateleiras') === '0 prateleiras', 'Plural de zero incorreto.');
echo "OK: cálculo ponderado, inativas, estoque vazio, capacidade, faixas e pluralização.\n";
