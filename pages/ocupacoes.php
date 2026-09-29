<?php

require_once __DIR__ . "/../database/conexao.php";

$empresa = empresa_id();

$stmt = $conexao->prepare("
    SELECT
        o.id,
        o.produto,
        o.setor,
        o.volume_m3,
        o.data_entrada,
        o.data_saida,
        o.observacao,
        p.id AS prateleira_id,
        p.codigo AS prateleira_codigo,
        g.nome AS galpao_nome
    FROM ocupacoes o
    INNER JOIN prateleiras p ON p.id = o.prateleira_id
    INNER JOIN galpoes g ON g.id = p.galpao_id
    WHERE g.empresa_id = ?
    ORDER BY o.data_saida IS NOT NULL, o.data_entrada DESC, o.id DESC
");

$stmt->bind_param("i", $empresa);
$stmt->execute();

$resultado = $stmt->get_result();

$ocupacoes = $resultado->fetch_all(MYSQLI_ASSOC);

$armazenados = 0;
$volumeAtivo = 0;

foreach ($ocupacoes as $o) {
    if ($o["data_saida"] === null) {
        $armazenados++;
        $volumeAtivo += (float) $o["volume_m3"];
    }
}

?>

<div class="pagina-topo">

    <div>
        <h1>Ocupações</h1>
        <p>Produtos armazenados nas prateleiras e histórico de retiradas.</p>
    </div>

    <a href="index.php?pagina=cadastrar_ocupacao" class="btn btn-primario">
        <i class="fa-solid fa-plus"></i>
        Registrar ocupação
    </a>

</div>


<div class="card">
    <div class="info-grade">

        <div class="info-item">
            <span>Registros</span>
            <strong><?= count($ocupacoes) ?></strong>
        </div>

        <div class="info-item">
            <span>Armazenados</span>
            <strong><?= $armazenados ?></strong>
        </div>

        <div class="info-item">
            <span>Retirados</span>
            <strong><?= count($ocupacoes) - $armazenados ?></strong>
        </div>

        <div class="info-item">
            <span>Volume em estoque</span>
            <strong><?= m3($volumeAtivo) ?></strong>
        </div>

    </div>
</div>


<section class="card">

    <div class="card-topo">

        <div>
            <h2>Lista de ocupações</h2>
            <p><span id="contadorOcupacoes"><?= quantidade(count($ocupacoes), "registro", "registros") ?></span></p>
        </div>

        <div class="busca">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search"
                   placeholder="Buscar por produto, prateleira ou setor..."
                   aria-label="Buscar ocupação"
                   data-busca="#tabelaOcupacoes"
                   data-contador="#contadorOcupacoes"
                   data-vazio="#semResultadoOcupacoes">
        </div>

    </div>

    <?php if (empty($ocupacoes)): ?>

        <div class="vazio">
            <i class="fa-solid fa-box-open"></i>
            <h3>Nenhuma ocupação registrada</h3>
            <p>Registre a entrada de um produto em uma prateleira.</p>
            <a href="index.php?pagina=cadastrar_ocupacao" class="btn btn-primario">
                <i class="fa-solid fa-plus"></i>
                Registrar ocupação
            </a>
        </div>

    <?php else: ?>

        <div class="tabela-rolagem">

            <table class="tabela" id="tabelaOcupacoes">

                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Prateleira</th>
                        <th>Galpão</th>
                        <th>Setor</th>
                        <th>Volume</th>
                        <th>Entrada</th>
                        <th>Saída</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($ocupacoes as $ocupacao): ?>
                        <?php $ativa = $ocupacao["data_saida"] === null; ?>

                        <tr>

                            <td><strong><?= e($ocupacao["produto"]) ?></strong></td>

                            <td>
                                <a href="index.php?pagina=detalhes_prateleira&id=<?= $ocupacao["prateleira_id"] ?>">
                                    <span class="codigo"><?= e($ocupacao["prateleira_codigo"]) ?></span>
                                </a>
                            </td>

                            <td>
                                <span class="link-icone">
                                    <i class="fa-solid fa-warehouse"></i>
                                    <?= e($ocupacao["galpao_nome"]) ?>
                                </span>
                            </td>

                            <td class="texto-fraco"><?= e($ocupacao["setor"]) ?></td>

                            <td class="numero"><?= m3($ocupacao["volume_m3"]) ?></td>

                            <td class="numero"><?= data_br($ocupacao["data_entrada"]) ?></td>

                            <td class="numero texto-fraco">
                                <?= data_br($ocupacao["data_saida"]) ?>
                            </td>

                            <td>
                                <?php if ($ativa): ?>
                                    <span class="etiqueta parcial">Armazenado</span>
                                <?php else: ?>
                                    <span class="etiqueta inativo">Retirado</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="acoes">

                                    <?php if ($ativa): ?>
                                        <form action="actions/registrar_saida.php" method="POST" class="acao-inline">
<input type="hidden" name="id" value="<?= $ocupacao["id"] ?>">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<button type="submit"
                                           class="btn btn-neutro btn-mini"
                                           data-confirmar="Registrar a saída de &quot;<?= e($ocupacao["produto"]) ?>&quot;?">
                                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                            Saída
                                        </button></form>
                                    <?php endif; ?>

                                    <a href="index.php?pagina=editar_ocupacao&id=<?= $ocupacao["id"] ?>"
                                       class="btn btn-neutro btn-mini" aria-label="Editar ocupação" title="Editar ocupação">
                                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                    </a>

                                    <form action="actions/excluir_ocupacao.php" method="POST" class="acao-inline">
<input type="hidden" name="id" value="<?= $ocupacao["id"] ?>">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<button type="submit"
                                       class="btn btn-perigo btn-mini" aria-label="Excluir registro"
                                       data-confirmar="Excluir o registro de &quot;<?= e($ocupacao["produto"]) ?>&quot;?">
                                        <i class="fa-solid fa-trash"></i>
                                    </button></form>

                                </div>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <div class="vazio oculto" id="semResultadoOcupacoes">
            <i class="fa-solid fa-magnifying-glass"></i>
            <h3>Nenhum resultado</h3>
            <p>Tente buscar por outro termo.</p>
        </div>

    <?php endif; ?>

</section>
