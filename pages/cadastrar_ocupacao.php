<?php
require_once __DIR__ . "/../database/conexao.php";

$flash = pegar_erro_formulario();
$erro = $flash["erro"];
$dados = $flash["dados"];


$prateleiraSelecionada = (int) ($_GET["prateleira_id"] ?? 0);

if (!empty($dados)) {
    $prateleiraSelecionada = (int) ($dados["prateleira_id"] ?? $prateleiraSelecionada);
}

/*
 * O espaço livre já vem calculado do banco, assim o select
 * mostra quanto ainda cabe em cada prateleira.
 */
$empresa = empresa_id();

$stmt = $conexao->prepare("
    SELECT
        p.id,
        p.codigo,
        p.metros_cubicos,
        g.nome AS galpao,
        GREATEST(
            0,
            p.metros_cubicos - COALESCE((
                SELECT SUM(o.volume_m3)
                FROM ocupacoes o
                WHERE o.prateleira_id = p.id
                AND o.data_saida IS NULL
            ), 0)
        ) AS livre
    FROM prateleiras p
    INNER JOIN galpoes g ON g.id = p.galpao_id
    WHERE p.ativo = 1 AND g.ativo = 1
    AND g.empresa_id = ?
    ORDER BY g.nome, p.codigo
");

$stmt->bind_param("i", $empresa);
$stmt->execute();

$prateleiras = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$voltarPara = $prateleiraSelecionada > 0
    ? "index.php?pagina=detalhes_prateleira&id=" . $prateleiraSelecionada
    : "index.php?pagina=ocupacoes";
?>

<nav class="trilha">
    <a href="index.php?pagina=ocupacoes">Ocupações</a>
    <i class="fa-solid fa-chevron-right"></i>
    <span>Registrar</span>
</nav>

<div class="pagina-topo">
    <div>
        <h1>Registrar ocupação</h1>
        <p>Registre a entrada de um produto em uma prateleira.</p>
    </div>
</div>

<?php if (empty($prateleiras)): ?>

    <div class="card">
        <div class="vazio">
            <i class="fa-solid fa-layer-group"></i>
            <h3>Nenhuma prateleira ativa</h3>
            <p>Cadastre uma prateleira antes de registrar ocupações.</p>
            <a href="index.php?pagina=cadastrar_prateleira" class="btn btn-primario">
                <i class="fa-solid fa-plus"></i>
                Cadastrar prateleira
            </a>
        </div>
    </div>

<?php else: ?>

<?php if ($erro): ?>
    <div class="aviso aviso-erro">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= e($erro) ?></span>
    </div>
<?php endif; ?>

<form action="actions/cadastrar_ocupacao.php" method="POST" class="card">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <div class="form">
        <div class="form-grade">

            <div class="campo largo">
                <label for="prateleira_id">Prateleira</label>
                <select name="prateleira_id" id="prateleira_id" required>
                    <option value="">Selecione uma prateleira</option>
                    <?php foreach ($prateleiras as $p): ?>
                        <option data-livre="<?= e($p["livre"]) ?>" value="<?= $p["id"] ?>"
                            <?= $p["id"] == $prateleiraSelecionada ? "selected" : "" ?>>
                            <?= e($p["galpao"]) ?> · <?= e($p["codigo"]) ?>
                            — <?= m3($p["livre"]) ?> disponíveis
                            de <?= m3($p["metros_cubicos"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="dica">
                    O volume informado não pode exceder o espaço livre da prateleira.
                </span>
            </div>

            <div class="campo">
                <label for="produto">Produto / item</label>
                <input type="text" name="produto" id="produto" maxlength="150"
                       value="<?= e($dados["produto"] ?? "") ?>" placeholder="Ex: Ferramentas de usinagem" required>
            </div>

            <div class="campo">
                <label for="setor">Setor</label>
                <input type="text" name="setor" id="setor" maxlength="100"
                       value="<?= e($dados["setor"] ?? "") ?>" placeholder="Ex: Usinagem" required>
            </div>

            <div class="campo">
                <label for="volume_m3">Volume ocupado (m³)</label>
                <input type="number" name="volume_m3" id="volume_m3"
                       step="0.01" min="0.01" value="<?= e($dados["volume_m3"] ?? "") ?>" placeholder="Ex: 2,00" required>
            </div>

            <div class="campo">
                <label for="data_entrada">Data de entrada</label>
                <input type="date" name="data_entrada" id="data_entrada"
                       value="<?= e($dados["data_entrada"] ?? date("Y-m-d")) ?>" required>
            </div>

            <div class="campo largo">
                <label for="observacao">Observação</label>
                <textarea name="observacao" id="observacao" maxlength="255"
                          placeholder="Observações sobre o armazenamento..."><?= e($dados["observacao"] ?? "") ?></textarea>
            </div>

        </div>
    </div>

    <div class="form-acoes">
        <a href="<?= $voltarPara ?>" class="btn btn-neutro">Cancelar</a>
        <button type="submit" class="btn btn-primario">
            <i class="fa-solid fa-check"></i>
            Registrar ocupação
        </button>
    </div>

</form>

<?php endif; ?>
