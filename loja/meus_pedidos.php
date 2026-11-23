<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: meus_pedidos.php
 * Histórico de compras realizadas pelo cliente logado e detalhamento de itens
 * ==============================================================================
 */

$titulo_pagina = 'Meus Pedidos';
require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';

// Exige cliente logado
exigirLoginCliente('login.php?retorno=meus_pedidos.php');

$clienteId = $_SESSION['cliente_id'];

try {
    // 1. Busca todas as compras do cliente
    $stmtCompras = $pdo->prepare("
        SELECT * FROM compras 
        WHERE cliente_id = ? 
        ORDER BY data_compra DESC
    ");
    $stmtCompras->execute([$clienteId]);
    $pedidos = $stmtCompras->fetchAll();

    // 2. Busca os itens de todas as compras do cliente
    $itensPorPedido = [];
    if (!empty($pedidos)) {
        $compraIds = array_column($pedidos, 'id');
        $inQuery = implode(',', array_fill(0, count($compraIds), '?'));

        $stmtItens = $pdo->prepare("
            SELECT ic.*, p.nome AS produto_nome, p.imagem AS produto_imagem
            FROM itens_compra ic
            JOIN produtos p ON p.id = ic.produto_id
            WHERE ic.compra_id IN ({$inQuery})
        ");
        $stmtItens->execute($compraIds);
        $todosItens = $stmtItens->fetchAll();

        foreach ($todosItens as $it) {
            $itensPorPedido[$it['compra_id']][] = $it;
        }
    }

} catch (PDOException $e) {
    error_log("Erro ao buscar histórico de pedidos: " . $e->getMessage());
    $pedidos = [];
    $itensPorPedido = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 1.85rem; font-weight: 800;">Meus Pedidos</h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">Acompanhe o status e confira o histórico de todas as suas compras na TechWave Store.</p>
</div>

<?php if (empty($pedidos)): ?>
    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 4rem 2rem; text-align: center;">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1rem;">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <path d="M16 10a4 4 0 0 1-8 0"></path>
        </svg>
        <h2 style="font-size: 1.35rem; font-weight: 700; margin-bottom: 0.5rem;">Nenhum pedido encontrado</h2>
        <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto 1.5rem;">
            Você ainda não realizou compras em nossa loja. Veja os produtos em destaque e comece a comprar agora!
        </p>
        <a href="index.php" class="btn btn-primary" style="padding: 0.85rem 2rem;">Ir para a Vitrine</a>
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <?php foreach ($pedidos as $p): 
            $itens = $itensPorPedido[$p['id']] ?? [];
            $statusClass = strtolower($p['status']);
        ?>
            <article style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.75rem; box-shadow: var(--shadow-sm);">
                <!-- Cabeçalho do Pedido -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1.25rem; margin-bottom: 1.25rem;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.35rem;">
                            <h2 style="font-size: 1.35rem; font-weight: 800;">
                                Pedido #<?= str_pad((string)$p['id'], 5, '0', STR_PAD_LEFT) ?>
                            </h2>
                            <span class="badge-status <?= $statusClass ?>">
                                <?= e($p['status']) ?>
                            </span>
                        </div>
                        <span style="font-size: 0.85rem; color: var(--text-muted);">
                            Realizado em: <strong><?= formatarData($p['data_compra']) ?></strong>
                        </span>
                    </div>

                    <div style="text-align: right;">
                        <span style="font-size: 0.8rem; color: var(--text-muted); display: block;">Valor Total:</span>
                        <strong style="font-size: 1.3rem; color: var(--primary);"><?= formatarPreco($p['total']) ?></strong>
                    </div>
                </div>

                <!-- Detalhes do Envio e Pagamento -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem; font-size: 0.875rem; background: var(--bg-surface-alt); padding: 1rem; border-radius: var(--radius-md);">
                    <div>
                        <strong>Endereço de Entrega:</strong>
                        <p style="color: var(--text-muted); margin-top: 0.2rem;"><?= e($p['endereco_entrega']) ?></p>
                        <p style="color: var(--text-muted);">CEP: <?= e(formatarCep($p['cep_entrega'])) ?></p>
                    </div>
                    <div>
                        <strong>Forma de Pagamento:</strong>
                        <p style="color: var(--text-muted); margin-top: 0.2rem;"><?= e($p['forma_pagamento']) ?></p>
                        <p style="color: var(--text-muted);">Frete: <?= $p['frete'] == 0 ? 'Grátis' : formatarPreco($p['frete']) ?></p>
                    </div>
                </div>

                <!-- Lista de Itens do Pedido -->
                <h3 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.75rem;">Itens Deste Pedido:</h3>
                <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden;">
                    <table class="admin-table" style="margin: 0;">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th style="width: 100px; text-align: center;">Qtd</th>
                                <th style="width: 130px; text-align: right;">Preço Unit.</th>
                                <th style="width: 130px; text-align: right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $it): 
                                $subIt = (float)$it['preco_unitario'] * (int)$it['quantidade'];
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= e($it['produto_nome']) ?></strong>
                                    </td>
                                    <td style="text-align: center;"><?= (int)$it['quantidade'] ?></td>
                                    <td style="text-align: right;"><?= formatarPreco($it['preco_unitario']) ?></td>
                                    <td style="text-align: right; font-weight: 700;"><?= formatarPreco($subIt) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Link para Recibo / Confirmação -->
                <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                    <a href="pedido_confirmado.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline">
                        Visualizar Recibo Completo &rarr;
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
