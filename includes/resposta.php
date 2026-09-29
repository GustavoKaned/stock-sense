<?php

function pedido_json()
{
    return strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
}

function responder_json($sucesso, $mensagem, $url = null, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['sucesso' => $sucesso, 'mensagem' => $mensagem, 'url' => $url], JSON_UNESCAPED_UNICODE);
    exit;
}

function concluir_acao($url, $mensagem)
{
    if (pedido_json()) responder_json(true, $mensagem, $url);
    $_SESSION['flash_sucesso'] = $mensagem;
    header('Location: ' . $url);
    exit;
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function exigir_post_seguro()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        if (pedido_json()) responder_json(false, 'Esta ação exige um formulário.', null, 405);
        http_response_code(405);
        exit('Esta ação exige um formulário. Volte à página anterior.');
    }
    if (!is_string($_POST['csrf_token'] ?? null) || !hash_equals(csrf_token(), $_POST['csrf_token'])) {
        if (pedido_json()) responder_json(false, 'Sua sessão mudou. Atualize a página antes de continuar.', null, 403);
        redirecionar_com_erro('../index.php', 'Sua sessão mudou. Tente novamente.');
    }
    set_exception_handler(function ($erro) {
        error_log('StockSense action: ' . $erro->getMessage());
        $mensagem = 'Não foi possível concluir a ação. Verifique os dados e as dependências do registro.';
        if (pedido_json()) responder_json(false, $mensagem, null, 500);
        $origem = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $formularios = [
            'salvar_galpao.php' => 'cadastrar_galpao',
            'salvar_prateleira.php' => 'cadastrar_prateleira',
            'cadastrar_ocupacao.php' => 'cadastrar_ocupacao',
            'atualizar_galpao.php' => 'editar_galpao',
            'atualizar_prateleira.php' => 'editar_prateleira',
            'atualizar_ocupacao.php' => 'editar_ocupacao',
        ];
        if (isset($formularios[$origem])) {
            voltar_com_erro('../index.php?pagina=' . $formularios[$origem] . '&id=' . (int) ($_POST['id'] ?? 0), $mensagem);
        }
        redirecionar_com_erro('../index.php', $mensagem);
    });
}
