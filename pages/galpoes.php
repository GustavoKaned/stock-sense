<?php

require_once __DIR__ . "/../database/conexao.php";

require_once __DIR__ . '/../includes/estoque.php';
$empresa = empresa_id();
$prateleiras = consultar_prateleiras_estoque($conexao, $empresa);
$galpoes = consultar_galpoes_estoque($conexao, $empresa, $prateleiras);

?>

<div class="pagina-topo">

    <div>
        <h1>Galpões</h1>
        <p>Gerencie os galpões e acompanhe a ocupação de cada um.</p>
    </div>

    <a href="index.php?pagina=cadastrar_galpao" class="btn btn-primario">
        <i class="fa-solid fa-plus"></i>
        Cadastrar galpão
    </a>

</div>


<section class="card">

    <div class="card-topo">

        <div>
            <h2>Lista de galpões</h2>
            <p><span id="contadorGalpoes"><?= quantidade(count($galpoes), "galpão cadastrado", "galpões cadastrados") ?></span> · Ocupação calculada nas prateleiras ativas.</p>
        </div>

        <div class="busca">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search"
                   placeholder="Buscar galpão..."
                   aria-label="Buscar galpão"
                   data-busca="#tabelaGalpoes"
                   data-contador="#contadorGalpoes"
                   data-vazio="#semResultadoGalpoes">
        </div>

    </div>

    <?php if (empty($galpoes)): ?>

        <div class="vazio">
            <i class="fa-solid fa-warehouse"></i>
            <h3>Nenhum galpão cadastrado</h3>
            <p>Comece cadastrando o primeiro galpão do seu estoque.</p>
            <a href="index.php?pagina=cadastrar_galpao" class="btn btn-primario">
                <i class="fa-solid fa-plus"></i>
                Cadastrar galpão
            </a>
        </div>

    <?php else: ?>

        <div class="tabela-rolagem">

            <table class="tabela" id="tabelaGalpoes">

                <thead>
                    <tr>
                        <th>Galpão</th>
                        <th>Descrição</th>
                        <th>Prateleiras ativas</th>
                        <th>Capacidade do galpão</th>
                        <th>Ocupação</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($galpoes as $galpao): ?>
                        <?php
                        $media = pct($galpao["ocupacao_media"]);
                        $status = status_ocupacao($media);
                        ?>

                        <tr>

                            <td>
                                <a href="index.php?pagina=detalhes_galpao&id=<?= $galpao["id"] ?>"
                                   class="link-icone">
                                    <i class="fa-solid fa-warehouse"></i>
                                    <?= e($galpao["nome"]) ?>
                                </a>
                            </td>

                            <td class="texto-fraco">
                                <?= e($galpao["descricao"] ?: "—") ?>
                            </td>

                            <td class="numero">
                                <?= (int) $galpao["total_prateleiras"] ?>
                            </td>

                            <td class="numero">
                                <?= m3($galpao["metros_cubicos"]) ?>
                                <small class="celula-nota"><?= m3($galpao["capacidade_prateleiras"]) ?> em prateleiras ativas</small>
                            </td>

                            <td>
                                <div class="barra-linha">
                                    <div class="barra-progresso <?= $status["nivel"] ?>">
                                        <span data-pct="<?= $media ?>" style="width:0"></span>
                                    </div>
                                    <b><?= num($media, 1) ?>%</b>
                                </div>
                            </td>

                            <td>
                                <?php if ($galpao["ativo"] == 1): ?>
                                    <span class="etiqueta livre">Ativo</span>
                                <?php else: ?>
                                    <span class="etiqueta inativo">Inativo</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="acoes">

                                    <a href="index.php?pagina=editar_galpao&id=<?= $galpao["id"] ?>"
                                       class="btn btn-neutro btn-mini">
                                        <i class="fa-solid fa-pen"></i>
                                        Editar
                                    </a>

                                    <form action="actions/excluir_galpao.php" method="POST" class="acao-inline">
<input type="hidden" name="id" value="<?= $galpao["id"] ?>">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<button type="submit"
                                       class="btn btn-perigo btn-mini" aria-label="Excluir registro"
                                       data-confirmar="Excluir o galpão &quot;<?= e($galpao["nome"]) ?>&quot;? As prateleiras vinculadas também serão removidas.">
                                        <i class="fa-solid fa-trash"></i>
                                    </button></form>

                                </div>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <div class="vazio oculto" id="semResultadoGalpoes">
            <i class="fa-solid fa-magnifying-glass"></i>
            <h3>Nenhum resultado</h3>
            <p>Tente buscar por outro termo.</p>
        </div>

    <?php endif; ?>

</section>
