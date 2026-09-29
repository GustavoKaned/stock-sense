<?php

require_once __DIR__ . "/database/conexao.php";
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/funcoes.php";

// Quem já está logado não precisa ver esta tela.
if (esta_logado()) {
    header("Location: index.php");
    exit;
}

$erro = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";

    if ($email === "" || $senha === "") {

        $erro = "Informe o e-mail e a senha.";

    } else {

        $sql = "SELECT
                    u.id,
                    u.nome,
                    u.senha,
                    u.cargo,
                    u.empresa_id,
                    e.nome AS empresa_nome,
                    e.ativo AS empresa_ativa
                FROM usuarios u
                INNER JOIN empresas e ON e.id = u.empresa_id
                WHERE u.email = ? AND u.ativo = 1";

        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $usuario = $stmt->get_result()->fetch_assoc();

        /*
         * A mensagem é a mesma para e-mail inexistente e senha errada:
         * respostas diferentes permitiriam descobrir quais e-mails
         * estão cadastrados no sistema.
         */
        if ($usuario && password_verify($senha, $usuario["senha"])) {

            if ((int) $usuario["empresa_ativa"] !== 1) {

                $erro = "A empresa vinculada a este usuário está inativa.";

            } else {

                iniciar_sessao_usuario($usuario);

                header("Location: index.php");
                exit;
            }

        } else {

            $erro = "E-mail ou senha incorretos.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2563eb">

    <title>Entrar · StockSense</title>

    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/acesso.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body class="tela-acesso">

    <main class="acesso">

        <div class="acesso-marca">
            <div class="logo-marca"><i class="fa-solid fa-cubes"></i></div>
            <h1>StockSense</h1>
            <p>Sistema Inteligente de Gestão e Monitoramento de Estoque</p>
        </div>

        <div class="card acesso-card">

            <div class="card-corpo">

                <h2>Entrar na sua conta</h2>
                <p class="texto-fraco acesso-sub">
                    Acesse o estoque da sua empresa.
                </p>

                <?php if ($erro): ?>
                    <div class="aviso aviso-erro">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?= e($erro) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET["cadastro"])): ?>
                    <div class="aviso aviso-sucesso">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Empresa cadastrada. Faça login para continuar.</span>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET["saiu"])): ?>
                    <div class="aviso aviso-ok">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Sessão encerrada com segurança.</span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="acesso-form">

                    <div class="campo">
                        <label for="email">E-mail</label>
                        <input type="email" name="email" id="email"
                               value="<?= e($email) ?>"
                               placeholder="voce@empresa.com"
                               autocomplete="username" required autofocus>
                    </div>

                    <div class="campo">
                        <label for="senha">Senha</label>
                        <input type="password" name="senha" id="senha"
                               placeholder="Sua senha"
                               autocomplete="current-password" required>
                    </div>

                    <button type="submit" class="btn btn-primario acesso-botao">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        Entrar
                    </button>

                </form>

            </div>

            <div class="acesso-rodape">
                Ainda não tem conta?
                <a href="cadastro.php">Cadastrar empresa</a>
            </div>

        </div>

        <p class="acesso-nota">
            © <?= date("Y") ?> StockSense — Trabalho de Conclusão de Curso
        </p>

    </main>

</body>

</html>
