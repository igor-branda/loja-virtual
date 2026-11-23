<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: produto.php
 * Página de detalhes do produto, especificações, seletor de quantidade e frete
 * ==============================================================================
 */

require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';

$produto_id = (int)($_GET['id'] ?? 0);

if ($produto_id <= 0) {
    setMensagem('erro', 'Produto inválido.');
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.nome AS categoria_nome 
        FROM produtos p
        JOIN categorias c ON c.id = p.categoria_id
        WHERE p.id = ? AND p.ativo = 1
        LIMIT 1
    ");
    $stmt->execute([$produto_id]);
    $produto = $stmt->fetch();

    if (!$produto) {
        setMensagem('erro', 'O produto solicitado não foi encontrado ou está inativo.');
        header('Location: index.php');
        exit;
    }

} catch (PDOException $e) {
    error_log("Erro ao carregar detalhes do produto: " . $e->getMessage());
    setMensagem('erro', 'Falha ao buscar informações do produto.');
    header('Location: index.php');
    exit;
}

$titulo_pagina = $produto['nome'];
require_once __DIR__ . '/includes/header.php';

// Caminho da imagem
$caminhoImg = 'assets/img/' . e($produto['imagem']);
if (!empty($produto['imagem']) && file_exists(__DIR__ . '/uploads/' . $produto['imagem'])) {
    $caminhoImg = 'uploads/' . e($produto['imagem']);
}

$estaEsgotado = ($produto['estoque'] <= 0);
$precoParcelado = $produto['preco'] / 10;
?>

<!-- Breadcrumbs de Navegação -->
<nav style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;" aria-label="Navegação estrutural">
    <a href="index.php">Início</a> &gt; 
    <a href="index.php?categoria=<?= (int)$produto['categoria_id'] ?>"><?= e($produto['categoria_nome']) ?></a> &gt; 
    <span style="color: var(--text-main); font-weight: 600;"><?= e($produto['nome']) ?></span>
</nav>

<div class="product-detail-layout">
    <!-- Coluna 1: Imagem Grande do Produto -->
    <div class="product-detail-image">
        <img src="<?= $caminhoImg ?>" alt="<?= e($produto['nome']) ?>">
    </div>

    <!-- Coluna 2: Informações Comerciais e Compra -->
    <div class="product-detail-info">
        <span class="product-category-label"><?= e($produto['categoria_nome']) ?> &bull; Cód. #<?= (int)$produto['id'] ?></span>
        <h1 class="product-detail-title"><?= e($produto['nome']) ?></h1>

        <!-- Status do Estoque -->
        <div class="product-detail-stock">
            <?php if ($estaEsgotado): ?>
                <span style="color: var(--danger); font-weight: 700; background: var(--danger-bg); padding: 4px 10px; border-radius: var(--radius-full);">
                    Produto Esgotado no momento
                </span>
            <?php elseif ($produto['estoque'] <= 5): ?>
                <span style="color: #92400e; font-weight: 700; background: var(--warning-bg); padding: 4px 10px; border-radius: var(--radius-full);">
                    Restam apenas <?= (int)$produto['estoque'] ?> unidades em estoque!
                </span>
            <?php else: ?>
                <span style="color: #065f46; font-weight: 600; background: var(--success-bg); padding: 4px 10px; border-radius: var(--radius-full);">
                    Em Estoque (<?= (int)$produto['estoque'] ?> unidades disponíveis)
                </span>
            <?php endif; ?>
        </div>

        <!-- Preço e Parcelamento -->
        <div style="margin: 1.25rem 0;">
            <div class="product-detail-price"><?= formatarPreco($produto['preco']) ?></div>
            <div style="font-size: 0.95rem; color: var(--text-muted);">
                ou em até <strong>10x de <?= formatarPreco($precoParcelado) ?></strong> sem juros no cartão
            </div>
            <div style="font-size: 0.85rem; color: var(--success); font-weight: 600; margin-top: 0.25rem;">
                5% de desconto à vista via Pix
            </div>
        </div>

        <!-- Seletor de Quantidade e Botão de Compra -->
        <?php if (!$estaEsgotado): ?>
            <div style="display: flex; align-items: center; gap: 1rem; margin: 1.5rem 0;">
                <label for="qtd-produto" style="font-weight: 600; font-size: 0.9rem;">Quantidade:</label>
                <div class="quantity-control">
                    <button type="button" class="quantity-btn btn-diminuir-qtd" aria-label="Diminuir">-</button>
                    <input type="number" 
                           id="qtd-produto" 
                           class="quantity-input" 
                           value="1" 
                           min="1" 
                           max="<?= (int)$produto['estoque'] ?>" 
                           readonly>
                    <button type="button" class="quantity-btn btn-aumentar-qtd" aria-label="Aumentar">+</button>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                <button type="button" 
                        class="btn btn-primary btn-block btn-adicionar-carrinho" 
                        data-id="<?= (int)$produto['id'] ?>"
                        data-qtd-input="#qtd-produto"
                        style="padding: 1rem; font-size: 1.1rem;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    Adicionar ao Carrinho
                </button>
            </div>
        <?php else: ?>
            <div style="margin: 2rem 0;">
                <button type="button" class="btn btn-block" disabled style="padding: 1rem; font-size: 1.1rem; background: #cbd5e1; color: #64748b; cursor: not-allowed;">
                    Produto Indisponível
                </button>
            </div>
        <?php endif; ?>

        <!-- Caixa de Cálculo de Frete Rápido -->
        <div class="cep-calc-box">
            <strong style="font-size: 0.9rem; display: flex; align-items: center; gap: 0.4rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="3" width="15" height="13"></rect>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                    <circle cx="5.5" cy="18.5" r="2.5"></circle>
                    <circle cx="18.5" cy="18.5" r="2.5"></circle>
                </svg>
                Calcular Frete e Prazo de Entrega:
            </strong>
            <div class="cep-form-row">
                <input type="text" id="cep_calculo" class="cep-input" placeholder="00000-000" maxlength="9">
                <button type="button" id="btn-calcular-frete-produto" class="btn btn-outline" style="padding: 0.5rem 1rem;">
                    Calcular
                </button>
            </div>
            <div id="resultado-frete-produto" class="cep-result" style="display: none;"></div>
        </div>

        <!-- Descrição Técnica -->
        <div class="product-detail-desc">
            <h3 style="font-size: 1.15rem; color: var(--text-main); margin-bottom: 0.75rem;">Descrição e Especificações:</h3>
            <p style="white-space: pre-line;"><?= e($produto['descricao']) ?></p>
        </div>
    </div>
</div>

<script>
    // Controle dos botões + e - de quantidade na página do produto
    document.addEventListener('DOMContentLoaded', () => {
        const inputQtd = document.querySelector('#qtd-produto');
        const btnMais = document.querySelector('.btn-aumentar-qtd');
        const btnMenos = document.querySelector('.btn-diminuir-qtd');

        if (inputQtd && btnMais && btnMenos) {
            const maxEstoque = parseInt(inputQtd.getAttribute('max'), 10) || 1;

            btnMais.addEventListener('click', () => {
                let atual = parseInt(inputQtd.value, 10) || 1;
                if (atual < maxEstoque) {
                    inputQtd.value = atual + 1;
                }
            });

            btnMenos.addEventListener('click', () => {
                let atual = parseInt(inputQtd.value, 10) || 1;
                if (atual > 1) {
                    inputQtd.value = atual - 1;
                }
            });
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
