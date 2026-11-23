<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: api/carrinho.php
 * Endpoint REST/JSON para manipulação assíncrona do carrinho de compras
 * ==============================================================================
 */

// Define resposta estritamente no formato JSON
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

// Inicializa a estrutura do carrinho na sessão se não existir
if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Resposta padrão
$resposta = [
    'sucesso'   => false,
    'mensagem'  => '',
    'total_itens' => contarItensCarrinho(),
    'subtotal'  => 0.0,
    'itens'     => []
];

// Lê o corpo da requisição JSON ou dados de formulário POST
$dadosBrutos = file_get_contents('php://input');
$dados = json_decode($dadosBrutos, true);

if (!is_array($dados)) {
    $dados = $_POST;
}

$acao = $dados['acao'] ?? $_GET['acao'] ?? '';

try {
    switch ($acao) {
        // ----------------------------------------------------------------------
        // 1. ADICIONAR PRODUTO AO CARRINHO
        // ----------------------------------------------------------------------
        case 'adicionar':
            $produto_id = (int)($dados['produto_id'] ?? 0);
            $quantidade = max(1, (int)($dados['quantidade'] ?? 1));

            if ($produto_id <= 0) {
                $resposta['mensagem'] = 'Produto inválido.';
                break;
            }

            // Busca preço e estoque reais do banco de dados (nunca confia no cliente)
            $stmt = $pdo->prepare("SELECT id, nome, preco, estoque, ativo FROM produtos WHERE id = ?");
            $stmt->execute([$produto_id]);
            $produto = $stmt->fetch();

            if (!$produto || !$produto['ativo']) {
                $resposta['mensagem'] = 'Este produto não está mais disponível.';
                break;
            }

            $qtdAtualNoCarrinho = $_SESSION['carrinho'][$produto_id] ?? 0;
            $novaQuantidade = $qtdAtualNoCarrinho + $quantidade;

            // Valida se a quantidade solicitada excede o estoque físico
            if ($novaQuantidade > $produto['estoque']) {
                $resposta['mensagem'] = "Não é possível adicionar. Temos apenas {$produto['estoque']} unidade(s) em estoque.";
                break;
            }

            $_SESSION['carrinho'][$produto_id] = $novaQuantidade;
            $resposta['sucesso']  = true;
            $resposta['mensagem'] = "{$produto['nome']} adicionado ao carrinho com sucesso!";
            break;

        // ----------------------------------------------------------------------
        // 2. ATUALIZAR QUANTIDADE DE UM ITEM NO CARRINHO
        // ----------------------------------------------------------------------
        case 'atualizar':
            $produto_id = (int)($dados['produto_id'] ?? 0);
            $quantidade = (int)($dados['quantidade'] ?? 0);

            if ($produto_id <= 0) {
                $resposta['mensagem'] = 'Produto inválido.';
                break;
            }

            if ($quantidade <= 0) {
                // Se quantidade for 0 ou negativa, remove do carrinho
                unset($_SESSION['carrinho'][$produto_id]);
                $resposta['sucesso']  = true;
                $resposta['mensagem'] = 'Item removido do carrinho.';
            } else {
                // Valida estoque no banco
                $stmt = $pdo->prepare("SELECT nome, estoque FROM produtos WHERE id = ? AND ativo = 1");
                $stmt->execute([$produto_id]);
                $produto = $stmt->fetch();

                if (!$produto) {
                    unset($_SESSION['carrinho'][$produto_id]);
                    $resposta['mensagem'] = 'Produto não encontrado.';
                    break;
                }

                if ($quantidade > $produto['estoque']) {
                    $resposta['mensagem'] = "Estoque insuficiente. Apenas {$produto['estoque']} unidade(s) disponíveis.";
                    $_SESSION['carrinho'][$produto_id] = $produto['estoque'];
                } else {
                    $_SESSION['carrinho'][$produto_id] = $quantidade;
                    $resposta['sucesso']  = true;
                    $resposta['mensagem'] = 'Quantidade atualizada.';
                }
            }
            break;

        // ----------------------------------------------------------------------
        // 3. REMOVER UM ITEM ESPECÍFICO DO CARRINHO
        // ----------------------------------------------------------------------
        case 'remover':
            $produto_id = (int)($dados['produto_id'] ?? 0);
            if (isset($_SESSION['carrinho'][$produto_id])) {
                unset($_SESSION['carrinho'][$produto_id]);
                $resposta['sucesso']  = true;
                $resposta['mensagem'] = 'Produto removido do carrinho.';
            } else {
                $resposta['mensagem'] = 'O item não estava no carrinho.';
            }
            break;

        // ----------------------------------------------------------------------
        // 4. ESVAZIAR COMPLETAMENTE O CARRINHO
        // ----------------------------------------------------------------------
        case 'limpar':
            $_SESSION['carrinho'] = [];
            unset($_SESSION['frete_valor'], $_SESSION['frete_cep'], $_SESSION['frete_endereco']);
            $resposta['sucesso']  = true;
            $resposta['mensagem'] = 'Carrinho esvaziado.';
            break;

        // ----------------------------------------------------------------------
        // 5. LISTAR CONTEÚDO DO CARRINHO
        // ----------------------------------------------------------------------
        case 'listar':
        default:
            $resposta['sucesso'] = true;
            break;
    }

    // Calcula os detalhes consolidados atuais do carrinho consultando o banco
    $itensConsolidados = [];
    $subtotalGeral = 0.0;

    if (!empty($_SESSION['carrinho'])) {
        $ids = array_keys($_SESSION['carrinho']);
        $inQuery = implode(',', array_fill(0, count($ids), '?'));

        $stmtItens = $pdo->prepare("
            SELECT id, nome, preco, estoque, imagem, ativo 
            FROM produtos 
            WHERE id IN ({$inQuery})
        ");
        $stmtItens->execute($ids);
        $produtosDb = $stmtItens->fetchAll();

        foreach ($produtosDb as $p) {
            $qtd = $_SESSION['carrinho'][$p['id']] ?? 0;
            if ($qtd > 0) {
                // Ajusta se o estoque tiver sido reduzido após a adição
                if ($qtd > $p['estoque']) {
                    $qtd = $p['estoque'];
                    $_SESSION['carrinho'][$p['id']] = $qtd;
                }

                $subItem = (float)$p['preco'] * $qtd;
                $subtotalGeral += $subItem;

                $caminhoImg = 'assets/img/' . $p['imagem'];
                if (!empty($p['imagem']) && file_exists(__DIR__ . '/../uploads/' . $p['imagem'])) {
                    $caminhoImg = 'uploads/' . $p['imagem'];
                }

                $itensConsolidados[] = [
                    'id'             => (int)$p['id'],
                    'nome'           => $p['nome'],
                    'preco_unitario' => (float)$p['preco'],
                    'preco_formatado'=> formatarPreco($p['preco']),
                    'quantidade'     => $qtd,
                    'estoque'        => (int)$p['estoque'],
                    'subtotal'       => $subItem,
                    'subtotal_formatado' => formatarPreco($subItem),
                    'imagem'         => $caminhoImg
                ];
            }
        }
    }

    $resposta['total_itens'] = contarItensCarrinho();
    $resposta['subtotal']    = $subtotalGeral;
    $resposta['subtotal_formatado'] = formatarPreco($subtotalGeral);
    $resposta['itens']       = $itensConsolidados;

} catch (Exception $e) {
    error_log("Erro na API de carrinho: " . $e->getMessage());
    $resposta['sucesso'] = false;
    $resposta['mensagem'] = 'Erro ao processar o carrinho no servidor.';
}

echo json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
