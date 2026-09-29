<?php


require_once __DIR__ . "/../includes/funcoes.php";
require_once __DIR__ . "/../includes/auth.php";

exigir_login("../");
exigir_post_seguro();
require_once __DIR__ . "/../database/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php?pagina=ocupacoes");
    exit;
}

$id = (int) ($_POST["id"] ?? 0);
$produto = trim($_POST["produto"] ?? "");
$setor = trim($_POST["setor"] ?? "");
$volume_m3 = isset($_POST["volume_m3"]) ? (float) $_POST["volume_m3"] : 0;
$data_entrada = $_POST["data_entrada"] ?? "";
$observacao = trim($_POST["observacao"] ?? "");

if (
    $id <= 0 ||
    $produto === "" ||
    $setor === "" ||
    $volume_m3 <= 0 ||
    $data_entrada === ""
) {
    voltar_com_erro(
        "../index.php?pagina=editar_ocupacao&id=" . $id,
        "Preencha todos os campos obrigatórios."
    );
}

exigir_propriedade(ocupacao_da_empresa($conexao, $id), "../", "ocupacoes");

// Buscar a ocupação atual.
$sql = "SELECT prateleira_id, data_saida
        FROM ocupacoes
        WHERE id = ?";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    voltar_com_erro(
        "../index.php?pagina=ocupacoes",
        "Ocupação não encontrada."
    );
}

$ocupacao = $resultado->fetch_assoc();
$prateleira_id = (int) $ocupacao["prateleira_id"];

// Se ainda estiver armazenada, verificar se o novo volume cabe.
if ($ocupacao["data_saida"] === null) {

    $sql = "SELECT metros_cubicos
            FROM prateleiras
            WHERE id = ? AND ativo = 1";

    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $prateleira_id);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        voltar_com_erro(
            "../index.php?pagina=editar_ocupacao&id=" . $id,
            "Prateleira não encontrada ou está inativa."
        );
    }

    $capacidade = (float) $resultado->fetch_assoc()["metros_cubicos"];

    $sql = "SELECT COALESCE(SUM(volume_m3), 0) AS total
            FROM ocupacoes
            WHERE prateleira_id = ?
            AND data_saida IS NULL
            AND id != ?";

    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("ii", $prateleira_id, $id);
    $stmt->execute();

    $outros_volumes = (float) $stmt->get_result()->fetch_assoc()["total"];
    $novo_total = $outros_volumes + $volume_m3;

    if ($novo_total > $capacidade) {
        voltar_com_erro(
            "../index.php?pagina=editar_ocupacao&id=" . $id,
            "Não é possível atualizar. A capacidade da prateleira é de " .
            number_format($capacidade, 2, ",", ".") . " m³."
        );
    }
}

// Atualizar ocupação.
$sql = "UPDATE ocupacoes
        SET produto = ?,
            setor = ?,
            volume_m3 = ?,
            data_entrada = ?,
            observacao = ?
        WHERE id = ?";

$stmt = $conexao->prepare($sql);
$stmt->bind_param(
    "ssdssi",
    $produto,
    $setor,
    $volume_m3,
    $data_entrada,
    $observacao,
    $id
);

if (!$stmt->execute()) {
    error_log("StockSense atualizar_ocupacao: " . $stmt->error);
    voltar_com_erro(
        "../index.php?pagina=editar_ocupacao&id=" . $id,
        "Não foi possível atualizar a ocupação. Verifique os dados e tente novamente."
    );
}

// Regra centralizada.
atualizarPercentualPrateleira($conexao, $prateleira_id);

concluir_acao("../index.php?pagina=detalhes_prateleira&id=" .
    $prateleira_id
, "Ocupação atualizada.");
