<?php

require_once __DIR__ . "/../database/conexao.php";

require_once __DIR__ . '/../includes/estoque.php';
$empresa = empresa_id();
$prateleiras = consultar_prateleiras_estoque($conexao, $empresa);

?>

<div class="pagina-topo">

    <div>
        <h1>Prateleiras</h1>
        <p>Gerencie as prateleiras cadastradas nos galpões.</p>
    </div>

    <a href="index.php?pagina=cadastrar_prateleira" class="btn btn-primario">
        <i class="fa-solid fa-plus"></i>
        Cadastrar prateleira
    </a>

</div>


<section class="card">

    <div class="card-topo">

        <div>
            <h2>Lista de prateleiras</h2>
            <p><span id="contadorPrateleiras"><?= quantidade(count($prateleiras), "prateleira cadastrada", "prateleiras cadastradas") ?></span></p>
        </div>

        <div class="busca">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search"
                   placeholder="Buscar por código, galpão ou setor..."
                   aria-label="Buscar prateleira"
                   data-busca="#tabelaPrateleiras"
                   data-contador="#contadorPrateleiras"
                   data-vazio="#semResultadoPrateleiras">
        </div>

    </div>

    <?php if (empty($prateleiras)): ?>

        <div class="vazio">
            <i class="fa-solid fa-layer-group"></i>
            <h3>Nenhuma prateleira cadastrada</h3>
            <p>Cadastre prateleiras para começar a registrar ocupações.</p>
            <a href="index.php?pagina=cadastrar_prateleira" class="btn btn-primario">
                <i class="fa-solid fa-plus"></i>
                Cadastrar prateleira
            </a>
        </div>

    <?php else: ?>

        <div class="tabela-rolagem">

            <table class="tabela" id="tabelaPrateleiras">

                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Galpão</th>
                        <th>Setor</th>
                        <th>Capacidade</th>
                        <th>Ocupação</th>
                        <th>Situação</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($prateleiras as $prateleira): ?>
                        <?php
                        $percentual = pct($prateleira["pct_ocupado"]);
                        $status = status_ocupacao($percentual);
                        ?>

                        <tr>

                            <td>
                                <a href="index.php?pagina=detalhes_prateleira&id=<?= $prateleira["id"] ?>">
                                    <span class="codigo"><?= e($prateleira["codigo"]) ?></span>
                                </a>
                            </td>

                            <td>
                                <span class="link-icone">
                                    <i class="fa-solid fa-warehouse"></i>
                                    <?= e($prateleira["galpao_nome"]) ?>
                                </span>
                            </td>

                            <td class="texto-fraco">
                                <?= e($prateleira["setor"] ?: "—") ?>
                            </td>

                            <td class="numero">
                                <?= m3($prateleira["metros_cubicos"]) ?>
                            </td>

                            <td>
                                <div class="barra-linha">
                                    <div class="barra-progresso <?= $status["nivel"] ?>">
                                        <span data-pct="<?= $percentual ?>" style="width:0"></span>
                                    </div>
                                    <b><?= num($percentual, 1) ?>%</b>
                                </div>
                            </td>

                            <td>
                                <span class="etiqueta <?= $status["nivel"] ?>">
                                    <?= $status["rotulo"] ?>
                                </span>
                            </td>

                            <td>
                                <?php if ($prateleira["ativo"] == 1): ?>
                                    <span class="etiqueta livre">Ativo</span>
                                <?php else: ?>
                                    <span class="etiqueta inativo">Inativo</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="acoes">

                                    <a href="index.php?pagina=editar_prateleira&id=<?= $prateleira["id"] ?>"
                                       class="btn btn-neutro btn-mini">
                                        <i class="fa-solid fa-pen"></i>
                                        Editar
                                    </a>

                                    <form action="actions/excluir_prateleira.php" method="POST" class="acao-inline">
<input type="hidden" name="id" value="<?= $prateleira["id"] ?>">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<button type="submit"
                                       class="btn btn-perigo btn-mini" aria-label="Excluir registro"
                                       data-confirmar="Excluir a prateleira <?= e($prateleira["codigo"]) ?>? As ocupações vinculadas também serão removidas.">
                                        <i class="fa-solid fa-trash"></i>
                                    </button></form>

                                </div>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <div class="vazio oculto" id="semResultadoPrateleiras">
            <i class="fa-solid fa-magnifying-glass"></i>
            <h3>Nenhum resultado</h3>
            <p>Tente buscar por outro termo.</p>
        </div>

    <?php endif; ?>

</section>
