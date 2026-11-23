<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: index.php
 * Página inicial da loja, vitrine dinâmica de produtos, busca e filtros
 * ==============================================================================
 */

$titulo_pagina = 'A sua Loja de Tecnologia e Eletrônicos';
require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';

// Filtros recebidos via URL (GET)
$categoria_id = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$busca = isset($_GET['q']) ? trim($_GET['q']) : '';

// Monta a consulta de produtos ativos com Prepared Statements
$sql = "SELECT p.*, c.nome AS categoria_nome 
        FROM produtos p
        JOIN categorias c ON c.id = p.categoria_id
        WHERE p.ativo = 1";
$params = [];

if ($categoria_id > 0) {
    $sql .= " AND p.categoria_id = ?";
    $params[] = $categoria_id;
}

if (!empty($busca)) {
    $sql .= " AND (p.nome LIKE ? OR p.descricao LIKE ?)";
    $termoBusca = "%{$busca}%";
    $params[] = $termoBusca;
    $params[] = $termoBusca;
}

$sql .= " ORDER BY p.id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $produtos = $stmt->fetchAll();

    // Nome da categoria filtrada se aplicável
    $nomeCategoriaAtual = '';
    if ($categoria_id > 0) {
        $stmtCat = $pdo->prepare("SELECT nome FROM categorias WHERE id = ?");
        $stmtCat->execute([$categoria_id]);
        $nomeCategoriaAtual = $stmtCat->fetchColumn() ?: '';
    }
} catch (PDOException $e) {
    error_log("Erro na consulta de vitrine: " . $e->getMessage());
    $produtos = [];
    $nomeCategoriaAtual = '';
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Banner Promocional de Destaque (Hero) -->
<?php if (empty($busca) && $categoria_id === 0): ?>
    <section class="hero-banner">
        <div class="hero-content">
            <span class="hero-tag">TechWave Inovação</span>
            <h1 class="hero-title">O Futuro da Tecnologia<br>Está ao Seu Alcance</h1>
            <p class="hero-description">
                Descubra a seleção de smartphones topo de linha, notebooks de alta performance, áudio imersivo e os periféricos que transformam seu setup.
            </p>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="#catalogo" class="btn btn-primary" style="padding: 0.85rem 1.75rem; font-size: 1rem;">
                    Explorar Ofertas &darr;
                </a>
                <div style="display: flex; align-items: center; gap: 0.5rem; color: #94a3b8; font-size: 0.9rem;">
                    <span>Frete Grátis acima de R$ 200</span>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Seção do Catálogo de Produtos -->
<section id="catalogo">
    <div class="catalog-header">
        <div>
            <?php if (!empty($busca)): ?>
                <h2 class="catalog-title">Resultados para: "<em><?= e($busca) ?></em>"</h2>
                <span class="catalog-count"><?= count($produtos) ?> produto(s) encontrado(s) &bull; <a href="index.php">Limpar busca</a></span>
            <?php elseif ($categoria_id > 0): ?>
                <h2 class="catalog-title"><?= e($nomeCategoriaAtual) ?></h2>
                <span class="catalog-count"><?= count($produtos) ?> item(ns) nesta categoria &bull; <a href="index.php">Ver todas</a></span>
            <?php else: ?>
                <h2 class="catalog-title">Vitrine de Destaques</h2>
                <span class="catalog-count">Mostrando <?= count($produtos) ?> produtos disponíveis</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($produtos)): ?>
        <!-- Mensagem de Nenhum Produto Encontrado -->
        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 4rem 2rem; text-align: center; margin-top: 1rem;">
            <svg width="54" height="54" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1rem;">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                <line x1="8" y1="11" x2="14" y2="11"></line>
            </svg>
            <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem;">Nenhum produto encontrado</h3>
            <p style="color: var(--text-muted); max-width: 450px; margin: 0 auto 1.5rem;">
                Não localizamos mercadorias correspondentes aos filtros selecionados. Tente termos diferentes ou limpe os filtros.
            </p>
            <a href="index.php" class="btn btn-primary">Voltar para Todos os Produtos</a>
        </div>
    <?php else: ?>
        <!-- Grade de Produtos -->
        <div class="products-grid">
            <?php foreach ($produtos as $p): 
                // Verifica localização da imagem
                $caminhoImg = 'assets/img/' . e($p['imagem']);
                if (!empty($p['imagem']) && file_exists(__DIR__ . '/uploads/' . $p['imagem'])) {
                    $caminhoImg = 'uploads/' . e($p['imagem']);
                }

                $estaEsgotado = ($p['estoque'] <= 0);
                $poucoEstoque = ($p['estoque'] > 0 && $p['estoque'] <= 5);
                $precoParcelado = $p['preco'] / 10;
            ?>
                <article class="product-card" aria-label="<?= e($p['nome']) ?>">
                    <div>
                        <!-- Container de Imagem com Badges -->
                        <div class="product-image-container">
                            <a href="produto.php?id=<?= (int)$p['id'] ?>" tabindex="-1">
                                <img src="<?= $caminhoImg ?>" 
                                     alt="<?= e($p['nome']) ?>" 
                                     loading="lazy" 
                                     width="250" 
                                     height="250">
                            </a>

                            <?php if ($estaEsgotado): ?>
                                <span class="product-badge-out">Esgotado</span>
                            <?php elseif ($poucoEstoque): ?>
                                <span style="position: absolute; top: 10px; right: 10px; background: var(--warning); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 3px 8px; border-radius: var(--radius-full);">
                                    Restam <?= (int)$p['estoque'] ?> un.
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Categoria e Título -->
                        <span class="product-category-label"><?= e($p['categoria_nome']) ?></span>
                        <h3 class="product-title">
                            <a href="produto.php?id=<?= (int)$p['id'] ?>" style="color: inherit;">
                                <?= e($p['nome']) ?>
                            </a>
                        </h3>

                        <!-- Bloco de Preços -->
                        <div class="product-price-box">
                            <div class="product-price"><?= formatarPreco($p['preco']) ?></div>
                            <div class="product-installments">
                                em até <strong>10x de <?= formatarPreco($precoParcelado) ?></strong> sem juros
                            </div>
                        </div>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="product-actions">
                        <button type="button" 
                                class="btn btn-primary btn-block btn-adicionar-carrinho" 
                                data-id="<?= (int)$p['id'] ?>"
                                <?= $estaEsgotado ? 'disabled' : '' ?>>
                            <?php if ($estaEsgotado): ?>
                                Indisponível
                            <?php else: ?>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="9" cy="21" r="1"></circle>
                                    <circle cx="20" cy="21" r="1"></circle>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                </svg>
                                Comprar
                            <?php endif; ?>
                        </button>

                        <a href="produto.php?id=<?= (int)$p['id'] ?>" class="btn btn-outline" title="Ver detalhes do produto" style="padding: 0.7rem 0.9rem;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
