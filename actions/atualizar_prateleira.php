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

$id             = (int) ($_POST["id"] ?? 0);
$galpao_id      = (int) ($_POST["galpao_id"] ?? 0);
$codigo         = trim($_POST["codigo"] ?? "");
$descricao      = trim($_POST["descricao"] ?? "");
$metros_cubicos = (float) ($_POST["metros_cubicos"] ?? 0);
$setor          = trim($_POST["setor"] ?? "");
$ativo          = (int) ($_POST["ativo"] ?? 1);

if ($id <= 0 || $galpao_id <= 0 || $codigo === "" || $metros_cubicos <= 0) {
    voltar_com_erro(
        $id > 0
            ? "../index.php?pagina=editar_prateleira&id=" . $id
            : "../index.php?pagina=prateleiras",
        "Preencha todos os campos obrigatórios. A capacidade deve ser maior que zero."
    );
}

// A prateleira e o galpão de destino precisam ser da empresa logada.
exigir_propriedade(prateleira_da_empresa($conexao, $id), "../", "prateleiras");
exigir_propriedade(galpao_da_empresa($conexao, $galpao_id), "../", "prateleiras");

// Impede que a soma das prateleiras ultrapasse a capacidade do galpão.
// A própria prateleira (valor antigo) é excluída da soma, senão ela
// seria contada junto com o novo valor vindo do formulário.
$sql = "SELECT metros_cubicos FROM galpoes WHERE id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $galpao_id);
$stmt->execute();

$galpao = $stmt->get_result()->fetch_assoc();

if (!$galpao) {
    voltar_com_erro(
        "../index.php?pagina=editar_prateleira&id=" . $id,
        "Galpão não encontrado."
    );
}

$capacidadeGalpao    = (float) $galpao["metros_cubicos"];
$capacidadeOcupada   = capacidadeOcupadaGalpao($conexao, $galpao_id, $id);
$capacidadeDisponivel = $capacidadeGalpao - $capacidadeOcupada;

// A nova capacidade também precisa comportar o volume já armazenado.
$sql = "SELECT COALESCE(SUM(volume_m3), 0) AS volume_ocupado
        FROM ocupacoes
        WHERE prateleira_id = ?
        AND data_saida IS NULL";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$volumeOcupado = (float) $stmt->get_result()->fetch_assoc()["volume_ocupado"];

if ($metros_cubicos < $volumeOcupado) {
    voltar_com_erro(
        "../index.php?pagina=editar_prateleira&id=" . $id,
        "A capacidade da prateleira não pode ser menor que " .
        number_format($volumeOcupado, 2, ",", ".") .
        " m³, que já está ocupado."
    );
}

if ($metros_cubicos > $capacidadeDisponivel) {
    voltar_com_erro(
        "../index.php?pagina=editar_prateleira&id=" . $id,
        "Limite excedido: o galpão tem capacidade declarada de " .
        number_format($capacidadeGalpao, 2, ",", ".") . " m³ e já possui " .
        number_format($capacidadeOcupada, 2, ",", ".") . " m³ em outras prateleiras — " .
        "restam apenas " . number_format(max(0, $capacidadeDisponivel), 2, ",", ".") .
        " m³ disponíveis."
    );
}

$sql = "UPDATE prateleiras
        SET galpao_id = ?,
            codigo = ?,
            descricao = ?,
            metros_cubicos = ?,
            setor = ?,
            ativo = ?
        WHERE id = ?";

$stmt = $conexao->prepare($sql);

$stmt->bind_param(
    "issdsii",
    $galpao_id,
    $codigo,
    $descricao,
    $metros_cubicos,
    $setor,
    $ativo,
    $id
);

if (!$stmt->execute()) {
    voltar_com_erro(
        "../index.php?pagina=editar_prateleira&id=" . $id,
        "Erro ao atualizar a prateleira: " . $stmt->error
    );
}

/*
 * A capacidade pode ter mudado, então o percentual de ocupação
 * precisa ser recalculado — caso contrário a prateleira continuaria
 * exibindo o percentual antigo no dashboard.
 */
atualizarPercentualPrateleira($conexao, $id);

concluir_acao("../index.php?pagina=detalhes_prateleira&id=" . $id, "Prateleira atualizada.");
