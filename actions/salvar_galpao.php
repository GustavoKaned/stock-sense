<?php


require_once __DIR__ . "/../includes/auth.php";

exigir_login("../");
exigir_post_seguro();
require_once __DIR__ . "/../database/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php?pagina=galpoes");
    exit;
}

$nome           = trim($_POST["nome"] ?? "");
$descricao      = trim($_POST["descricao"] ?? "");
$metros_cubicos = (float) ($_POST["metros_cubicos"] ?? 0);
$ativo          = (int) ($_POST["ativo"] ?? 1);

// A empresa vem SEMPRE da sessão, nunca do formulário.
$empresa_id = empresa_id();

if ($nome === "" || $metros_cubicos <= 0) {
    voltar_com_erro(
        "../index.php?pagina=cadastrar_galpao",
        "Preencha todos os campos obrigatórios. A capacidade deve ser maior que zero."
    );
}

$sql = "INSERT INTO galpoes
        (empresa_id, nome, descricao, metros_cubicos, ativo)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conexao->prepare($sql);

$stmt->bind_param(
    "issdi",
    $empresa_id,
    $nome,
    $descricao,
    $metros_cubicos,
    $ativo
);

if (!$stmt->execute()) {
    error_log("StockSense salvar_galpao: " . $stmt->error);
    voltar_com_erro(
        "../index.php?pagina=cadastrar_galpao",
        "Não foi possível cadastrar o galpão. Verifique os dados e tente novamente."
    );
}

concluir_acao("../index.php?pagina=detalhes_galpao&id=" . $conexao->insert_id, "Galpão cadastrado.");

