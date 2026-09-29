<?php


require_once __DIR__ . "/../includes/auth.php";

exigir_login("../");
exigir_post_seguro();
require_once __DIR__ . "/../database/conexao.php";

$id = (int) ($_POST["id"] ?? 0);

if ($id <= 0) {
    header("Location: ../index.php?pagina=prateleiras");
    exit;
}

exigir_propriedade(prateleira_da_empresa($conexao, $id), "../", "prateleiras");

$sql = "DELETE FROM prateleiras WHERE id = ?";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id);

if (!$stmt->execute()) {
    error_log("StockSense excluir_prateleira: " . $stmt->error);
    redirecionar_com_erro("../index.php?pagina=prateleiras", "Não foi possível excluir a prateleira. Verifique as ocupações vinculadas e tente novamente.");
}

concluir_acao("../index.php?pagina=prateleiras", "Prateleira excluída.");
