<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: admin/index.php
 * Painel de controle e visão geral com métricas do e-commerce
 * ==============================================================================
 */

$titulo_pagina = 'Visão Geral do Painel';
require_once __DIR__ . '/admin_header.php';

// Consultas estatísticas
try {
    // 1. Total de Produtos
    $stmtProd = $pdo->query("SELECT COUNT(*) AS total, SUM(CASE WHEN estoque = 0 THEN 1 ELSE 0 END) AS esgotados FROM produtos");
    $statsProd = $stmtProd->fetch();

    // 2. Total de Clientes
    $stmtCli = $pdo->query("SELECT COUNT(*) AS total FROM clientes");
    $totalClientes = $stmtCli->fetchColumn();

    // 3. Total de Vendas e Faturamento
    $stmtVendas = $pdo->query("SELECT COUNT(*) AS total_pedidos, COALESCE(SUM(total), 0) AS faturamento FROM compras");
    $statsVendas = $stmtVendas->fetch();

    // 4. Últimos 5 Pedidos Realizados
    $stmtUltimos = $pdo->query("
        SELECT c.id, c.data_compra, c.total, c.forma_pagamento, c.status, cl.nome AS cliente_nome 
        FROM compras c
        JOIN clientes cl ON cl.id = c.cliente_id
        ORDER BY c.data_compra DESC
        LIMIT 5
    ");
    $ultimosPedidos = $stmtUltimos->fetchAll();

} catch (PDOException $e) {
    error_log("Erro no dashboard admin: " . $e->getMessage());
    $statsProd = ['total' => 0, 'esgotados' => 0];
    $totalClientes = 0;
    $statsVendas = ['total_pedidos' => 0, 'faturamento' => 0];
    $ultimosPedidos = [];
}
?>

<div class="admin-header-bar">
    <div>
        <h1 style="font-size: 1.75rem; font-weight: 800;">Painel de Gestão da Loja</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Resumo geral de vendas, catálogo e atividades do sistema.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="cadastro_produto.php" class="btn btn-primary">+ Cadastrar Produto</a>
        <a href="listar_produtos.php" class="btn btn-outline">Gerenciar Catálogo</a>
    </div>
</div>

<!-- Grid de Métricas Principais -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-card-title">Faturamento Total</span>
        <span class="stat-card-val" style="color: var(--success);"><?= formatarPreco($statsVendas['faturamento']) ?></span>
        <span style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem;">Volume acumulado</span>
    </div>

    <div class="stat-card">
        <span class="stat-card-title">Pedidos Realizados</span>
        <span class="stat-card-val"><?= (int)$statsVendas['total_pedidos'] ?></span>
        <span style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem;">Compras registradas</span>
    </div>

    <div class="stat-card">
        <span class="stat-card-title">Produtos no Catálogo</span>
        <span class="stat-card-val"><?= (int)$statsProd['total'] ?></span>
        <span style="font-size: 0.8rem; color: <?= $statsProd['esgotados'] > 0 ? 'var(--danger)' : 'var(--text-muted)' ?>; margin-top: 0.35rem;">
            <?= (int)$statsProd['esgotados'] ?> produto(s) esgotado(s)
        </span>
    </div>

    <div class="stat-card">
        <span class="stat-card-title">Clientes Cadastrados</span>
        <span class="stat-card-val"><?= (int)$totalClientes ?></span>
        <span style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem;">Base de clientes ativos</span>
    </div>
</div>

<!-- Últimos Pedidos -->
<div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
        <h2 style="font-size: 1.25rem; font-weight: 700;">Últimas Compras Realizadas</h2>
        <a href="listar_compras.php" class="btn btn-sm btn-outline">Ver Todas as Compras &rarr;</a>
    </div>

    <?php if (empty($ultimosPedidos)): ?>
        <p style="color: var(--text-muted); padding: 1rem 0;">Nenhuma compra registrada até o momento.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Pagamento</th>
                        <th>Valor Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimosPedidos as $pedido): ?>
                        <tr>
                            <td><strong>#<?= str_pad((string)$pedido['id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                            <td><?= formatarData($pedido['data_compra']) ?></td>
                            <td><?= e($pedido['cliente_nome']) ?></td>
                            <td><?= e($pedido['forma_pagamento']) ?></td>
                            <td><strong><?= formatarPreco($pedido['total']) ?></strong></td>
                            <td>
                                <span class="badge-status <?= strtolower($pedido['status']) ?>">
                                    <?= e($pedido['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
