<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: pedido_confirmado.php
 * Tela de confirmação e recibo da compra realizada
 * ==============================================================================
 */

require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';

exigirLoginCliente('login.php');

$clienteId = $_SESSION['cliente_id'];
$pedidoId  = (int)($_GET['id'] ?? 0);

if ($pedidoId <= 0) {
    setMensagem('erro', 'Pedido inválido.');
    header('Location: index.php');
    exit;
}

// Busca os dados da compra assegurando que pertence ao cliente logado
try {
    $stmtCompra = $pdo->prepare("
        SELECT c.*, cl.nome AS cliente_nome, cl.email AS cliente_email 
        FROM compras c
        JOIN clientes cl ON cl.id = c.cliente_id
        WHERE c.id = ? AND c.cliente_id = ?
        LIMIT 1
    ");
    $stmtCompra->execute([$pedidoId, $clienteId]);
    $compra = $stmtCompra->fetch();

    if (!$compra) {
        setMensagem('erro', 'Pedido não encontrado.');
        header('Location: meus_pedidos.php');
        exit;
    }

    // Busca os itens desta compra
    $stmtItens = $pdo->prepare("
        SELECT ic.*, p.nome AS produto_nome, p.imagem AS produto_imagem
        FROM itens_compra ic
        JOIN produtos p ON p.id = ic.produto_id
        WHERE ic.compra_id = ?
    ");
    $stmtItens->execute([$pedidoId]);
    $itens = $stmtItens->fetchAll();

} catch (PDOException $e) {
    error_log("Erro ao carregar confirmação do pedido: " . $e->getMessage());
    setMensagem('erro', 'Erro ao consultar o pedido.');
    header('Location: index.php');
    exit;
}

$titulo_pagina = 'Pedido #' . str_pad((string)$pedidoId, 5, '0', STR_PAD_LEFT) . ' Confirmado!';
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 820px; margin: 1rem auto;">

    <!-- Card de Parabéns / Sucesso -->
    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 3rem 2rem; text-align: center; margin-bottom: 2rem; box-shadow: var(--shadow-sm);">
        <div style="width: 72px; height: 72px; background: var(--success-bg); color: var(--success); border-radius: var(--radius-full); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>

        <span style="display: block; font-size: 0.9rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.35rem;">
            Compra Concluída com Sucesso!
        </span>
        <h1 style="font-size: 2.1rem; font-weight: 800; margin-bottom: 0.5rem;">
            Obrigado pelo seu pedido, <?= e(explode(' ', $compra['cliente_nome'])[0]) ?>!
        </h1>
        <p style="color: var(--text-muted); font-size: 1.05rem;">
            O pedido <strong>#<?= str_pad((string)$compra['id'], 5, '0', STR_PAD_LEFT) ?></strong> foi gerado e registrado no banco de dados.
        </p>
    </div>

    <!-- Instruções de Pagamento Conforme a Escolha -->
    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.75rem; margin-bottom: 2rem;">
        <h2 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
            Informações de Pagamento
        </h2>

        <?php if ($compra['forma_pagamento'] === 'Pix'): ?>
            <div style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap;">
                <div style="background: #ffffff; border: 2px solid var(--border-color); padding: 1rem; border-radius: var(--radius-md); text-align: center;">
                    <svg width="130" height="130" viewBox="0 0 100 100" fill="#0f172a">
                        <!-- QR Code vetorial ilustrativo -->
                        <rect width="100" height="100" fill="#ffffff" />
                        <rect x="10" y="10" width="30" height="30" fill="#0284c7" />
                        <rect x="16" y="16" width="18" height="18" fill="#ffffff" />
                        <rect x="20" y="20" width="10" height="10" fill="#0284c7" />
                        <rect x="60" y="10" width="30" height="30" fill="#0284c7" />
                        <rect x="66" y="16" width="18" height="18" fill="#ffffff" />
                        <rect x="70" y="20" width="10" height="10" fill="#0284c7" />
                        <rect x="10" y="60" width="30" height="30" fill="#0284c7" />
                        <rect x="16" y="66" width="18" height="18" fill="#ffffff" />
                        <rect x="20" y="70" width="10" height="10" fill="#0284c7" />
                        <rect x="50" y="50" width="10" height="10" fill="#0f172a" />
                        <rect x="70" y="55" width="15" height="10" fill="#0f172a" />
                        <rect x="55" y="75" width="20" height="15" fill="#0f172a" />
                    </svg>
                    <span style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 0.25rem;">QR Code Pix</span>
                </div>
                <div style="flex: 1; min-width: 250px;">
                    <strong style="color: var(--primary); font-size: 1.05rem;">Pagamento via Pix Aprovado / Confirmado</strong>
                    <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0.5rem 0;">
                        Chave Pix Simulada: <code>techwave-pagamentos@banco.com.br</code>
                    </p>
                    <span class="badge-status pago">Status: Pago com Sucesso</span>
                </div>
            </div>
        <?php elseif ($compra['forma_pagamento'] === 'Boleto Bancário'): ?>
            <div style="padding: 1rem; background: var(--bg-surface-alt); border-radius: var(--radius-md);">
                <strong style="display: block; margin-bottom: 0.5rem;">Linha Digitável do Boleto:</strong>
                <code style="font-size: 0.95rem; font-weight: 700; background: #fff; padding: 0.5rem 0.75rem; border: 1px solid var(--border-color); border-radius: 4px; display: block; overflow-x: auto;">
                    34191.79001 01043.510047 91020.150008 4 954800244890
                </code>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">
                    Vencimento em 3 dias úteis. Compensação bancária automática em até 24 horas.
                </p>
            </div>
        <?php else: ?>
            <div style="padding: 1rem; background: var(--bg-surface-alt); border-radius: var(--radius-md);">
                <strong style="color: var(--success); font-size: 1rem;">Transação de Cartão de Crédito Aprovada!</strong>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.25rem;">
                    O pagamento foi processado e autorizado com sucesso. Em breve você receberá as atualizações de envio.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Detalhes do Pedido e Endereço -->
    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.75rem; margin-bottom: 2rem;">
        <h2 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
            Dados da Entrega
        </h2>
        <div style="font-size: 0.9375rem; line-height: 1.6;">
            <p><strong>Destinatário:</strong> <?= e($compra['cliente_nome']) ?> (<?= e($compra['cliente_email']) ?>)</p>
            <p><strong>Endereço:</strong> <?= e($compra['endereco_entrega']) ?></p>
            <p><strong>CEP de Entrega:</strong> <?= e(formatarCep($compra['cep_entrega'])) ?></p>
            <p><strong>Data da Compra:</strong> <?= formatarData($compra['data_compra']) ?></p>
        </div>
    </div>

    <!-- Tabela de Itens Comprados -->
    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.75rem; margin-bottom: 2.5rem;">
        <h2 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
            Itens Adquiridos
        </h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="width: 90px; text-align: center;">Qtd</th>
                    <th style="width: 140px; text-align: right;">Preço Unit.</th>
                    <th style="width: 140px; text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $it): 
                    $subIt = (float)$it['preco_unitario'] * (int)$it['quantidade'];
                ?>
                    <tr>
                        <td><strong><?= e($it['produto_nome']) ?></strong></td>
                        <td style="text-align: center;"><?= (int)$it['quantidade'] ?></td>
                        <td style="text-align: right;"><?= formatarPreco($it['preco_unitario']) ?></td>
                        <td style="text-align: right; font-weight: 700;"><?= formatarPreco($subIt) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totais -->
        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem; margin-top: 1.25rem; font-size: 0.95rem;">
            <div>Subtotal dos Itens: <strong><?= formatarPreco($compra['subtotal']) ?></strong></div>
            <div>Frete: <strong><?= $compra['frete'] == 0 ? 'Grátis' : formatarPreco($compra['frete']) ?></strong></div>
            <div style="font-size: 1.35rem; font-weight: 800; color: var(--primary); border-top: 1px dashed var(--border-color); padding-top: 0.5rem; margin-top: 0.25rem;">
                Total Pago: <?= formatarPreco($compra['total']) ?>
            </div>
        </div>
    </div>

    <!-- Botões de Ação -->
    <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
        <a href="meus_pedidos.php" class="btn btn-primary" style="padding: 0.85rem 1.75rem;">
            Ver Meus Pedidos
        </a>
        <a href="index.php" class="btn btn-outline" style="padding: 0.85rem 1.75rem;">
            Voltar para a Página Inicial
        </a>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
