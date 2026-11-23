<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: admin/admin_header.php
 * Cabeçalho e barra de navegação exclusiva da área administrativa
 * ==============================================================================
 */

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

// Bloqueia acesso de usuários não autorizados
exigirLoginAdmin('login.php');

$admin_usuario = $_SESSION['admin_usuario'] ?? 'Administrador';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo_pagina ?? 'Painel Admin') ?> | TechWave Store</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-nav {
            background-color: #0f172a;
            color: #ffffff;
            border-bottom: 2px solid var(--primary);
        }
        .admin-nav-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 1.25rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .admin-links {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            list-style: none;
            margin: 0;
            padding: 0;
            flex-wrap: wrap;
        }
        .admin-links a {
            color: #cbd5e1;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 0.35rem 0.65rem;
            border-radius: var(--radius-sm);
        }
        .admin-links a:hover, .admin-links a.active {
            color: #ffffff;
            background-color: #1e293b;
        }
        .admin-badge-user {
            background-color: #1e293b;
            color: var(--primary);
            padding: 0.35rem 0.75rem;
            border-radius: var(--radius-full);
            font-size: 0.8rem;
            font-weight: 700;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        .stat-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
        }
        .stat-card-title {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        .stat-card-val {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-main);
        }
    </style>
</head>
<body>

    <!-- Menu Superior Administrativo -->
    <nav class="admin-nav">
        <div class="container admin-nav-content">
            <div style="display: flex; align-items: center; gap: 1.5rem;">
                <a href="index.php" style="display: flex; align-items: center;">
                    <img src="../assets/img/logo.svg" alt="TechWave Store" width="160" height="38" style="filter: brightness(0) invert(1);">
                </a>
                <span class="admin-badge-user">Painel de Gestão</span>
            </div>

            <ul class="admin-links">
                <li><a href="index.php">Visão Geral</a></li>
                <li><a href="listar_produtos.php">Produtos</a></li>
                <li><a href="cadastro_produto.php">+ Novo Produto</a></li>
                <li><a href="listar_compras.php">Pedidos / Compras</a></li>
                <li><a href="../index.php" target="_blank" style="color: #38bdf8;">Visualizar Loja ↗</a></li>
                <li><a href="logout.php" style="color: var(--danger);">Sair</a></li>
            </ul>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <?= renderMensagens() ?>
