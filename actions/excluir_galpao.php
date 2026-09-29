<?php


require_once __DIR__ . "/../includes/auth.php";

exigir_login("../");
exigir_post_seguro();
require_once __DIR__ . "/../database/conexao.php";

$id = (int) ($_POST["id"] ?? 0);

if ($id <= 0) {
    header("Location: ../index.php?pagina=galpoes");
    exit;
}

exigir_propriedade(galpao_da_empresa($conexao, $id), "../", "galpoes");

// O empresa_id no WHERE é uma segunda barreira, caso a checagem acima mude.
$sql = "DELETE FROM galpoes WHERE id = ? AND empresa_id = ?";

$stmt = $conexao->prepare($sql);

$empresa = empresa_id();
$stmt->bind_param("ii", $id, $empresa);

if (!$stmt->execute()) {
    error_log("StockSense excluir_galpao: " . $stmt->error);
    redirecionar_com_erro("../index.php?pagina=galpoes", "Não foi possível excluir o galpão. Verifique se existem dependências e tente novamente.");
}

concluir_acao("../index.php?pagina=galpoes", "Galpão excluído.");
