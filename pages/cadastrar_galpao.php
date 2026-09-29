<?php
require_once __DIR__ . "/../database/conexao.php";

$flash = pegar_erro_formulario();
$erro = $flash["erro"];
$dados = $flash["dados"];


/*
 * A empresa vem da sessão, nunca de um campo do formulário — assim
 * um usuário não consegue cadastrar um galpão em empresa alheia.
 */
?>

<nav class="trilha">
    <a href="index.php?pagina=galpoes">Galpões</a>
    <i class="fa-solid fa-chevron-right"></i>
    <span>Cadastrar</span>
</nav>

<div class="pagina-topo">
    <div>
        <h1>Cadastrar galpão</h1>
        <p>Preencha as informações do novo galpão.</p>
    </div>
</div>

<?php if ($erro): ?>
    <div class="aviso aviso-erro">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= e($erro) ?></span>
    </div>
<?php endif; ?>

<form action="actions/salvar_galpao.php" method="POST" class="card">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <div class="form">
        <div class="form-grade">

            <div class="campo">
                <label for="nome">Nome do galpão</label>
                <input type="text" name="nome" id="nome"
                       placeholder="Ex: Galpão C" maxlength="80" required autofocus>
            </div>

            <div class="campo">
                <label for="metros_cubicos">Capacidade (m³)</label>
                <input type="number" name="metros_cubicos" id="metros_cubicos"
                       placeholder="Ex: 500" min="0.01" step="0.01" required>
                <span class="dica">Volume total que o galpão comporta.</span>
            </div>

            <div class="campo">
                <label for="ativo">Status</label>
                <select name="ativo" id="ativo">
                    <option value="1">Ativo</option>
                    <option value="0">Inativo</option>
                </select>
            </div>

            <div class="campo largo">
                <label for="descricao">Descrição</label>
                <textarea name="descricao" id="descricao" maxlength="255"
                          placeholder="Ex: Galpão principal de ferramentas"></textarea>
            </div>

        </div>
    </div>

    <div class="form-acoes">
        <a href="index.php?pagina=galpoes" class="btn btn-neutro">Cancelar</a>
        <button type="submit" class="btn btn-primario">
            <i class="fa-solid fa-check"></i>
            Cadastrar galpão
        </button>
    </div>

</form>
