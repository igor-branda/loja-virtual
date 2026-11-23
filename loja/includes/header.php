<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: includes/header.php
 * Cabeçalho padrão compartilhado por todas as páginas da loja
 * ==============================================================================
 */

// Define o caminho relativo base caso não tenha sido definido pela página
if (!isset($caminho_base)) {
    $caminho_base = '';
}

// Requer conexão e funções
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/funcoes.php';

// Título padrão da página
$titulo_completo = !empty($titulo_pagina) 
    ? e($titulo_pagina) . ' | TechWave Store' 
    : 'TechWave Store | Eletrônicos & Tecnologia';

// Busca categorias para o menu
$categorias_menu = getCategorias($pdo);

// Cliente autenticado
$cliente_atual = getClienteLogado();
$itens_no_carrinho = contarItensCarrinho();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_completo ?></title>
    
    <!-- Meta tags de SEO e usabilidade -->
    <meta name="description" content="TechWave Store: A sua loja virtual de eletrônicos, informática, áudio e periféricos com as melhores ofertas e entrega rápida.">
    
    <!-- Folha de Estilos Principal -->
    <link rel="stylesheet" href="<?= $caminho_base ?>assets/css/style.css">
</head>
<body>

    <!-- Faixa de Destaque Superior -->
    <div class="top-banner">
        <div class="container top-banner-content">
            <span>FRETE GRÁTIS para compras a partir de R$ 200,00</span>
            <div class="top-banner-links">
                <a href="<?= $caminho_base ?>admin/login.php" class="admin-link">Área do Lojista (Admin)</a>
            </div>
        </div>
    </div>

    <!-- Cabeçalho Principal -->
    <header class="site-header">
        <div class="container header-container">
            <!-- Logotipo da Loja -->
            <a href="<?= $caminho_base ?>index.php" class="brand-logo" aria-label="Página Inicial TechWave Store">
                <img src="<?= $caminho_base ?>assets/img/logo.svg" alt="TechWave Store" width="210" height="50">
            </a>

            <!-- Formulário de Busca com Autocompletar / Filtro -->
            <form action="<?= $caminho_base ?>index.php" method="GET" class="search-form" role="search">
                <div class="search-input-wrapper">
                    <input type="search" 
                           name="q" 
                           placeholder="Buscar smartphones, notebooks, fones..." 
                           value="<?= e($_GET['q'] ?? '') ?>"
                           aria-label="Campo de busca de produtos"
                           required>
                    <button type="submit" aria-label="Pesquisar">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </button>
                </div>
            </form>

            <!-- Ações do Usuário e Carrinho -->
            <div class="header-actions">
                <?php if ($cliente_atual): ?>
                    <!-- Menu do Cliente Logado -->
                    <div class="user-menu">
                        <div class="user-greeting">
                            <span class="user-subtitle">Bem-vindo(a),</span>
                            <strong class="user-name"><?= e(explode(' ', $cliente_atual['nome'])[0]) ?></strong>
                        </div>
                        <div class="user-links">
                            <a href="<?= $caminho_base ?>meus_pedidos.php" class="btn-link">Meus Pedidos</a>
                            <span class="divider">|</span>
                            <a href="<?= $caminho_base ?>logout.php" class="btn-link text-danger">Sair</a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Links para Usuário Visitante -->
                    <div class="guest-menu">
                        <a href="<?= $caminho_base ?>login.php" class="btn-outline-header">Entrar</a>
                        <a href="<?= $caminho_base ?>cadastro_cliente.php" class="btn-primary-header">Cadastre-se</a>
                    </div>
                <?php endif; ?>

                <!-- Botão do Carrinho de Compras -->
                <a href="<?= $caminho_base ?>carrinho.php" class="cart-button" aria-label="Abrir carrinho de compras">
                    <div class="cart-icon-wrapper">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <span id="cart-count" class="cart-badge <?= $itens_no_carrinho > 0 ? 'visible' : '' ?>">
                            <?= $itens_no_carrinho ?>
                        </span>
                    </div>
                    <span class="cart-text">Carrinho</span>
                </a>
            </div>
        </div>

        <!-- Barra de Navegação de Categorias -->
        <nav class="categories-nav" aria-label="Categorias de produtos">
            <div class="container nav-container">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="<?= $caminho_base ?>index.php" class="nav-link <?= empty($_GET['categoria']) ? 'active' : '' ?>">
                            Todas as Categorias
                        </a>
                    </li>
                    <?php foreach ($categorias_menu as $cat): ?>
                        <li class="nav-item">
                            <a href="<?= $caminho_base ?>index.php?categoria=<?= $cat['id'] ?>" 
                               class="nav-link <?= (isset($_GET['categoria']) && $_GET['categoria'] == $cat['id']) ? 'active' : '' ?>">
                                <?= e($cat['nome']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Container Principal do Conteúdo -->
    <main class="main-content">
        <div class="container">
            <!-- Exibição de Mensagens do Sistema (Flash Messages) -->
            <?= renderMensagens() ?>
        </div>
