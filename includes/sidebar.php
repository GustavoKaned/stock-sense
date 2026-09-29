<?php
/*
 * $menuAtivo é definido pelo roteador em index.php.
 * Assim uma subpágina (ex.: cadastrar_galpao) mantém
 * "Galpões" destacado no menu.
 */

$menuAtivo = $menuAtivo ?? "dashboard";

$itens = [
    ["chave" => "dashboard",   "rota" => "dashboard",   "icone" => "fa-chart-line",     "texto" => "Visão geral"],
    ["chave" => "galpoes",     "rota" => "galpoes",     "icone" => "fa-warehouse",      "texto" => "Galpões"],
    ["chave" => "prateleiras", "rota" => "prateleiras", "icone" => "fa-layer-group",    "texto" => "Prateleiras"],
    ["chave" => "ocupacoes",   "rota" => "ocupacoes",   "icone" => "fa-boxes-stacked",  "texto" => "Ocupações"],
];
?>

<aside class="sidebar" id="navegacaoLateral" aria-label="Navegação principal">

    <div class="logo">

        <div class="logo-marca">
            <i class="fa-solid fa-cubes"></i>
        </div>

        <div class="logo-texto">
            <strong>StockSense</strong>
            <span>Gestão de estoque</span>
        </div>

    </div>

    <nav aria-label="Menu principal">

        <p class="nav-rotulo">Navegação</p>

        <ul>
            <?php foreach ($itens as $item): ?>

                <li class="<?= $menuAtivo === $item["chave"] ? "ativo" : "" ?>">
                    <a href="index.php?pagina=<?= $item["rota"] ?>"
                       <?= $menuAtivo === $item["chave"] ? 'aria-current="page"' : "" ?>>
                        <i class="fa-solid <?= $item["icone"] ?>"></i>
                        <span><?= $item["texto"] ?></span>
                    </a>
                </li>

            <?php endforeach; ?>
        </ul>

    </nav>

    <div class="sidebar-rodape">
        Sistema Inteligente de Monitoramento de Estoque
    </div>

</aside>
