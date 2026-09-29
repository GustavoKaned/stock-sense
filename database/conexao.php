<?php

/**
 * Conexao principal do StockSense.
 *
 * - XAMPP/local: usa localhost, root, sem senha por padrao.
 * - Producao: crie database/conexao.local.php com as credenciais do host.
 *
 * O arquivo local fica fora do Git para evitar publicar senhas.
 */

$arquivoLocal = __DIR__ . "/conexao.local.php";

if (file_exists($arquivoLocal)) {
    require $arquivoLocal;
} else {
    $hostHttp = strtolower((string) ($_SERVER["HTTP_HOST"] ?? $_SERVER["SERVER_NAME"] ?? ""));
    $hostBase = preg_replace('/:\d+$/', '', $hostHttp);

    $ehLocal = in_array($hostBase, ["localhost", "127.0.0.1", "::1"], true);

    if ($ehLocal) {
        $host = "localhost";
        $usuario = "root";
        $senha = "";
        $banco = "stocksense";
    } else {
        $host    = getenv("STOCKSENSE_DB_HOST") ?: "";
        $usuario = getenv("STOCKSENSE_DB_USER") ?: "";
        $senha   = getenv("STOCKSENSE_DB_PASS") ?: "";
        $banco   = getenv("STOCKSENSE_DB_NAME") ?: "";

        if ($host === "" || $usuario === "" || $banco === "") {
            http_response_code(500);
            exit("Configuracao do banco nao encontrada. Crie database/conexao.local.php.");
        }
    }
}

$conexao = new mysqli($host, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    error_log("StockSense: erro na conexao com o banco: " . $conexao->connect_error);
    http_response_code(500);
    exit("Nao foi possivel conectar ao banco de dados.");
}

$conexao->set_charset("utf8mb4");
