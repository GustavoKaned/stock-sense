<?php

require_once __DIR__ . "/../database/conexao.php";

header("Content-Type: application/json; charset=utf-8");

function responderJson($status, array $dados)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responderJson(405, [
        "ok" => false,
        "erro" => "Use o metodo POST."
    ]);
}

$input = [];
$conteudo = file_get_contents("php://input");

if ($conteudo !== "") {
    $json = json_decode($conteudo, true);

    if (is_array($json)) {
        $input = $json;
    }
}

$token = trim((string) ($_SERVER["HTTP_X_DEVICE_TOKEN"] ?? $input["token"] ?? $_POST["token"] ?? ""));
$distancia = $input["distancia_cm"] ?? $_POST["distancia_cm"] ?? null;
$percentual = $input["percentual_ocupado"] ?? $_POST["percentual_ocupado"] ?? null;

if ($token === "" || $distancia === null || $percentual === null) {
    responderJson(422, [
        "ok" => false,
        "erro" => "Envie token, distancia_cm e percentual_ocupado."
    ]);
}

if (!is_numeric($distancia) || !is_numeric($percentual)) {
    responderJson(422, [
        "ok" => false,
        "erro" => "distancia_cm e percentual_ocupado devem ser numericos."
    ]);
}

$distancia = (float) $distancia;
$percentual = (float) $percentual;

if ($distancia < 0 || $distancia > 1000) {
    responderJson(422, [
        "ok" => false,
        "erro" => "distancia_cm fora do intervalo aceito."
    ]);
}

if ($percentual < 0 || $percentual > 100) {
    responderJson(422, [
        "ok" => false,
        "erro" => "percentual_ocupado deve ficar entre 0 e 100."
    ]);
}

// O token substitui a sessao de login: o ESP nao usa cookie de sessao.
$sql = "SELECT
            d.id,
            d.prateleira_id,
            d.ativo AS dispositivo_ativo,
            p.ativo AS prateleira_ativa,
            g.ativo AS galpao_ativo
        FROM dispositivos d
        INNER JOIN prateleiras p ON p.id = d.prateleira_id
        INNER JOIN galpoes g ON g.id = p.galpao_id
        WHERE d.token = ?
        LIMIT 1";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$dispositivo = $stmt->get_result()->fetch_assoc();

if (!$dispositivo || !$dispositivo["dispositivo_ativo"] || !$dispositivo["prateleira_ativa"] || !$dispositivo["galpao_ativo"]) {
    responderJson(401, [
        "ok" => false,
        "erro" => "Dispositivo invalido ou inativo."
    ]);
}

$dispositivoId = (int) $dispositivo["id"];
$prateleiraId = (int) $dispositivo["prateleira_id"];

$sql = "INSERT INTO leituras
            (dispositivo_id, prateleira_id, distancia_cm, percentual_ocupado)
        VALUES (?, ?, ?, ?)";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("iidd", $dispositivoId, $prateleiraId, $distancia, $percentual);

if (!$stmt->execute()) {
    error_log("StockSense api/leitura: " . $stmt->error);
    responderJson(500, [
        "ok" => false,
        "erro" => "Nao foi possivel salvar a leitura."
    ]);
}

// Nao altera pct_ocupado: esse percentual vem das ocupacoes cadastradas.
// O sensor fica separado em pct_sensor para o dashboard poder comparar os dois.
$sql = "UPDATE prateleiras
        SET pct_sensor = ?
        WHERE id = ?";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("di", $percentual, $prateleiraId);

if (!$stmt->execute()) {
    error_log("StockSense api/leitura update prateleiras: " . $stmt->error);
    responderJson(500, [
        "ok" => false,
        "erro" => "Leitura salva, mas o percentual da prateleira nao foi atualizado."
    ]);
}

responderJson(201, [
    "ok" => true,
    "mensagem" => "Leitura registrada com sucesso.",
    "prateleira_id" => $prateleiraId,
    "distancia_cm" => $distancia,
    "percentual_ocupado" => $percentual
]);
