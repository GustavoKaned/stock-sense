<?php


require_once __DIR__ . "/../includes/funcoes.php";
require_once __DIR__ . "/../includes/auth.php";

exigir_login("../");
exigir_post_seguro();
require_once __DIR__ . "/../database/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php?pagina=prateleiras");
    exit;
}

$galpao_id      = (int) ($_POST["galpao_id"] ?? 0);
$codigo         = trim($_POST["codigo"] ?? "");
$descricao      = trim($_POST["descricao"] ?? "");
$metros_cubicos = (float) ($_POST["metros_cubicos"] ?? 0);
$setor          = trim($_POST["setor"] ?? "");
$ativo          = (int) ($_POST["ativo"] ?? 1);

if ($galpao_id <= 0 || $codigo === "" || $metros_cubicos <= 0) {
    voltar_com_erro(
        "../index.php?pagina=cadastrar_prateleira",
        "Preencha todos os campos obrigatórios."
    );
}

// Impede cadastrar uma prateleira dentro do galpão de outra empresa.
exigir_propriedade(galpao_da_empresa($conexao, $galpao_id), "../", "prateleiras");

// Impede que a soma das prateleiras ultrapasse a capacidade do galpão.
$sql = "SELECT metros_cubicos FROM galpoes WHERE id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $galpao_id);
$stmt->execute();

$galpao = $stmt->get_result()->fetch_assoc();

if (!$galpao) {
    voltar_com_erro(
        "../index.php?pagina=cadastrar_prateleira",
        "Galpão não encontrado."
    );
}

$capacidadeGalpao    = (float) $galpao["metros_cubicos"];
$capacidadeOcupada   = capacidadeOcupadaGalpao($conexao, $galpao_id);
$capacidadeDisponivel = $capacidadeGalpao - $capacidadeOcupada;

if ($metros_cubicos > $capacidadeDisponivel) {
    voltar_com_erro(
        "../index.php?pagina=cadastrar_prateleira",
        "Limite excedido: o galpão tem capacidade declarada de " .
        number_format($capacidadeGalpao, 2, ",", ".") . " m³ e já possui " .
        number_format($capacidadeOcupada, 2, ",", ".") . " m³ em prateleiras — " .
        "restam apenas " . number_format(max(0, $capacidadeDisponivel), 2, ",", ".") .
        " m³ disponíveis."
    );
}

$sql = "INSERT INTO prateleiras
        (galpao_id, codigo, descricao, metros_cubicos, setor, ativo)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conexao->prepare($sql);

$stmt->bind_param(
    "issdsi",
    $galpao_id,
    $codigo,
    $descricao,
    $metros_cubicos,
    $setor,
    $ativo
);

if (!$stmt->execute()) {

    // 1062 = violação da chave única (galpao_id + codigo)
    if ($conexao->errno === 1062) {
        voltar_com_erro(
            "../index.php?pagina=cadastrar_prateleira",
            "Já existe uma prateleira com esse código neste galpão."
        );
    }

    voltar_com_erro(
        "../index.php?pagina=cadastrar_prateleira",
        "Erro ao cadastrar a prateleira: " . $stmt->error
    );
}

concluir_acao("../index.php?pagina=detalhes_galpao&id=" . $galpao_id, "Prateleira cadastrada.");
