<?php


require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/funcoes.php";

exigir_login("../");
exigir_post_seguro();
require_once __DIR__ . "/../database/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php?pagina=galpoes");
    exit;
}

$id             = (int) ($_POST["id"] ?? 0);
$nome           = trim($_POST["nome"] ?? "");
$descricao      = trim($_POST["descricao"] ?? "");
$metros_cubicos = (float) ($_POST["metros_cubicos"] ?? 0);
$ativo          = (int) ($_POST["ativo"] ?? 1);

if ($id <= 0 || $nome === "" || $metros_cubicos <= 0) {
    voltar_com_erro(
        $id > 0 ? "../index.php?pagina=editar_galpao&id=" . $id : "../index.php?pagina=galpoes",
        "Preencha todos os campos obrigatórios. A capacidade deve ser maior que zero."
    );
}

// Sem esta checagem, bastaria alterar o id no formulário para
// editar o galpão de outra empresa.
exigir_propriedade(galpao_da_empresa($conexao, $id), "../", "galpoes");

// A capacidade do galpão não pode ficar menor que a soma das capacidades
// das prateleiras já cadastradas nele.
$capacidadePrateleiras = capacidadeOcupadaGalpao($conexao, $id);

if ($metros_cubicos < $capacidadePrateleiras) {
    voltar_com_erro(
        "../index.php?pagina=editar_galpao&id=" . $id,
        "A capacidade do galpão não pode ser menor que " .
        number_format($capacidadePrateleiras, 2, ",", ".") .
        " m³, que já está distribuída entre as prateleiras."
    );
}

/*
 * empresa_id não entra no UPDATE: um galpão nunca muda de dono.
 */
$sql = "UPDATE galpoes
        SET nome = ?,
            descricao = ?,
            metros_cubicos = ?,
            ativo = ?
        WHERE id = ? AND empresa_id = ?";

$stmt = $conexao->prepare($sql);

$empresa = empresa_id();

$stmt->bind_param(
    "ssdiii",
    $nome,
    $descricao,
    $metros_cubicos,
    $ativo,
    $id,
    $empresa
);

if (!$stmt->execute()) {
    error_log("StockSense atualizar_galpao: " . $stmt->error);
    voltar_com_erro(
        "../index.php?pagina=editar_galpao&id=" . $id,
        "Não foi possível atualizar o galpão. Verifique os dados e tente novamente."
    );
}

concluir_acao("../index.php?pagina=detalhes_galpao&id=" . $id, "Galpão atualizado.");
