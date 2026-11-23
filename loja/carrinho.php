<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: carrinho.php
 * Página do carrinho de compras, cálculo de frete por CEP e resumo de valores
 * ==============================================================================
 */

$titulo_pagina = 'Meu Carrinho de Compras';
require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';

// Busca os itens consolidados do carrinho diretamente no banco
$itensCarrinho = [];
$subtotalGeral = 0.0;

if (!empty($_SESSION['carrinho']) && is_array($_SESSION['carrinho'])) {
    $ids = array_keys($_SESSION['carrinho']);
    $inQuery = implode(',', array_fill(0, count($ids), '?'));

    try {
        $stmt = $pdo->prepare("SELECT id, nome, preco, estoque, imagem FROM produtos WHERE id IN ({$inQuery})");
        $stmt->execute($ids);
        $produtosDb = $stmt->fetchAll();

        foreach ($produtosDb as $p) {
            $qtd = $_SESSION['carrinho'][$p['id']] ?? 0;
            if ($qtd > 0) {
                // Sincroniza se a quantidade guardada for maior que o estoque atual
                if ($qtd > $p['estoque']) {
                    $qtd = $p['estoque'];
                    $_SESSION['carrinho'][$p['id']] = $qtd;
                }

                $subItem = (float)$p['preco'] * $qtd;
                $subtotalGeral += $subItem;

                $caminhoImg = 'assets/img/' . e($p['imagem']);
                if (!empty($p['imagem']) && file_exists(__DIR__ . '/uploads/' . $p['imagem'])) {
                    $caminhoImg = 'uploads/' . e($p['imagem']);
                }

                $itensCarrinho[] = [
                    'id'             => (int)$p['id'],
                    'nome'           => $p['nome'],
                    'preco'          => (float)$p['preco'],
                    'quantidade'     => $qtd,
                    'estoque'        => (int)$p['estoque'],
                    'subtotal'       => $subItem,
                    'imagem'         => $caminhoImg
                ];
            }
        }
    } catch (PDOException $e) {
        error_log("Erro ao carregar carrinho: " . $e->getMessage());
    }
}

// Frete gravado na sessão (ou valor zero se elegível a frete grátis >= 200)
$freteCalculado = isset($_SESSION['frete_valor']) ? (float)$_SESSION['frete_valor'] : null;
$cepCalculado   = $_SESSION['frete_cep'] ?? '';
$enderecoFrete  = $_SESSION['frete_endereco'] ?? '';

// Regra de Frete Grátis acima de R$ 200,00
if ($subtotalGeral >= 200.00 && $subtotalGeral > 0) {
    $freteCalculado = 0.00;
}

$totalCompra = $subtotalGeral + ($freteCalculado ?? 0.00);

require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 1.85rem; font-weight: 800;">Carrinho de Compras</h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">Confira seus produtos, calcule o prazo de entrega e finalize seu pedido.</p>
</div>

<?php if (empty($itensCarrinho)): ?>
    <!-- Estado de Carrinho Vazio -->
    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 4rem 2rem; text-align: center;">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1rem;">
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <h2 style="font-size: 1.35rem; font-weight: 700; margin-bottom: 0.5rem;">Seu carrinho está vazio</h2>
        <p style="color: var(--text-muted); max-width: 420px; margin: 0 auto 1.75rem;">
            Você ainda não adicionou nenhum item. Aproveite as nossas ofertas de eletrônicos e computadores!
        </p>
        <a href="index.php" class="btn btn-primary" style="padding: 0.85rem 2rem;">Explorar Produtos</a>
    </div>
<?php else: ?>
    <!-- Layout do Carrinho com Itens -->
    <div class="cart-layout">
        <!-- Tabela de Itens -->
        <div>
            <table class="cart-table" id="tabela-carrinho">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th style="width: 130px; text-align: right;">Preço</th>
                        <th style="width: 140px; text-align: center;">Qtd</th>
                        <th style="width: 130px; text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itensCarrinho as $item): ?>
                        <tr data-id="<?= (int)$item['id'] ?>">
                            <td>
                                <div class="cart-item-info">
                                    <img src="<?= $item['imagem'] ?>" alt="<?= e($item['nome']) ?>" class="cart-item-img">
                                    <div>
                                        <a href="produto.php?id=<?= (int)$item['id'] ?>" class="cart-item-name">
                                            <?= e($item['nome']) ?>
                                        </a>
                                        <div>
                                            <button type="button" class="cart-btn-remove btn-cart-remove">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                                Remover
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align: right; font-weight: 500;">
                                <?= formatarPreco($item['preco']) ?>
                            </td>
                            <td style="text-align: center;">
                                <div class="quantity-control" style="transform: scale(0.9);">
                                    <button type="button" class="quantity-btn btn-cart-minus" aria-label="Diminuir">-</button>
                                    <input type="text" class="quantity-input input-cart-qtd" value="<?= (int)$item['quantidade'] ?>" readonly>
                                    <button type="button" class="quantity-btn btn-cart-plus" aria-label="Aumentar" <?= $item['quantidade'] >= $item['estoque'] ? 'disabled' : '' ?>>+</button>
                                </div>
                            </td>
                            <td style="text-align: right; font-weight: 700; color: var(--text-main);">
                                <?= formatarPreco($item['subtotal']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.25rem;">
                <a href="index.php" class="btn btn-outline">&larr; Adicionar Mais Produtos</a>
                <button type="button" id="btn-limpar-carrinho" class="btn btn-outline" style="color: var(--danger); border-color: #fecaca;">
                    Esvaziar Carrinho
                </button>
            </div>
        </div>

        <!-- Card Lateral de Resumo Financeiro e Frete -->
        <div class="cart-summary-card">
            <h2 class="summary-title">Resumo do Pedido</h2>

            <div class="summary-line">
                <span>Subtotal dos Produtos</span>
                <strong><?= formatarPreco($subtotalGeral) ?></strong>
            </div>

            <!-- Bloco de Cálculo de Frete -->
            <div class="cep-calc-box">
                <label for="cep_calculo" style="font-size: 0.85rem; font-weight: 600; color: var(--text-main);">
                    Calcular Frete de Entrega:
                </label>
                <div class="cep-form-row">
                    <input type="text" 
                           id="cep_calculo" 
                           class="cep-input" 
                           placeholder="00000-000" 
                           value="<?= e(formatarCep($cepCalculado)) ?>" 
                           maxlength="9">
                    <button type="button" id="btn-calcular-frete" class="btn btn-primary" style="padding: 0.5rem 0.9rem;">
                        Calcular
                    </button>
                </div>
                <div id="resultado-frete" class="cep-result" style="<?= !empty($enderecoFrete) ? '' : 'display: none;' ?>">
                    <?php if (!empty($enderecoFrete)): ?>
                        <span>Destino: <strong><?= e($enderecoFrete) ?></strong></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="summary-line">
                <span>Frete:</span>
                <span id="valor-frete-display" style="font-weight: 600;">
                    <?php if ($freteCalculado === 0.00): ?>
                        <span style="color: var(--success); font-weight: 700;">FRETE GRÁTIS</span>
                    <?php elseif ($freteCalculado !== null): ?>
                        <?= formatarPreco($freteCalculado) ?>
                    <?php else: ?>
                        <span style="color: var(--text-muted); font-size: 0.85rem;">Não calculado</span>
                    <?php endif; ?>
                </span>
            </div>

            <div class="summary-line total">
                <span>Total:</span>
                <span id="valor-total-display"><?= formatarPreco($totalCompra) ?></span>
            </div>

            <div style="margin-top: 1.5rem;">
                <a href="checkout.php" class="btn btn-success btn-block" style="padding: 1rem; font-size: 1.1rem; box-shadow: var(--shadow-md);">
                    Fechar Pedido &rarr;
                </a>
            </div>

            <div style="margin-top: 1rem; text-align: center; font-size: 0.75rem; color: var(--text-muted);">
                Ambiente Seguro de Pagamento com Criptografia SSL
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
