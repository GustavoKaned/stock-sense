<?php
require_once __DIR__ . "/../database/conexao.php";

$id = (int) ($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: index.php?pagina=prateleiras");
    exit;
}

$empresa = empresa_id();

$stmt = $conexao->prepare("
    SELECT p.id, p.galpao_id, p.codigo, p.descricao,
           p.metros_cubicos, p.setor, p.ativo
    FROM prateleiras p
    INNER JOIN galpoes g ON g.id = p.galpao_id
    WHERE p.id = ? AND g.empresa_id = ?
");

$stmt->bind_param("ii", $id, $empresa);
$stmt->execute();

$prateleira = $stmt->get_result()->fetch_assoc();

if (!$prateleira) {
    header("Location: index.php?pagina=prateleiras");
    exit;
}

// Se veio de um erro de validação (ex: limite excedido), o que o
// usuário digitou tem prioridade sobre o que está salvo no banco —
// senão ele perderia a alteração que tentou fazer.
$flash = pegar_erro_formulario();
$erro  = $flash["erro"];

if (!empty($flash["dados"])) {
    $prateleira = array_merge($prateleira, $flash["dados"]);
}

$stmt = $conexao->prepare("
    SELECT id, nome FROM galpoes
    WHERE ativo = 1 AND empresa_id = ?
    ORDER BY nome
");

$stmt->bind_param("i", $empresa);
$stmt->execute();

$galpoes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<nav class="trilha">
    <a href="index.php?pagina=prateleiras">Prateleiras</a>
    <i class="fa-solid fa-chevron-right"></i>
    <span><?= e($prateleira["codigo"]) ?></span>
</nav>

<div class="pagina-topo">
    <div>
        <h1>Editar prateleira</h1>
        <p>Altere as informações da prateleira.</p>
    </div>
</div>

<?php if ($erro): ?>
    <div class="aviso aviso-erro">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= e($erro) ?></span>
    </div>
<?php endif; ?>

<form action="actions/atualizar_prateleira.php" method="POST" class="card">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <input type="hidden" name="id" value="<?= $prateleira["id"] ?>">

    <div class="form">
        <div class="form-grade">

            <div class="campo">
                <label for="galpao_id">Galpão</label>
                <select name="galpao_id" id="galpao_id" required>
                    <?php foreach ($galpoes as $galpao): ?>
                        <option value="<?= $galpao["id"] ?>"
                            <?= $galpao["id"] == $prateleira["galpao_id"] ? "selected" : "" ?>>
                            <?= e($galpao["nome"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="codigo">Código</label>
                <input type="text" name="codigo" id="codigo" maxlength="20"
                       value="<?= e($prateleira["codigo"]) ?>" required>
            </div>

            <div class="campo">
                <label for="metros_cubicos">Capacidade (m³)</label>
                <input type="number" name="metros_cubicos" id="metros_cubicos"
                       value="<?= e($prateleira["metros_cubicos"]) ?>"
                       min="0.01" step="0.01" required>
                <span class="dica">
                    Alterar a capacidade recalcula o percentual de ocupação.
                </span>
            </div>

            <div class="campo">
                <label for="setor">Setor</label>
                <input type="text" name="setor" id="setor" maxlength="100"
                       value="<?= e($prateleira["setor"]) ?>">
            </div>

            <div class="campo">
                <label for="ativo">Status</label>
                <select name="ativo" id="ativo">
                    <option value="1" <?= $prateleira["ativo"] == 1 ? "selected" : "" ?>>Ativo</option>
                    <option value="0" <?= $prateleira["ativo"] == 0 ? "selected" : "" ?>>Inativo</option>
                </select>
            </div>

            <div class="campo largo">
                <label for="descricao">Descrição</label>
                <textarea name="descricao" id="descricao"
                          maxlength="255"><?= e($prateleira["descricao"]) ?></textarea>
            </div>

        </div>
    </div>

    <div class="form-acoes">
        <a href="index.php?pagina=detalhes_prateleira&id=<?= $prateleira["id"] ?>"
           class="btn btn-neutro">Cancelar</a>
        <button type="submit" class="btn btn-primario">
            <i class="fa-solid fa-check"></i>
            Salvar alterações
        </button>
    </div>

</form>
