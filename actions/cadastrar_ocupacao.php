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

$prateleira_id = (int) ($_POST["prateleira_id"] ?? 0);
$produto = trim($_POST["produto"] ?? "");
$setor = trim($_POST["setor"] ?? "");
$volume_m3 = isset($_POST["volume_m3"]) ? (float) $_POST["volume_m3"] : 0;
$data_entrada = $_POST["data_entrada"] ?? "";
$observacao = trim($_POST["observacao"] ?? "");

if (
    $prateleira_id <= 0 ||
    $produto === "" ||
    $setor === "" ||
    $volume_m3 <= 0 ||
    $data_entrada === ""
) {
    voltar_com_erro(
        "../index.php?pagina=cadastrar_ocupacao&prateleira_id=" . $prateleira_id,
        "Preencha todos os campos obrigatórios."
    );
}

// A prateleira precisa pertencer à empresa logada.
exigir_propriedade(
    prateleira_da_empresa($conexao, $prateleira_id),
    "../",
    "ocupacoes"
);

// Verificar capacidade atual da prateleira.
$sql = "SELECT p.metros_cubicos
        FROM prateleiras p INNER JOIN galpoes g ON g.id = p.galpao_id
        WHERE p.id = ? AND p.ativo = 1 AND g.ativo = 1";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $prateleira_id);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    voltar_com_erro(
        "../index.php?pagina=cadastrar_ocupacao",
        "A prateleira ou o galpão não está disponível para novas entradas."
    );
}

$prateleira = $resultado->fetch_assoc();
$capacidade = (float) $prateleira["metros_cubicos"];

// Buscar o volume atualmente ocupado.
$sql = "SELECT COALESCE(SUM(volume_m3), 0) AS volume_ocupado
        FROM ocupacoes
        WHERE prateleira_id = ?
        AND data_saida IS NULL";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $prateleira_id);
$stmt->execute();

$dados = $stmt->get_result()->fetch_assoc();
$volume_atual = (float) $dados["volume_ocupado"];

$novo_volume_total = $volume_atual + $volume_m3;

if ($novo_volume_total > $capacidade) {
    voltar_com_erro(
        "../index.php?pagina=cadastrar_ocupacao&prateleira_id=" . $prateleira_id,
        "Não é possível registrar esta ocupação. A capacidade da prateleira é de " .
        number_format($capacidade, 2, ",", ".") .
        " m³ e o volume informado ultrapassa o espaço disponível."
    );
}

// Inserir ocupação.
// Com o login implementado, fica registrado QUEM fez o lançamento.
$usuario = usuario_id();

$sql = "INSERT INTO ocupacoes (
            prateleira_id,
            produto,
            setor,
            volume_m3,
            data_entrada,
            observacao,
            usuario_id
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conexao->prepare($sql);
$stmt->bind_param(
    "issdssi",
    $prateleira_id,
    $produto,
    $setor,
    $volume_m3,
    $data_entrada,
    $observacao,
    $usuario
);

if (!$stmt->execute()) {
    error_log("StockSense cadastrar_ocupacao: " . $stmt->error);
    voltar_com_erro(
        "../index.php?pagina=cadastrar_ocupacao&prateleira_id=" . $prateleira_id,
        "Não foi possível cadastrar a ocupação. Verifique os dados e tente novamente."
    );
}

// Uma única regra centralizada recalcula o percentual.
atualizarPercentualPrateleira($conexao, $prateleira_id);

concluir_acao("../index.php?pagina=detalhes_prateleira&id=" .
    $prateleira_id
, "Ocupação registrada.");
