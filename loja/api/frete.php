<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: api/frete.php
 * Endpoint para cálculo oficial de frete com regras regionais e frete grátis
 * ==============================================================================
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$dadosBrutos = file_get_contents('php://input');
$dados = json_decode($dadosBrutos, true);

if (!is_array($dados)) {
    $dados = $_POST;
}

$cep = limparNumeros($dados['cep'] ?? '');
$uf  = strtoupper(trim($dados['uf'] ?? ''));

$resposta = [
    'sucesso'         => false,
    'mensagem'        => '',
    'valor'           => 0.00,
    'valor_formatado' => 'R$ 0,00',
    'prazo'           => '',
    'endereco'        => '',
    'cidade'          => '',
    'uf'              => '',
    'frete_gratis'    => false,
    'subtotal'        => 0.00,
    'total'           => 0.00
];

if (strlen($cep) !== 8) {
    $resposta['mensagem'] = 'CEP inválido. O CEP deve conter exatamente 8 números.';
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
    exit;
}

// Se a UF não foi enviada diretamente pelo cliente, consulta o ViaCEP pelo backend
if (empty($uf)) {
    $urlViaCep = "https://viacep.com.br/ws/{$cep}/json/";
    $contexto = stream_context_create([
        'http' => [
            'timeout' => 4, // 4 segundos de timeout
            'ignore_errors' => true
        ]
    ]);

    $jsonResposta = @file_get_contents($urlViaCep, false, $contexto);
    if ($jsonResposta) {
        $dadosViaCep = json_decode($jsonResposta, true);
        if (!empty($dadosViaCep) && empty($dadosViaCep['erro'])) {
            $uf = strtoupper($dadosViaCep['uf'] ?? '');
            $resposta['cidade']   = $dadosViaCep['localidade'] ?? '';
            $resposta['uf']       = $uf;
            $resposta['endereco'] = ($dadosViaCep['logradouro'] ?? '') . ' - ' . 
                                    ($dadosViaCep['bairro'] ?? '') . ', ' . 
                                    $resposta['cidade'] . '/' . $uf;
        }
    }
}

// Fallback se o ViaCEP não retornou dados no backend
if (empty($uf)) {
    $resposta['mensagem'] = 'Não foi possível identificar a região do CEP informado. Verifique o CEP digitado.';
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------------------------------------------------------------------
// CÁLCULO DO SUBTOTAL REAL DOS ITENS DO CARRINHO (NO BANCO DE DADOS)
// ------------------------------------------------------------------------------
$subtotalCarrinho = 0.00;
if (!empty($_SESSION['carrinho'])) {
    $ids = array_keys($_SESSION['carrinho']);
    $inQuery = implode(',', array_fill(0, count($ids), '?'));
    try {
        $stmt = $pdo->prepare("SELECT id, preco FROM produtos WHERE id IN ({$inQuery})");
        $stmt->execute($ids);
        $precos = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        foreach ($_SESSION['carrinho'] as $pId => $qtd) {
            if (isset($precos[$pId])) {
                $subtotalCarrinho += (float)$precos[$pId] * (int)$qtd;
            }
        }
    } catch (PDOException $e) {
        error_log("Erro ao calcular subtotal para frete: " . $e->getMessage());
    }
}

// ------------------------------------------------------------------------------
// REGRAS DE FRETE DA TECHWAVE STORE
// ------------------------------------------------------------------------------
$valorFrete = 35.00; // Padrão
$prazoDias = '5 a 9 dias úteis';

// 1. Regra de Frete Grátis acima ou igual a R$ 200,00
$freteGratis = ($subtotalCarrinho >= 200.00);

if ($freteGratis) {
    $valorFrete = 0.00;
    $prazoDias = '4 a 7 dias úteis (Envio Expresso Grátis)';
} else {
    // 2. Regra por Região / UF
    $mesmaUF = ['PA'];
    $norteNordeste = ['AM', 'AP', 'RR', 'TO', 'AC', 'RO', 'MA', 'PI', 'CE', 'RN', 'PB', 'PE', 'AL', 'SE', 'BA'];
    $centroOeste   = ['MT', 'MS', 'GO', 'DF'];
    $sudeste       = ['SP', 'RJ', 'MG', 'ES'];
    $sul           = ['PR', 'SC', 'RS'];

    if (in_array($uf, $mesmaUF)) {
        $valorFrete = 12.00;
        $prazoDias = '1 a 3 dias úteis';
    } elseif (in_array($uf, $norteNordeste)) {
        $valorFrete = 25.00;
        $prazoDias = '4 a 7 dias úteis';
    } elseif (in_array($uf, $centroOeste)) {
        $valorFrete = 30.00;
        $prazoDias = '5 a 8 dias úteis';
    } elseif (in_array($uf, $sudeste)) {
        $valorFrete = 35.00;
        $prazoDias = '6 a 10 dias úteis';
    } elseif (in_array($uf, $sul)) {
        $valorFrete = 40.00;
        $prazoDias = '7 a 12 dias úteis';
    }
}

// Salva os dados de frete validados na sessão do usuário
$_SESSION['frete_cep']      = $cep;
$_SESSION['frete_uf']       = $uf;
$_SESSION['frete_valor']    = $valorFrete;
$_SESSION['frete_prazo']    = $prazoDias;
if (!empty($resposta['endereco'])) {
    $_SESSION['frete_endereco'] = $resposta['endereco'];
}

$resposta['sucesso']         = true;
$resposta['valor']           = $valorFrete;
$resposta['valor_formatado'] = ($valorFrete == 0.00) ? 'FRETE GRÁTIS' : formatarPreco($valorFrete);
$resposta['prazo']           = $prazoDias;
$resposta['frete_gratis']    = $freteGratis;
$resposta['subtotal']        = $subtotalCarrinho;
$resposta['total']           = $subtotalCarrinho + $valorFrete;

echo json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
