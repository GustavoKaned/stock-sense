<?php

/*
|--------------------------------------------------------------------------
| HELPERS DE APRESENTAÇÃO
|--------------------------------------------------------------------------
| Pequenas funções usadas nas views para evitar repetição de código.
*/

/**
 * Escapa texto para saída segura em HTML.
 */
function e($texto)
{
    return htmlspecialchars($texto ?? "", ENT_QUOTES, "UTF-8");
}

/**
 * Formata um número no padrão brasileiro.
 */
function num($valor, $casas = 2)
{
    return number_format((float) $valor, $casas, ",", ".");
}

/** Texto natural para contagens, inclusive zero. */
function quantidade($valor, $singular, $plural)
{
    return num($valor, 0) . ' ' . ((int) $valor === 1 ? $singular : $plural);
}

/**
 * Formata um volume em metros cúbicos.
 */
function m3($valor)
{
    return num($valor, 2) . " m³";
}

/**
 * Formata uma data vinda do banco (Y-m-d) para d/m/Y.
 */
function data_br($data, $vazio = "—")
{
    if (empty($data)) {
        return $vazio;
    }

    return date("d/m/Y", strtotime($data));
}

/**
 * Garante que um percentual fique sempre entre 0 e 100.
 */
function pct($valor)
{
    return max(0, min(100, (float) $valor));
}

/**
 * Classifica um percentual de ocupação.
 *
 * Centraliza as faixas (livre / parcial / atenção / cheia) que antes
 * estavam repetidas no dashboard e nos detalhes da prateleira.
 *
 * @return array{nivel:string, rotulo:string}
 */
function status_ocupacao($percentual)
{
    $percentual = (float) $percentual;

    if ($percentual >= 100) {
        return ["nivel" => "cheia", "rotulo" => "Cheia"];
    }

    if ($percentual >= 80) {
        return ["nivel" => "atencao", "rotulo" => "Quase cheia"];
    }

    if ($percentual > 0) {
        return ["nivel" => "parcial", "rotulo" => "Ocupada"];
    }

    return ["nivel" => "livre", "rotulo" => "Livre"];
}


/*
|--------------------------------------------------------------------------
| VALIDAÇÃO
|--------------------------------------------------------------------------
*/

/**
 * Valida um CNPJ (apenas dígitos, 14 caracteres) pelo algoritmo oficial
 * dos dígitos verificadores da Receita Federal.
 */
function cnpjValido($cnpj)
{
    if (strlen($cnpj) !== 14 || !ctype_digit($cnpj)) {
        return false;
    }

    // Sequências com todos os dígitos iguais (ex: 00000000000000) passam
    // no cálculo dos dígitos verificadores, mas não são CNPJs válidos.
    if (preg_match('/^(\d)\1{13}$/', $cnpj) === 1) {
        return false;
    }

    $digitoVerificador = function ($base, $pesos) {
        $soma = 0;

        foreach ($pesos as $i => $peso) {
            $soma += (int) $base[$i] * $peso;
        }

        $resto = $soma % 11;

        return $resto < 2 ? 0 : 11 - $resto;
    };

    $base = substr($cnpj, 0, 12);

    $digito1 = $digitoVerificador($base, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
    $digito2 = $digitoVerificador($base . $digito1, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

    return $cnpj === $base . $digito1 . $digito2;
}

/**
 * Já existe uma empresa cadastrada com esse CNPJ (só dígitos)?
 */
function cnpjJaCadastrado($conexao, $cnpjNumeros)
{
    $stmt = $conexao->prepare("SELECT id FROM empresas WHERE cnpj = ?");
    $stmt->bind_param("s", $cnpjNumeros);
    $stmt->execute();

    return $stmt->get_result()->num_rows > 0;
}


/*
|--------------------------------------------------------------------------
| REGRA DE NEGÓCIO
|--------------------------------------------------------------------------
*/

/**
 * Recalcula e salva o percentual de ocupação de uma prateleira.
 */
function atualizarPercentualPrateleira($conexao, $prateleira_id)
{
    $sql = "SELECT metros_cubicos
            FROM prateleiras
            WHERE id = ?";

    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $prateleira_id);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        return false;
    }

    $prateleira = $resultado->fetch_assoc();
    $capacidade = (float) $prateleira["metros_cubicos"];

    $sql = "SELECT COALESCE(SUM(volume_m3), 0) AS total
            FROM ocupacoes
            WHERE prateleira_id = ?
            AND data_saida IS NULL";

    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $prateleira_id);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $dados = $resultado->fetch_assoc();

    $volume_ocupado = (float) $dados["total"];

    $percentual = 0;

    if ($capacidade > 0) {
        $percentual = ($volume_ocupado / $capacidade) * 100;
    }

    $percentual = round(pct($percentual), 2);

    $sql = "UPDATE prateleiras
            SET pct_ocupado = ?
            WHERE id = ?";

    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("di", $percentual, $prateleira_id);
    $stmt->execute();

    return $percentual;
}

/**
 * Soma a capacidade (m³) de todas as prateleiras de um galpão.
 *
 * Usado para impedir que o total cadastrado em prateleiras ultrapasse
 * a capacidade declarada do galpão (mesmo cálculo usado em
 * detalhes_galpao.php no card "Em prateleiras").
 *
 * @param int|null $ignorar_prateleira_id Exclui essa prateleira da soma.
 *        Necessário ao editar uma prateleira existente: sem isso, o
 *        valor antigo dela (ainda salvo no banco) seria somado junto
 *        com o valor novo do formulário, contando a mesma prateleira
 *        duas vezes.
 */
function capacidadeOcupadaGalpao($conexao, $galpao_id, $ignorar_prateleira_id = null)
{
    $sql = "SELECT COALESCE(SUM(metros_cubicos), 0) AS total
            FROM prateleiras
            WHERE galpao_id = ?";

    if ($ignorar_prateleira_id !== null) {
        $sql .= " AND id != ?";
    }

    $stmt = $conexao->prepare($sql);

    if ($ignorar_prateleira_id !== null) {
        $stmt->bind_param("ii", $galpao_id, $ignorar_prateleira_id);
    } else {
        $stmt->bind_param("i", $galpao_id);
    }

    $stmt->execute();

    return (float) $stmt->get_result()->fetch_assoc()["total"];
}
