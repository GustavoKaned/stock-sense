<?php

require_once __DIR__ . "/database/conexao.php";
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/funcoes.php";

if (esta_logado()) {
    header("Location: index.php");
    exit;
}

$erro = "";

$empresa   = "";
$cnpj      = "";
$responsavel = "";
$email     = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $empresa     = trim($_POST["empresa"] ?? "");
    $cnpj        = trim($_POST["cnpj"] ?? "");
    $responsavel = trim($_POST["responsavel"] ?? "");
    $email       = trim($_POST["email"] ?? "");
    $senha       = $_POST["senha"] ?? "";
    $confirmar   = $_POST["confirmar"] ?? "";

    // CNPJ é opcional; quando preenchido, só os dígitos são validados e
    // gravados — evita erro de banco por formatação e facilita comparar
    // duplicados (com ou sem máscara, ficam iguais).
    $cnpjNumeros = preg_replace("/\D/", "", $cnpj);

    if ($empresa === "" || $responsavel === "" || $email === "" || $senha === "") {

        $erro = "Preencha todos os campos obrigatórios.";

    } elseif (mb_strlen($empresa) > 150 || mb_strlen($responsavel) > 150) {

        $erro = "O nome da empresa e o do responsável podem ter no máximo 150 caracteres.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {

        $erro = "Informe um e-mail válido.";

    } elseif ($cnpj !== "" && !cnpjValido($cnpjNumeros)) {

        $erro = "Informe um CNPJ válido, com 14 dígitos.";

    } elseif (strlen($senha) < 6) {

        $erro = "A senha deve ter pelo menos 6 caracteres.";

    } elseif ($senha !== $confirmar) {

        $erro = "As senhas não coincidem.";

    } else {

        // O e-mail é único no sistema inteiro (é ele que identifica o login).
        $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {

            $erro = "Este e-mail já está cadastrado.";

        } elseif ($cnpjNumeros !== "" && cnpjJaCadastrado($conexao, $cnpjNumeros)) {

            $erro = "Este CNPJ já está cadastrado para outra empresa.";

        } else {

            /*
             * Empresa e usuário são criados juntos. A transação garante
             * que não sobre uma empresa sem nenhum usuário caso a segunda
             * inserção falhe.
             */
            $conexao->begin_transaction();

            try {

                $stmt = $conexao->prepare("
                    INSERT INTO empresas (nome, cnpj, email, ativo)
                    VALUES (?, ?, ?, 1)
                ");

                $stmt->bind_param("sss", $empresa, $cnpjNumeros, $email);
                $stmt->execute();

                $empresa_id = $conexao->insert_id;

                // A senha nunca é gravada em texto puro.
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $cargo = "Administrador";

                $stmt = $conexao->prepare("
                    INSERT INTO usuarios
                        (empresa_id, nome, email, senha, cargo, ativo)
                    VALUES (?, ?, ?, ?, ?, 1)
                ");

                $stmt->bind_param(
                    "issss",
                    $empresa_id,
                    $responsavel,
                    $email,
                    $hash,
                    $cargo
                );

                $stmt->execute();

                $conexao->commit();

                header("Location: login.php?cadastro=1");
                exit;

            } catch (mysqli_sql_exception $excecao) {

                $conexao->rollback();

                // 1062 = violação de chave única — proteção extra contra
                // concorrência (dois cadastros com o mesmo e-mail chegando
                // ao mesmo tempo, por exemplo), já que a checagem acima
                // sozinha não cobre esse caso.
                if ($excecao->getCode() === 1062) {
                    $erro = "Este e-mail já está cadastrado.";
                } else {
                    $erro = "Não foi possível concluir o cadastro: " . $excecao->getMessage();
                }
            }
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

    <title>Cadastrar empresa · StockSense</title>

    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/acesso.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body class="tela-acesso">

    <main class="acesso acesso-largo">

        <div class="acesso-marca">
            <div class="logo-marca"><i class="fa-solid fa-cubes"></i></div>
            <h1>StockSense</h1>
            <p>Cadastre sua empresa e comece a gerenciar seu estoque</p>
        </div>

        <div class="card acesso-card">

            <div class="card-corpo">

                <h2>Cadastrar empresa</h2>
                <p class="texto-fraco acesso-sub">
                    O primeiro usuário criado será o administrador da empresa.
                </p>

                <?php if ($erro): ?>
                    <div class="aviso aviso-erro">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?= e($erro) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <p class="nav-rotulo" style="padding:4px 0 8px">Empresa</p>

                    <div class="form-grade">

                        <div class="campo">
                            <label for="empresa">Nome da empresa *</label>
                            <input type="text" name="empresa" id="empresa"
                                   value="<?= e($empresa) ?>" maxlength="150"
                                   placeholder="Ex: Reptec Ferramentas"
                                   required autofocus>
                        </div>

                        <div class="campo">
                            <label for="cnpj">CNPJ</label>
                            <input type="text" name="cnpj" id="cnpj"
                                   value="<?= e($cnpj) ?>" maxlength="18"
                                   placeholder="00.000.000/0001-00">
                            <span class="dica">Opcional. Com ou sem pontuação.</span>
                        </div>

                    </div>

                    <p class="nav-rotulo" style="padding:18px 0 8px">
                        Usuário administrador
                    </p>

                    <div class="form-grade">

                        <div class="campo">
                            <label for="responsavel">Nome do responsável *</label>
                            <input type="text" name="responsavel" id="responsavel"
                                   value="<?= e($responsavel) ?>" maxlength="150"
                                   placeholder="Ex: Maria Silva" required>
                        </div>

                        <div class="campo">
                            <label for="email">E-mail *</label>
                            <input type="email" name="email" id="email"
                                   value="<?= e($email) ?>" maxlength="150"
                                   placeholder="voce@empresa.com"
                                   autocomplete="username" required>
                            <span class="dica">Será usado para entrar no sistema.</span>
                        </div>

                        <div class="campo">
                            <label for="senha">Senha *</label>
                            <input type="password" name="senha" id="senha"
                                   placeholder="Mínimo de 6 caracteres"
                                   autocomplete="new-password" minlength="6" required>
                        </div>

                        <div class="campo">
                            <label for="confirmar">Confirmar senha *</label>
                            <input type="password" name="confirmar" id="confirmar"
                                   placeholder="Repita a senha"
                                   autocomplete="new-password" minlength="6" required>
                        </div>

                    </div>

                    <button type="submit" class="btn btn-primario acesso-botao">
                        <i class="fa-solid fa-check"></i>
                        Cadastrar empresa
                    </button>

                </form>

            </div>

            <div class="acesso-rodape">
                Já tem conta?
                <a href="login.php">Entrar</a>
            </div>

        </div>

        <p class="acesso-nota">
            © <?= date("Y") ?> StockSense — Trabalho de Conclusão de Curso
        </p>

    </main>

</body>

</html>
