<?php

require_once __DIR__ . "/database/conexao.php";
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/funcoes.php";

// Nenhuma página do sistema é acessível sem login.
exigir_login();

/*
|--------------------------------------------------------------------------
| ROTEADOR
|--------------------------------------------------------------------------
| Cada rota declara o arquivo, o título e o CSS extra que precisa.
| Antes isso era um switch de ~60 linhas mais um array separado de CSS;
| agora é uma tabela só — adicionar uma página é acrescentar uma linha.
*/

$rotas = [
    "dashboard" => [
        "arquivo" => "dashboard.php",
        "titulo"  => "Visão geral",
        "css"     => "dashboard.css",
        "menu"    => "dashboard",
    ],

    "galpoes" => [
        "arquivo" => "galpoes.php",
        "titulo"  => "Galpões",
        "menu"    => "galpoes",
    ],
    "cadastrar_galpao" => [
        "arquivo" => "cadastrar_galpao.php",
        "titulo"  => "Cadastrar galpão",
        "menu"    => "galpoes",
    ],
    "editar_galpao" => [
        "arquivo" => "editar_galpao.php",
        "titulo"  => "Editar galpão",
        "menu"    => "galpoes",
    ],
    "detalhes_galpao" => [
        "arquivo" => "detalhes_galpao.php",
        "titulo"  => "Detalhes do galpão",
        "css"     => "detalhes.css",
        "menu"    => "galpoes",
    ],

    "prateleiras" => [
        "arquivo" => "prateleiras.php",
        "titulo"  => "Prateleiras",
        "menu"    => "prateleiras",
    ],
    "cadastrar_prateleira" => [
        "arquivo" => "cadastrar_prateleira.php",
        "titulo"  => "Cadastrar prateleira",
        "menu"    => "prateleiras",
    ],
    "editar_prateleira" => [
        "arquivo" => "editar_prateleira.php",
        "titulo"  => "Editar prateleira",
        "menu"    => "prateleiras",
    ],
    "detalhes_prateleira" => [
        "arquivo" => "detalhes_prateleira.php",
        "titulo"  => "Detalhes da prateleira",
        "css"     => "detalhes.css",
        "menu"    => "prateleiras",
    ],

    "ocupacoes" => [
        "arquivo" => "ocupacoes.php",
        "titulo"  => "Ocupações",
        "menu"    => "ocupacoes",
    ],
    "cadastrar_ocupacao" => [
        "arquivo" => "cadastrar_ocupacao.php",
        "titulo"  => "Registrar ocupação",
        "menu"    => "ocupacoes",
    ],
    "editar_ocupacao" => [
        "arquivo" => "editar_ocupacao.php",
        "titulo"  => "Editar ocupação",
        "menu"    => "ocupacoes",
    ],
];

$pagina = is_string($_GET["pagina"] ?? null) ? $_GET["pagina"] : "dashboard";

// Rota desconhecida cai no dashboard.
if (!isset($rotas[$pagina])) {
    $pagina = "dashboard";
}

$rota = $rotas[$pagina];

$flashErroGlobal = pegar_flash_erro();

$menuAtivo = $rota["menu"];
$tituloPagina = $rota["titulo"];
$cssExtra = $rota["css"] ?? null;

// Renderizar antes do layout permite redirecionar sem headers já enviados.
ob_start();
include __DIR__ . "/pages/" . $rota["arquivo"];
$conteudoPagina = ob_get_clean();
$flashSucesso = $_SESSION["flash_sucesso"] ?? null;
unset($_SESSION["flash_sucesso"]);

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2563eb">

    <title><?= e($tituloPagina) ?> · StockSense</title>

    <script src="assets/js/tema.js"></script>
    <link rel="stylesheet" href="assets/css/base.css?v=<?= filemtime(__DIR__ . '/assets/css/base.css') ?>">
    <link rel="stylesheet" href="assets/css/detalhes.css?v=<?= filemtime(__DIR__ . "/assets/css/detalhes.css") ?>">
    <link rel="stylesheet" href="assets/css/acesso.css">

    <?php if ($cssExtra && $cssExtra !== 'detalhes.css'): ?>
        <link rel="stylesheet" href="assets/css/<?= e($cssExtra) ?>?v=<?= filemtime(__DIR__ . "/assets/css/" . $cssExtra) ?>">
    <?php endif; ?>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>
    <a class="pular-conteudo" href="#conteudoPrincipal">Pular para o conteúdo</a>

    <div class="container">

        <?php include "includes/sidebar.php"; ?>

        <div class="overlay" id="overlay"></div>

        <div class="conteudo">

            <?php include "includes/header.php"; ?>

            <main class="pagina" id="conteudoPrincipal" tabindex="-1">

                <?php if ($flashErroGlobal): ?>
                    <div class="aviso aviso-erro" data-toast="erro">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?= e($flashErroGlobal) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($flashSucesso): ?><div class="aviso" data-toast="sucesso"><?= e($flashSucesso) ?></div><?php endif; ?>
                <?= $conteudoPagina ?>

            </main>

            <?php include "includes/footer.php"; ?>

        </div>

    </div>

    <script src="assets/js/app.js?v=<?= filemtime(__DIR__ . "/assets/js/app.js") ?>"></script>

</body>

</html>
