<?php
require_once __DIR__ . "/../database/conexao.php";

$flash = pegar_erro_formulario();
$erro = $flash["erro"];
$dados = $flash["dados"];


$id = (int) ($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: index.php?pagina=galpoes");
    exit;
}

$empresa = empresa_id();

$stmt = $conexao->prepare("
    SELECT id, empresa_id, nome, descricao, metros_cubicos, ativo
    FROM galpoes WHERE id = ? AND empresa_id = ?
");

$stmt->bind_param("ii", $id, $empresa);
$stmt->execute();

$galpao = $stmt->get_result()->fetch_assoc();

if (!$galpao) {
    header("Location: index.php?pagina=galpoes");
    exit;
}
$capacidadeMinima = max(0.01, capacidadeOcupadaGalpao($conexao, $id));
foreach (["nome","descricao","metros_cubicos","ativo"] as $campo) {
    if (isset($dados[$campo])) $galpao[$campo] = $dados[$campo];
}
?>

<nav class="trilha">
    <a href="index.php?pagina=galpoes">Galpões</a>
    <i class="fa-solid fa-chevron-right"></i>
    <span><?= e($galpao["nome"]) ?></span>
</nav>

<div class="pagina-topo">
    <div>
        <h1>Editar galpão</h1>
        <p>Altere as informações do galpão.</p>
    </div>
</div>

<?php if ($erro): ?>
    <div class="aviso aviso-erro">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= e($erro) ?></span>
    </div>
<?php endif; ?>

<form action="actions/atualizar_galpao.php" method="POST" class="card">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <input type="hidden" name="id" value="<?= $galpao["id"] ?>">

    <div class="form">
        <div class="form-grade">

            <div class="campo">
                <label for="nome">Nome do galpão</label>
                <input type="text" name="nome" id="nome" maxlength="80"
                       value="<?= e($galpao["nome"]) ?>" required>
            </div>

            <div class="campo">
                <label for="metros_cubicos">Capacidade (m³)</label>
                <input type="number" name="metros_cubicos" id="metros_cubicos"
                       value="<?= e($galpao["metros_cubicos"]) ?>"
                       min="<?= e($capacidadeMinima) ?>" step="0.01" required>
            </div>

            <div class="campo">
                <label for="ativo">Status</label>
                <select name="ativo" id="ativo">
                    <option value="1" <?= $galpao["ativo"] == 1 ? "selected" : "" ?>>Ativo</option>
                    <option value="0" <?= $galpao["ativo"] == 0 ? "selected" : "" ?>>Inativo</option>
                </select>
            </div>

            <div class="campo largo">
                <label for="descricao">Descrição</label>
                <textarea name="descricao" id="descricao"
                          maxlength="255"><?= e($galpao["descricao"]) ?></textarea>
            </div>

        </div>
    </div>

    <div class="form-acoes">
        <a href="index.php?pagina=detalhes_galpao&id=<?= $galpao["id"] ?>"
           class="btn btn-neutro">Cancelar</a>
        <button type="submit" class="btn btn-primario">
            <i class="fa-solid fa-check"></i>
            Salvar alterações
        </button>
    </div>

</form>
