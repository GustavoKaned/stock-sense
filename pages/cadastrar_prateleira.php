<?php
require_once __DIR__ . "/../database/conexao.php";

$galpaoSelecionado = (int) ($_GET["galpao_id"] ?? 0);

// Se veio de um erro de validação (ex: limite excedido), reaproveita o
// que o usuário já tinha digitado em vez de limpar o formulário.
$flash = pegar_erro_formulario();
$erro  = $flash["erro"];
$dados = $flash["dados"];

if (!empty($dados)) {
    $galpaoSelecionado = (int) ($dados["galpao_id"] ?? $galpaoSelecionado);
}

$codigo         = $dados["codigo"] ?? "";
$descricao      = $dados["descricao"] ?? "";
$metros_cubicos = $dados["metros_cubicos"] ?? "";
$setor          = $dados["setor"] ?? "";
$ativo          = $dados["ativo"] ?? "1";

// Só aparecem galpões da empresa logada.
$empresa = empresa_id();

$stmt = $conexao->prepare("
    SELECT id, nome, GREATEST(0, metros_cubicos - COALESCE((
        SELECT SUM(p.metros_cubicos) FROM prateleiras p WHERE p.galpao_id = galpoes.id
    ), 0)) AS livre FROM galpoes
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
    <span>Cadastrar</span>
</nav>

<div class="pagina-topo">
    <div>
        <h1>Cadastrar prateleira</h1>
        <p>Cadastre uma nova prateleira em um galpão.</p>
    </div>
</div>

<?php if ($erro): ?>
    <div class="aviso aviso-erro">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= e($erro) ?></span>
    </div>
<?php endif; ?>

<?php if (empty($galpoes)): ?>

    <div class="card">
        <div class="vazio">
            <i class="fa-solid fa-warehouse"></i>
            <h3>Nenhum galpão ativo</h3>
            <p>Cadastre um galpão antes de criar prateleiras.</p>
            <a href="index.php?pagina=cadastrar_galpao" class="btn btn-primario">
                <i class="fa-solid fa-plus"></i>
                Cadastrar galpão
            </a>
        </div>
    </div>

<?php else: ?>

<form action="actions/salvar_prateleira.php" method="POST" class="card">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <div class="form">
        <div class="form-grade">

            <div class="campo">
                <label for="galpao_id">Galpão</label>
                <select name="galpao_id" id="galpao_id" required>
                    <option value="">Selecione um galpão</option>
                    <?php foreach ($galpoes as $galpao): ?>
                        <option data-livre="<?= e($galpao["livre"]) ?>" value="<?= $galpao["id"] ?>"
                            <?= $galpao["id"] == $galpaoSelecionado ? "selected" : "" ?>>
                            <?= e($galpao["nome"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="codigo">Código</label>
                <input type="text" name="codigo" id="codigo"
                       placeholder="Ex: A1" maxlength="20"
                       value="<?= e($codigo) ?>" required>
                <span class="dica">Deve ser único dentro do galpão.</span>
            </div>

            <div class="campo">
                <label for="metros_cubicos">Capacidade (m³)</label>
                <input type="number" name="metros_cubicos" id="metros_cubicos"
                       placeholder="Ex: 5" min="0.01" step="0.01"
                       value="<?= e($metros_cubicos) ?>" required>
            </div>

            <div class="campo">
                <label for="setor">Setor</label>
                <input type="text" name="setor" id="setor"
                       placeholder="Ex: Usinagem" maxlength="100"
                       value="<?= e($setor) ?>">
            </div>

            <div class="campo">
                <label for="ativo">Status</label>
                <select name="ativo" id="ativo">
                    <option value="1" <?= $ativo == 1 ? "selected" : "" ?>>Ativo</option>
                    <option value="0" <?= $ativo == 0 ? "selected" : "" ?>>Inativo</option>
                </select>
            </div>

            <div class="campo largo">
                <label for="descricao">Descrição</label>
                <textarea name="descricao" id="descricao" maxlength="255"
                          placeholder="Localização ou características da prateleira"><?= e($descricao) ?></textarea>
            </div>

        </div>
    </div>

    <div class="form-acoes">
        <a href="index.php?pagina=prateleiras" class="btn btn-neutro">Cancelar</a>
        <button type="submit" class="btn btn-primario">
            <i class="fa-solid fa-check"></i>
            Cadastrar prateleira
        </button>
    </div>

</form>

<?php endif; ?>
