<?php
require_once __DIR__ . "/../database/conexao.php";

$flash = pegar_erro_formulario();
$erro = $flash["erro"];
$dados = $flash["dados"];


$id = (int) ($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: index.php?pagina=ocupacoes");
    exit;
}

$empresa = empresa_id();

$stmt = $conexao->prepare("
    SELECT
        o.id,
        o.prateleira_id,
        o.produto,
        o.setor,
        o.volume_m3,
        o.data_entrada,
        o.data_saida,
        o.observacao,
        p.codigo AS prateleira_codigo,
        p.metros_cubicos,
        g.nome AS galpao_nome
    FROM ocupacoes o
    INNER JOIN prateleiras p ON p.id = o.prateleira_id
    INNER JOIN galpoes g ON g.id = p.galpao_id
    WHERE o.id = ? AND g.empresa_id = ?
");

$stmt->bind_param("ii", $id, $empresa);
$stmt->execute();

$ocupacao = $stmt->get_result()->fetch_assoc();

if (!$ocupacao) {
    echo '<div class="vazio">
            <i class="fa-solid fa-circle-exclamation"></i>
            <h3>Ocupação não encontrada</h3>
            <a href="index.php?pagina=ocupacoes" class="btn btn-neutro">Voltar</a>
          </div>';
    return;
}

$ativa = $ocupacao["data_saida"] === null;
$volumeMaximo = null;
if ($ativa) {
    $stmt = $conexao->prepare('SELECT COALESCE(SUM(volume_m3), 0) AS total
        FROM ocupacoes WHERE prateleira_id = ? AND data_saida IS NULL AND id != ?');
    $stmt->bind_param('ii', $ocupacao['prateleira_id'], $id);
    $stmt->execute();
    $volumeMaximo = max(0, (float) $ocupacao['metros_cubicos'] - (float) $stmt->get_result()->fetch_assoc()['total']);
}
foreach (["produto","setor","volume_m3","data_entrada","observacao"] as $campo) {
    if (isset($dados[$campo])) $ocupacao[$campo] = $dados[$campo];
}
?>

<nav class="trilha">
    <a href="index.php?pagina=ocupacoes">Ocupações</a>
    <i class="fa-solid fa-chevron-right"></i>
    <span><?= e($ocupacao["produto"]) ?></span>
</nav>

<div class="pagina-topo">
    <div>
        <h1>Editar ocupação</h1>
        <p>Altere os dados do produto armazenado.</p>
    </div>
</div>

<?php if ($erro): ?>
    <div class="aviso aviso-erro">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= e($erro) ?></span>
    </div>
<?php endif; ?>

<form action="actions/atualizar_ocupacao.php" method="POST" class="card">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <input type="hidden" name="id" value="<?= $ocupacao["id"] ?>">

    <div class="info-grade">

        <div class="info-item">
            <span>Prateleira</span>
            <strong><?= e($ocupacao["prateleira_codigo"]) ?></strong>
        </div>

        <div class="info-item">
            <span>Galpão</span>
            <strong><?= e($ocupacao["galpao_nome"]) ?></strong>
        </div>

        <div class="info-item">
            <span>Capacidade</span>
            <strong><?= m3($ocupacao["metros_cubicos"]) ?></strong>
        </div>

        <div class="info-item">
            <span>Situação</span>
            <strong>
                <?php if ($ativa): ?>
                    <span class="etiqueta parcial">Armazenado</span>
                <?php else: ?>
                    <span class="etiqueta inativo">
                        Retirado em <?= data_br($ocupacao["data_saida"]) ?>
                    </span>
                <?php endif; ?>
            </strong>
        </div>

    </div>

    <div class="form">
        <div class="form-grade">

            <div class="campo">
                <label for="produto">Produto / item</label>
                <input type="text" name="produto" id="produto" maxlength="150"
                       value="<?= e($ocupacao["produto"]) ?>" required>
            </div>

            <div class="campo">
                <label for="setor">Setor</label>
                <input type="text" name="setor" id="setor" maxlength="100"
                       value="<?= e($ocupacao["setor"]) ?>" required>
            </div>

            <div class="campo">
                <label for="volume_m3">Volume ocupado (m³)</label>
                <input type="number" name="volume_m3" id="volume_m3"
                       step="0.01" min="0.01" <?= $volumeMaximo !== null ? 'max="' . e($volumeMaximo) . '"' : '' ?>
                       value="<?= e($ocupacao["volume_m3"]) ?>" required>
            </div>

            <div class="campo">
                <label for="data_entrada">Data de entrada</label>
                <input type="date" name="data_entrada" id="data_entrada"
                       value="<?= e($ocupacao["data_entrada"]) ?>" required>
            </div>

            <div class="campo largo">
                <label for="observacao">Observação</label>
                <textarea name="observacao" id="observacao"
                          maxlength="255"><?= e($ocupacao["observacao"]) ?></textarea>
            </div>

        </div>
    </div>

    <div class="form-acoes">
        <a href="index.php?pagina=detalhes_prateleira&id=<?= $ocupacao["prateleira_id"] ?>"
           class="btn btn-neutro">Cancelar</a>
        <button type="submit" class="btn btn-primario">
            <i class="fa-solid fa-check"></i>
            Salvar alterações
        </button>
    </div>

</form>
