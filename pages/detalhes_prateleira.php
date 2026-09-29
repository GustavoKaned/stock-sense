<?php

require_once __DIR__ . "/../database/conexao.php";

$id = (int) ($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: index.php?pagina=prateleiras");
    exit;
}

/*
 * A prateleira só é encontrada se pertencer a um galpão da empresa
 * logada — o que bloqueia o acesso por troca de ?id= na URL.
 */
$empresa = empresa_id();

$stmt = $conexao->prepare("
    SELECT
        p.id,
        p.codigo,
        p.descricao,
        p.metros_cubicos,
        p.setor,
        p.pct_ocupado,
        p.ativo,
        g.id AS galpao_id,
        g.nome AS galpao_nome
    FROM prateleiras p
    INNER JOIN galpoes g ON g.id = p.galpao_id
    WHERE p.id = ? AND g.empresa_id = ?
");

$stmt->bind_param("ii", $id, $empresa);
$stmt->execute();

$prateleira = $stmt->get_result()->fetch_assoc();

if (!$prateleira) {
    echo '<div class="vazio">
            <i class="fa-solid fa-circle-exclamation"></i>
            <h3>Prateleira não encontrada</h3>
            <a href="index.php?pagina=prateleiras" class="btn btn-neutro">Voltar</a>
          </div>';
    return;
}

$stmt = $conexao->prepare("
    SELECT id, produto, setor, volume_m3, data_entrada, data_saida, observacao
    FROM ocupacoes
    WHERE prateleira_id = ?
    ORDER BY data_saida IS NOT NULL, data_entrada DESC, id DESC
");

$stmt->bind_param("i", $id);
$stmt->execute();

$ocupacoes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Volume ocupado somado a partir das ocupações ativas.
$volumeOcupado = 0;

foreach ($ocupacoes as $o) {
    if ($o["data_saida"] === null) {
        $volumeOcupado += (float) $o["volume_m3"];
    }
}

$capacidade = (float) $prateleira["metros_cubicos"];
$volumeDisponivel = max(0, $capacidade - $volumeOcupado);

$percentual = $capacidade > 0
    ? pct(($volumeOcupado / $capacidade) * 100)
    : 0;

$status = status_ocupacao($percentual);

?>

<nav class="trilha">
    <a href="index.php?pagina=galpoes">Galpões</a>
    <i class="fa-solid fa-chevron-right"></i>
    <a href="index.php?pagina=detalhes_galpao&id=<?= $prateleira["galpao_id"] ?>">
        <?= e($prateleira["galpao_nome"]) ?>
    </a>
    <i class="fa-solid fa-chevron-right"></i>
    <span><?= e($prateleira["codigo"]) ?></span>
</nav>


<div class="pagina-topo">

    <div>
        <h1>
            <i class="fa-solid fa-layer-group texto-fraco"></i>
            Prateleira <?= e($prateleira["codigo"]) ?>
        </h1>
        <p><?= e($prateleira["descricao"] ?: "Sem descrição") ?></p>
    </div>

    <div class="acoes">
        <a href="index.php?pagina=detalhes_galpao&id=<?= $prateleira["galpao_id"] ?>"
           class="btn btn-neutro">
            <i class="fa-solid fa-arrow-left"></i>
            Voltar
        </a>

        <a href="index.php?pagina=cadastrar_ocupacao&prateleira_id=<?= $prateleira["id"] ?>"
           class="btn btn-primario">
            <i class="fa-solid fa-plus"></i>
            Registrar ocupação
        </a>
    </div>

</div>


<div class="card">
    <div class="info-grade">

        <div class="info-item">
            <span>Galpão</span>
            <strong><?= e($prateleira["galpao_nome"]) ?></strong>
        </div>

        <div class="info-item">
            <span>Setor</span>
            <strong><?= e($prateleira["setor"] ?: "—") ?></strong>
        </div>

        <div class="info-item">
            <span>Capacidade</span>
            <strong><?= m3($capacidade) ?></strong>
        </div>

        <div class="info-item">
            <span>Disponível</span>
            <strong class="texto-livre"><?= m3($volumeDisponivel) ?></strong>
        </div>

        <div class="info-item">
            <span>Status</span>
            <strong>
                <?php if ($prateleira["ativo"] == 1): ?>
                    <span class="etiqueta livre">Ativo</span>
                <?php else: ?>
                    <span class="etiqueta inativo">Inativo</span>
                <?php endif; ?>
            </strong>
        </div>

    </div>
</div>


<!-- OCUPAÇÃO -->

<section class="card">

    <div class="card-corpo">

        <div class="taxa-cabecalho" style="display:flex;align-items:baseline;
             justify-content:space-between;gap:20px;margin-bottom:12px;flex-wrap:wrap">

            <div>
                <h2>Ocupação da prateleira</h2>
                <p class="texto-fraco">
                    <?= m3($volumeOcupado) ?> de <?= m3($capacidade) ?>
                </p>
            </div>

            <span class="etiqueta <?= $status["nivel"] ?>">
                <?= $status["rotulo"] ?> · <?= num($percentual, 1) ?>%
            </span>

        </div>

        <div class="barra-progresso grossa <?= $status["nivel"] ?>">
            <span data-pct="<?= $percentual ?>" style="width:0"></span>
        </div>

    </div>

</section>


<!-- PRODUTOS -->

<section class="card">

    <div class="card-topo">

        <div>
            <h2>Movimentações da prateleira</h2>
            <p>Histórico de entradas e saídas desta prateleira.</p>
        </div>

        <span class="contador"><?= quantidade(count($ocupacoes), "registro", "registros") ?></span>

    </div>

    <?php if (empty($ocupacoes)): ?>

        <div class="vazio">
            <i class="fa-solid fa-box-open"></i>
            <h3>Nenhum produto armazenado</h3>
            <p>Esta prateleira ainda não possui ocupações registradas.</p>
            <a href="index.php?pagina=cadastrar_ocupacao&prateleira_id=<?= $prateleira["id"] ?>"
               class="btn btn-primario">
                <i class="fa-solid fa-plus"></i>
                Registrar ocupação
            </a>
        </div>

    <?php else: ?>

        <div class="lista-ocupacoes">

            <?php foreach ($ocupacoes as $ocupacao): ?>
                <?php $ativa = $ocupacao["data_saida"] === null; ?>

                <article class="item-ocupacao <?= $ativa ? "ativa" : "" ?>">

                    <div class="item-icone">
                        <i class="fa-solid <?= $ativa ? "fa-box" : "fa-box-open" ?>"></i>
                    </div>

                    <div class="item-dados">

                        <h3><?= e($ocupacao["produto"]) ?></h3>

                        <div class="item-meta">
                            <span>Setor <b><?= e($ocupacao["setor"]) ?></b></span>
                            <span>Volume <b><?= m3($ocupacao["volume_m3"]) ?></b></span>
                            <span>Entrada <b><?= data_br($ocupacao["data_entrada"]) ?></b></span>

                            <?php if (!$ativa): ?>
                                <span>Saída <b><?= data_br($ocupacao["data_saida"]) ?></b></span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($ocupacao["observacao"])): ?>
                            <p class="item-obs"><?= e($ocupacao["observacao"]) ?></p>
                        <?php endif; ?>

                    </div>

                    <div class="item-lado">

                        <?php if ($ativa): ?>

                            <span class="etiqueta parcial">Armazenado</span>

                            <form action="actions/registrar_saida.php" method="POST" class="acao-inline">
<input type="hidden" name="id" value="<?= $ocupacao["id"] ?>">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<button type="submit"
                               class="btn btn-neutro btn-mini"
                               data-confirmar="Registrar a saída de &quot;<?= e($ocupacao["produto"]) ?>&quot;?">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                Registrar saída
                            </button></form>

                        <?php else: ?>

                            <span class="etiqueta inativo">Retirado</span>

                        <?php endif; ?>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>
