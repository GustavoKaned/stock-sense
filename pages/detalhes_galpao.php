<?php

require_once __DIR__ . "/../database/conexao.php";

$id = (int) ($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: index.php?pagina=galpoes");
    exit;
}

/*
 * O filtro por empresa_id aqui é o que impede acessar o galpão de
 * outra empresa apenas trocando o ?id= na URL.
 */
$empresa = empresa_id();

$stmt = $conexao->prepare("
    SELECT id, nome, descricao, metros_cubicos, ativo
    FROM galpoes
    WHERE id = ? AND empresa_id = ?
");

$stmt->bind_param("ii", $id, $empresa);
$stmt->execute();

$galpao = $stmt->get_result()->fetch_assoc();

if (!$galpao) {
    echo '<div class="vazio">
            <i class="fa-solid fa-circle-exclamation"></i>
            <h3>Galpão não encontrado</h3>
            <a href="index.php?pagina=galpoes" class="btn btn-neutro">Voltar</a>
          </div>';
    return;
}

require_once __DIR__ . '/../includes/estoque.php';
$prateleiras = consultar_prateleiras_estoque($conexao, $empresa, $id);
$resumo = resumir_prateleiras($prateleiras);
$capacidadePrateleiras = array_sum(array_column($prateleiras, 'metros_cubicos'));
$volumeOcupado = $resumo['volume'];
$ocupacaoGalpao = $resumo['ocupacao'];
$statusGeral = status_ocupacao($ocupacaoGalpao);

?>

<nav class="trilha">
    <a href="index.php?pagina=galpoes">Galpões</a>
    <i class="fa-solid fa-chevron-right"></i>
    <span><?= e($galpao["nome"]) ?></span>
</nav>


<div class="pagina-topo">

    <div>
        <h1>
            <i class="fa-solid fa-warehouse texto-fraco"></i>
            <?= e($galpao["nome"]) ?>
        </h1>
        <p><?= e($galpao["descricao"] ?: "Sem descrição") ?></p>
    </div>

    <div class="acoes">
        <a href="index.php?pagina=galpoes" class="btn btn-neutro">
            <i class="fa-solid fa-arrow-left"></i>
            Voltar
        </a>

        <a href="index.php?pagina=editar_galpao&id=<?= $galpao["id"] ?>"
           class="btn btn-primario">
            <i class="fa-solid fa-pen"></i>
            Editar
        </a>
    </div>

</div>


<div class="card">
    <div class="info-grade">

        <div class="info-item">
            <span>Capacidade declarada</span>
            <strong><?= m3($galpao["metros_cubicos"]) ?></strong>
        </div>

        <div class="info-item">
            <span>Em prateleiras</span>
            <strong><?= m3($capacidadePrateleiras) ?></strong>
        </div>

        <div class="info-item">
            <span>Prateleiras</span>
            <strong><?= count($prateleiras) ?></strong>
        </div>

        <div class="info-item">
            <span>Ocupação das prateleiras ativas</span>
            <strong><?= num($ocupacaoGalpao, 1) ?>%</strong>
        </div>

        <div class="info-item">
            <span>Status</span>
            <strong>
                <?php if ($galpao["ativo"] == 1): ?>
                    <span class="etiqueta livre">Ativo</span>
                <?php else: ?>
                    <span class="etiqueta inativo">Inativo</span>
                <?php endif; ?>
            </strong>
        </div>

    </div>
</div>


<section class="card">

    <div class="card-topo">

        <div>
            <h2>Prateleiras</h2>
            <p>Clique em uma prateleira para ver seus produtos.</p>
        </div>

        <a href="index.php?pagina=cadastrar_prateleira&galpao_id=<?= $galpao["id"] ?>" class="btn btn-neutro btn-mini">
            <i class="fa-solid fa-plus"></i>
            Nova prateleira
        </a>

    </div>

    <?php if (empty($prateleiras)): ?>

        <div class="vazio">
            <i class="fa-solid fa-layer-group"></i>
            <h3>Nenhuma prateleira neste galpão</h3>
            <p>Cadastre a primeira prateleira para começar.</p>
            <a href="index.php?pagina=cadastrar_prateleira&galpao_id=<?= $galpao["id"] ?>" class="btn btn-primario">
                <i class="fa-solid fa-plus"></i>
                Cadastrar prateleira
            </a>
        </div>

    <?php else: ?>

        <div class="grade-cards">

            <?php foreach ($prateleiras as $prateleira): ?>
                <?php
                $percentual = pct($prateleira["pct_ocupado"]);
                $status = status_ocupacao($percentual);
                ?>

                <a href="index.php?pagina=detalhes_prateleira&id=<?= $prateleira["id"] ?>"
                   class="mini-card">

                    <div class="mini-card-topo">
                        <span class="codigo"><?= e($prateleira["codigo"]) ?></span>
                        <span class="etiqueta <?= (int) $prateleira["ativo"] === 1 ? $status["nivel"] : "inativo" ?>">
                            <?= (int) $prateleira["ativo"] === 1 ? $status["rotulo"] : "Inativa" ?>
                        </span>
                    </div>

                    <p class="mini-card-setor">
                        <?= e($prateleira["setor"] ?: "Sem setor") ?>
                    </p>

                    <div class="barra-progresso <?= $status["nivel"] ?>">
                        <span data-pct="<?= $percentual ?>" style="width:0"></span>
                    </div>

                    <div class="mini-card-rodape">
                        <span><?= m3($prateleira["metros_cubicos"]) ?></span>
                        <b><?= num($percentual, 1) ?>%</b>
                    </div>

                </a>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>
