<?php
require_once __DIR__ . '/../database/conexao.php';
require_once __DIR__ . '/../includes/estoque.php';

$empresa = empresa_id();
$todasPrateleiras = consultar_prateleiras_estoque($conexao, $empresa);
$galpoes = array_values(array_filter(consultar_galpoes_estoque($conexao, $empresa, $todasPrateleiras),
    fn($g) => (int) $g['ativo'] === 1));
$galpaoId = (int) ($_GET['galpao'] ?? 0);
$galpaoAtual = null;
foreach ($galpoes as $g) {
    if ((int) $g['id'] === $galpaoId) $galpaoAtual = $g;
}
if (!$galpaoAtual) $galpaoId = 0;
$prateleiras = array_values(array_filter($todasPrateleiras, fn($p) =>
    (int) $p['ativo'] === 1 && (int) $p['galpao_ativo'] === 1
    && (!$galpaoId || (int) $p['galpao_id'] === $galpaoId)));
$resumo = resumir_prateleiras($prateleiras);
$totalGalpoes = $galpaoAtual ? 1 : count($galpoes);
$totalPrateleiras = $resumo['prateleiras'];
$prateleirasAtencao = $resumo['atencao'];
$prateleirasLivres = $resumo['livres'];
$itensArmazenados = $resumo['itens'];
$capacidadeTotal = $resumo['capacidade'];
$volumeOcupado = $resumo['volume'];
$volumeDisponivel = $resumo['disponivel'];
$ocupacao = $resumo['ocupacao'];
$statusGeral = status_ocupacao($ocupacao);
$porGalpao = [];
foreach ($prateleiras as $p) {
    $porGalpao[$p['galpao_id']]['nome'] = $p['galpao_nome'];
    $porGalpao[$p['galpao_id']]['itens'][] = $p;
}
$escopo = $galpaoAtual ? $galpaoAtual['nome'] : 'Todos os galpões';
?>

<div class="pagina-topo">

    <div>
        <h1>Visão geral</h1>
        <p>
            Acompanhe seu estoque · <strong><?= e($escopo) ?></strong> ·
            <?= e(empresa_nome()) ?>
        </p>
    </div>

    <a href="index.php?pagina=cadastrar_ocupacao" class="btn btn-primario">
        <i class="fa-solid fa-plus"></i>
        Registrar ocupação
    </a>

</div>


<!-- SELETOR DE GALPÃO -->

<?php if (count($galpoes) > 0): ?>

    <nav class="filtro-galpoes" aria-label="Filtrar visão geral por galpão">

        <a href="index.php?pagina=dashboard"
           <?= $galpaoId === 0 ? 'aria-current="page"' : '' ?> class="chip <?= $galpaoId === 0 ? "ativo" : "" ?>">
            <i class="fa-solid fa-layer-group"></i>
            <span>Todos os galpões</span>
            <b><?= count($galpoes) ?></b>
        </a>

        <?php foreach ($galpoes as $g): ?>
            <?php $st = status_ocupacao(pct($g["ocupacao_media"])); ?>

            <a href="index.php?pagina=dashboard&galpao=<?= $g["id"] ?>"
               <?= $galpaoId === (int) $g['id'] ? 'aria-current="page"' : '' ?> class="chip <?= $galpaoId === (int) $g["id"] ? "ativo" : "" ?>">
                <i class="fa-solid fa-warehouse ponto <?= $st["nivel"] ?>"></i>
                <span><?= e($g["nome"]) ?></span>
                <b><?= $g["total_prateleiras"] > 0 ? num(pct($g["ocupacao_media"]), 1) . "%" : "Sem prateleiras" ?></b>
            </a>

        <?php endforeach; ?>

    </nav>

<?php endif; ?>


<!-- INDICADORES -->

<section class="kpis">

    <?php if ($galpaoAtual): ?>

        <div class="kpi">
            <div class="kpi-icone"><i class="fa-solid fa-warehouse"></i></div>
            <div class="kpi-dados">
                <p class="kpi-rotulo">Galpão</p>
                <p class="kpi-valor galpao-nome"><?= e($galpaoAtual["nome"]) ?></p>
                <p class="kpi-nota"><?= quantidade($totalPrateleiras, 'prateleira ativa', 'prateleiras ativas') ?></p>
            </div>
        </div>

    <?php else: ?>

        <div class="kpi">
            <div class="kpi-icone"><i class="fa-solid fa-warehouse"></i></div>
            <div class="kpi-dados">
                <p class="kpi-rotulo">Galpões ativos</p>
                <p class="kpi-valor"><?= $totalGalpoes ?></p>
                <p class="kpi-nota"><?= quantidade($totalPrateleiras, 'prateleira ativa', 'prateleiras ativas') ?></p>
            </div>
        </div>

    <?php endif; ?>

    <div class="kpi <?= $statusGeral["nivel"] ?>">
        <div class="kpi-icone"><i class="fa-solid fa-gauge-high"></i></div>
        <div class="kpi-dados">
            <p class="kpi-rotulo">Ocupação das prateleiras</p>
            <p class="kpi-valor"><?= num($ocupacao, 1) ?><small>%</small></p>
            <p class="kpi-nota"><?= $capacidadeTotal > 0 ? $statusGeral['rotulo'] : 'Sem capacidade cadastrada' ?></p>
            <div class="barra-progresso <?= $statusGeral['nivel'] ?>"><span data-pct="<?= $ocupacao ?>" style="width:<?= $ocupacao ?>%"></span></div>
        </div>
    </div>

    <div class="kpi">
        <div class="kpi-icone"><i class="fa-solid fa-box"></i></div>
        <div class="kpi-dados">
            <p class="kpi-rotulo">Volume ocupado</p>
            <p class="kpi-valor"><?= num($volumeOcupado) ?><small>m³</small></p>
            <p class="kpi-nota"><?= quantidade($itensArmazenados, 'item armazenado', 'itens armazenados') ?></p>
        </div>
    </div>

    <div class="kpi livre">
        <div class="kpi-icone"><i class="fa-solid fa-square-check"></i></div>
        <div class="kpi-dados">
            <p class="kpi-rotulo">Espaço disponível</p>
            <p class="kpi-valor"><?= num($volumeDisponivel) ?><small>m³</small></p>
            <p class="kpi-nota"><?= quantidade($prateleirasLivres, 'prateleira livre', 'prateleiras livres') ?></p>
        </div>
    </div>

</section>


<p class="escopo-nota"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Capacidade operacional: <strong><?= m3($capacidadeTotal) ?></strong>. Indicadores de galpões e prateleiras ativos.</p>

<!-- ALERTA -->

<?php if ($prateleirasAtencao > 0): ?>

    <section class="card alerta-estoque">
        <div class="card-corpo aviso-linha">

            <div class="kpi-icone" style="background:var(--atencao-bg);color:var(--atencao)">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>

            <div>
                <strong>
                    <?= quantidade($prateleirasAtencao, 'prateleira com ocupação de 80% ou mais', 'prateleiras com ocupação de 80% ou mais') ?>
                    <?= $galpaoAtual ? "em " . e($galpaoAtual["nome"]) : "" ?>
                </strong>
                <p class="texto-fraco">
                    Considere redistribuir a carga antes de registrar novas entradas.
                </p>
            </div>

        </div>
    </section>

<?php endif; ?>


<!-- MAPA DO ESTOQUE -->

<section class="card">

    <div class="card-topo">
        <div>
            <h2>Mapa do estoque</h2>
            <p>Escolha uma prateleira para consultar os produtos e o espaço livre.</p>
        </div>

        <span class="contador"><?= quantidade($totalPrateleiras, 'prateleira', 'prateleiras') ?></span>
    </div>

    <?php if (empty($porGalpao)): ?>

        <div class="vazio">
            <i class="fa-solid fa-layer-group"></i>

            <?php if ($galpaoAtual): ?>
                <h3>Nenhuma prateleira em <?= e($galpaoAtual["nome"]) ?></h3>
                <p>Cadastre prateleiras neste galpão para visualizar o mapa.</p>
                <a href="index.php?pagina=cadastrar_prateleira&galpao_id=<?= $galpaoAtual["id"] ?>"
                   class="btn btn-primario">
                    <i class="fa-solid fa-plus"></i>
                    Cadastrar prateleira
                </a>
            <?php elseif (!empty($galpoes)): ?>
                <h3>Cadastre sua primeira prateleira</h3>
                <p>Você já tem galpões. Adicione uma prateleira para organizar os produtos.</p>
                <a href="index.php?pagina=cadastrar_prateleira" class="btn btn-primario">Cadastrar prateleira</a>
            <?php else: ?>
                <h3>Nenhuma prateleira cadastrada</h3>
                <p>Cadastre um galpão e suas prateleiras para visualizar o mapa.</p>
                <a href="index.php?pagina=cadastrar_galpao" class="btn btn-primario">
                    <i class="fa-solid fa-plus"></i>
                    Cadastrar galpão
                </a>
            <?php endif; ?>
        </div>

    <?php else: ?>

        <?php foreach ($porGalpao as $gid => $grupo): ?>

            <div class="card-corpo grupo-galpao">

                <div class="grupo-galpao-topo">

                    <h3>
                        <i class="fa-solid fa-warehouse"></i>
                        <?= e($grupo["nome"]) ?>
                        <span class="contador"><?= count($grupo["itens"]) ?></span>
                    </h3>

                    <div class="grupo-galpao-acoes">

                        <?php if ($galpaoId === 0): ?>
                            <a href="index.php?pagina=dashboard&galpao=<?= $gid ?>"
                               class="btn btn-neutro btn-mini">
                                <i class="fa-solid fa-filter"></i>
                                Ver só este
                            </a>
                        <?php endif; ?>

                        <a href="index.php?pagina=detalhes_galpao&id=<?= $gid ?>"
                           class="btn btn-neutro btn-mini">
                            Abrir galpão
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>

                </div>

                <div class="mapa">

                    <?php foreach ($grupo["itens"] as $prateleira): ?>
                        <?php
                        $percentual = pct($prateleira["pct_ocupado"]);
                        $status = status_ocupacao($percentual);
                        ?>

                        <a href="index.php?pagina=detalhes_prateleira&id=<?= $prateleira["id"] ?>"
                           class="bloco <?= $status["nivel"] ?>"
                           title="<?= e($grupo["nome"]) ?> · <?= e($prateleira["codigo"]) ?> — <?= $status["rotulo"] ?>">

                            <span class="bloco-status"><?= $status['rotulo'] ?></span>
                            <div class="bloco-topo">
                                <span class="bloco-codigo"><?= e($prateleira["codigo"]) ?></span>
                                <span class="bloco-pct"><?= num($percentual, 1) ?>%</span>
                            </div>

                            <div class="barra-progresso <?= $status["nivel"] ?>">
                                <span data-pct="<?= $percentual ?>" style="width:<?= $percentual ?>%"></span>
                            </div>

                            <span class="bloco-volume"><?= m3($prateleira['volume_ocupado']) ?> de <?= m3($prateleira['metros_cubicos']) ?></span>
                            <span class="bloco-setor">
                                <?= e($prateleira["setor"] ?: "Sem setor") ?>
                            </span>

                        </a>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endforeach; ?>

        <div class="legenda">
            <div><i class="livre"></i> Livre</div>
            <div><i class="parcial"></i> Ocupada</div>
            <div><i class="atencao"></i> Quase cheia (80%+)</div>
            <div><i class="cheia"></i> Cheia</div>
        </div>

    <?php endif; ?>

</section>
