<?php
require_once __DIR__ . "/resposta.php";

/*
|--------------------------------------------------------------------------
| AUTENTICAÇÃO E ISOLAMENTO POR EMPRESA
|--------------------------------------------------------------------------
| Toda página protegida chama exigir_login(). A partir daí, empresa_id()
| devolve a empresa do usuário da sessão, e TODA consulta ao banco precisa
| filtrar por esse valor — é isso que impede uma empresa de ver o estoque
| da outra.
*/

if (session_status() === PHP_SESSION_NONE) {

    // Cookie de sessão mais restrito (não acessível por JavaScript).
    session_set_cookie_params([
        "httponly" => true,
        "samesite" => "Lax",
    ]);

    session_start();
}


/**
 * Há um usuário autenticado na sessão?
 */
function esta_logado()
{
    return isset($_SESSION["usuario_id"], $_SESSION["empresa_id"]);
}

/**
 * ID da empresa do usuário logado.
 * É a chave usada para filtrar todas as consultas.
 */
function empresa_id()
{
    return (int) ($_SESSION["empresa_id"] ?? 0);
}

function usuario_id()
{
    return (int) ($_SESSION["usuario_id"] ?? 0);
}

function usuario_nome()
{
    return $_SESSION["usuario_nome"] ?? "";
}

function empresa_nome()
{
    return $_SESSION["empresa_nome"] ?? "";
}

/**
 * Bloqueia o acesso de quem não está logado.
 * Deve ser a primeira coisa executada em páginas protegidas.
 *
 * @param string $base Prefixo para chegar à raiz do projeto
 *                     ("" na raiz, "../" dentro de actions/).
 */
function exigir_login($base = "")
{
    if (!esta_logado()) {
        if (pedido_json()) responder_json(false, "Sua sessão expirou. Entre novamente para continuar.", $base . "login.php", 401);
        header("Location: " . $base . "login.php");
        exit;
    }
}

/**
 * Cria a sessão do usuário após um login válido.
 */
function iniciar_sessao_usuario(array $usuario)
{
    // Evita fixação de sessão: o ID muda no momento do login.
    session_regenerate_id(true);

    $_SESSION["usuario_id"]    = (int) $usuario["id"];
    $_SESSION["usuario_nome"]  = $usuario["nome"];
    $_SESSION["empresa_id"]    = (int) $usuario["empresa_id"];
    $_SESSION["empresa_nome"]  = $usuario["empresa_nome"];
    $_SESSION["usuario_cargo"] = $usuario["cargo"] ?? "";
}

/**
 * Encerra a sessão.
 */
function encerrar_sessao()
{
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $p["path"],
            $p["domain"],
            $p["secure"],
            $p["httponly"]
        );
    }

    session_destroy();
}


/*
|--------------------------------------------------------------------------
| VERIFICAÇÃO DE PROPRIEDADE
|--------------------------------------------------------------------------
| Não basta filtrar as listagens: sem estas checagens, bastaria trocar o
| ?id= na URL para editar ou excluir o registro de outra empresa.
*/

/**
 * O galpão pertence à empresa logada?
 */
function galpao_da_empresa($conexao, $galpao_id)
{
    $sql = "SELECT id FROM galpoes WHERE id = ? AND empresa_id = ?";

    $stmt = $conexao->prepare($sql);
    $empresa = empresa_id();
    $stmt->bind_param("ii", $galpao_id, $empresa);
    $stmt->execute();

    return $stmt->get_result()->num_rows > 0;
}

/**
 * A prateleira pertence à empresa logada?
 */
function prateleira_da_empresa($conexao, $prateleira_id)
{
    $sql = "SELECT p.id
            FROM prateleiras p
            INNER JOIN galpoes g ON g.id = p.galpao_id
            WHERE p.id = ? AND g.empresa_id = ?";

    $stmt = $conexao->prepare($sql);
    $empresa = empresa_id();
    $stmt->bind_param("ii", $prateleira_id, $empresa);
    $stmt->execute();

    return $stmt->get_result()->num_rows > 0;
}

/**
 * A ocupação pertence à empresa logada?
 */
function ocupacao_da_empresa($conexao, $ocupacao_id)
{
    $sql = "SELECT o.id
            FROM ocupacoes o
            INNER JOIN prateleiras p ON p.id = o.prateleira_id
            INNER JOIN galpoes g ON g.id = p.galpao_id
            WHERE o.id = ? AND g.empresa_id = ?";

    $stmt = $conexao->prepare($sql);
    $empresa = empresa_id();
    $stmt->bind_param("ii", $ocupacao_id, $empresa);
    $stmt->execute();

    return $stmt->get_result()->num_rows > 0;
}

/**
 * Interrompe a execução quando o registro não é da empresa logada.
 */
function exigir_propriedade($condicao, $base = "", $rota = "dashboard")
{
    if (!$condicao) {
        if (pedido_json()) responder_json(false, "Registro não encontrado ou acesso não permitido.", null, 403);
        header("Location: " . $base . "index.php?pagina=" . $rota . "&erro=acesso");
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| MENSAGEM DE ERRO ENTRE PÁGINAS (FLASH)
|--------------------------------------------------------------------------
| As actions rodam fora do layout (sem sidebar, sem CSS). Antes, um erro
| de validação usava die() e jogava o usuário numa página em branco.
| Com isso, a action guarda o erro e os dados do formulário na sessão e
| redireciona de volta para o formulário, que exibe tudo normalmente.
*/

/**
 * Guarda uma mensagem de erro e os dados enviados na sessão e
 * redireciona de volta para a URL informada.
 */
function voltar_com_erro($url, $mensagem)
{
    if (pedido_json()) responder_json(false, $mensagem, null, 422);
    $_SESSION["erro_formulario"] = $mensagem;
    $_SESSION["dados_formulario"] = $_POST;

    header("Location: " . $url);
    exit;
}

/**
 * Recupera (e apaga) o erro e os dados do último formulário que
 * falhou, para a página de cadastro/edição repopular os campos.
 *
 * @return array{erro:?string, dados:array}
 */
function pegar_erro_formulario()
{
    $erro = $_SESSION["erro_formulario"] ?? null;
    $dados = $_SESSION["dados_formulario"] ?? [];

    unset($_SESSION["erro_formulario"], $_SESSION["dados_formulario"]);

    return ["erro" => $erro, "dados" => $dados];
}

/**
 * Guarda uma mensagem simples para uma pagina de destino, como listagens
 * apos uma exclusao ou uma acao por GET.
 */
function redirecionar_com_erro($url, $mensagem)
{
    if (pedido_json()) responder_json(false, $mensagem, null, 422);
    $_SESSION["flash_erro"] = $mensagem;
    header("Location: " . $url);
    exit;
}

/**
 * Recupera e apaga a mensagem simples de erro.
 */
function pegar_flash_erro()
{
    $erro = $_SESSION["flash_erro"] ?? null;
    unset($_SESSION["flash_erro"]);
    return $erro;
}
