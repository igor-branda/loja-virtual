<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: admin/listar_compras.php
 * Gestão de compras realizadas, detalhamento de itens e atualização de status
 * ==============================================================================
 */

$titulo_pagina = 'Gerenciar Compras Realizadas';
require_once __DIR__ . '/admin_header.php';

// Processamento da Alteração de Status do Pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'alterar_status') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('erro', 'Sessão expirada. Tente novamente.');
    } else {
        $compra_id = (int)($_POST['compra_id'] ?? 0);
        $novo_status = trim($_POST['status'] ?? '');
        $statusPermitidos = ['Pendente', 'Pago', 'Enviado', 'Entregue', 'Cancelado'];

        if ($compra_id > 0 && in_array($novo_status, $statusPermitidos)) {
            try {
                $stmtStatus = $pdo->prepare("UPDATE compras SET status = ? WHERE id = ?");
                $stmtStatus->execute([$novo_status, $compra_id]);

                setMensagem('sucesso', "Status do Pedido #{$compra_id} alterado para '{$novo_status}' com sucesso!");
                header('Location: listar_compras.php');
                exit;

            } catch (PDOException $e) {
                error_log("Erro ao atualizar status da compra: " . $e->getMessage());
                setMensagem('erro', 'Falha ao atualizar o status do pedido.');
            }
        }
    }
}

// Busca todas as compras com dados do cliente
try {
    $sqlCompras = "
        SELECT c.*, cl.nome AS cliente_nome, cl.email AS cliente_email, cl.cpf AS cliente_cpf, cl.telefone AS cliente_telefone
        FROM compras c
        JOIN clientes cl ON cl.id = c.cliente_id
        ORDER BY c.data_compra DESC
    ";
    $compras = $pdo->query($sqlCompras)->fetchAll();

    // Busca os itens de todas as compras para exibição detalhada
    $sqlItens = "
        SELECT ic.*, p.nome AS produto_nome, p.imagem AS produto_imagem
        FROM itens_compra ic
        JOIN produtos p ON p.id = ic.produto_id
    ";
    $todosItens = $pdo->query($sqlItens)->fetchAll();

    // Agrupa itens por compra_id
    $itensPorCompra = [];
    foreach ($todosItens as $item) {
        $itensPorCompra[$item['compra_id']][] = $item;
    }

} catch (PDOException $e) {
    error_log("Erro ao buscar compras no admin: " . $e->getMessage());
    $compras = [];
    $itensPorCompra = [];
}
?>

<div class="admin-header-bar">
    <div>
        <h1 style="font-size: 1.75rem; font-weight: 800;">Pedidos & Compras</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Controle de entregas, pagamentos e histórico de clientes.</p>
    </div>
</div>

<?php if (empty($compras)): ?>
    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 3rem; text-align: center;">
        <p style="color: var(--text-muted); font-size: 1.1rem;">Nenhuma compra foi efetuada até o momento.</p>
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 1.75rem;">
        <?php foreach ($compras as $c): 
            $itens = $itensPorCompra[$c['id']] ?? [];
        ?>
            <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.75rem; box-shadow: var(--shadow-sm);">
                <!-- Cabeçalho do Card do Pedido -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1rem;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <h2 style="font-size: 1.25rem; font-weight: 800;">Pedido #<?= str_pad((string)$c['id'], 5, '0', STR_PAD_LEFT) ?></h2>
                            <span class="badge-status <?= strtolower($c['status']) ?>"><?= e($c['status']) ?></span>
                        </div>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                            Realizado em: <strong><?= formatarData($c['data_compra']) ?></strong>
                        </p>
                    </div>

                    <!-- Alteração Rápida de Status -->
                    <form action="listar_compras.php" method="POST" style="display: flex; align-items: center; gap: 0.5rem;">
                        <?= campoCSRF() ?>
                        <input type="hidden" name="acao" value="alterar_status">
                        <input type="hidden" name="compra_id" value="<?= (int)$c['id'] ?>">
                        <label for="status_<?= $c['id'] ?>" style="font-size: 0.85rem; font-weight: 600;">Alterar Status:</label>
                        <select name="status" id="status_<?= $c['id'] ?>" class="form-control" style="padding: 0.35rem 0.65rem; font-size: 0.85rem;">
                            <option value="Pendente" <?= $c['status'] === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                            <option value="Pago" <?= $c['status'] === 'Pago' ? 'selected' : '' ?>>Pago</option>
                            <option value="Enviado" <?= $c['status'] === 'Enviado' ? 'selected' : '' ?>>Enviado</option>
                            <option value="Entregue" <?= $c['status'] === 'Entregue' ? 'selected' : '' ?>>Entregue</option>
                            <option value="Cancelado" <?= $c['status'] === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline">Salvar</button>
                    </form>
                </div>

                <!-- Dados do Cliente e Envio -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.25rem; font-size: 0.9rem;">
                    <div style="background: var(--bg-surface-alt); padding: 1rem; border-radius: var(--radius-md);">
                        <strong style="color: var(--text-main); display: block; margin-bottom: 0.4rem;">Dados do Cliente:</strong>
                        <p><strong>Nome:</strong> <?= e($c['cliente_nome']) ?></p>
                        <p><strong>E-mail:</strong> <?= e($c['cliente_email']) ?></p>
                        <p><strong>CPF:</strong> <?= e(formatarCpf($c['cliente_cpf'])) ?> | <strong>Telefone:</strong> <?= e($c['cliente_telefone'] ?: 'Não informado') ?></p>
                    </div>

                    <div style="background: var(--bg-surface-alt); padding: 1rem; border-radius: var(--radius-md);">
                        <strong style="color: var(--text-main); display: block; margin-bottom: 0.4rem;">Endereço e Pagamento:</strong>
                        <p><strong>Entrega:</strong> <?= e($c['endereco_entrega']) ?></p>
                        <p><strong>CEP:</strong> <?= e(formatarCep($c['cep_entrega'])) ?></p>
                        <p><strong>Forma de Pagamento:</strong> <?= e($c['forma_pagamento']) ?></p>
                    </div>
                </div>

                <!-- Itens Comprados -->
                <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.75rem;">Itens do Pedido:</h4>
                <table class="admin-table" style="margin-bottom: 1rem;">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th style="width: 100px; text-align: center;">Qtd</th>
                            <th style="width: 140px; text-align: right;">Preço Unitário</th>
                            <th style="width: 140px; text-align: right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens as $it): ?>
                            <tr>
                                <td><?= e($it['produto_nome']) ?></td>
                                <td style="text-align: center;"><?= (int)$it['quantidade'] ?></td>
                                <td style="text-align: right;"><?= formatarPreco($it['preco_unitario']) ?></td>
                                <td style="text-align: right;"><strong><?= formatarPreco($it['preco_unitario'] * $it['quantidade']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Resumo Financeiro -->
                <div style="display: flex; justify-content: flex-end; gap: 2rem; font-size: 0.95rem; border-top: 1px dashed var(--border-color); padding-top: 0.75rem;">
                    <span>Subtotal: <strong><?= formatarPreco($c['subtotal']) ?></strong></span>
                    <span>Frete: <strong><?= $c['frete'] == 0 ? 'Grátis' : formatarPreco($c['frete']) ?></strong></span>
                    <span style="font-size: 1.15rem; color: var(--primary);">Total Geral: <strong><?= formatarPreco($c['total']) ?></strong></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
