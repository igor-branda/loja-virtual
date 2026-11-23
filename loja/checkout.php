<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: checkout.php
 * Finalização de compras com Transação Atômica PDO (Lock FOR UPDATE, Baixa de Estoque)
 * ==============================================================================
 */

$titulo_pagina = 'Finalização do Pedido (Checkout)';
require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';

// 1. Exige que o cliente esteja autenticado
exigirLoginCliente('login.php?retorno=checkout.php');

$clienteId = $_SESSION['cliente_id'];

// 2. Valida se o carrinho possui itens
if (empty($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
    setMensagem('aviso', 'Seu carrinho está vazio. Adicione itens antes de finalizar a compra.');
    header('Location: carrinho.php');
    exit;
}

// 3. Busca dados cadastrais do cliente para pré-preenchimento
try {
    $stmtCli = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmtCli->execute([$clienteId]);
    $cliente = $stmtCli->fetch();
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do cliente no checkout: " . $e->getMessage());
    $cliente = [];
}

// 4. Carrega itens do carrinho para exibição do resumo
$itensCheckout = [];
$subtotalGeral = 0.0;
$ids = array_keys($_SESSION['carrinho']);
$inQuery = implode(',', array_fill(0, count($ids), '?'));

try {
    $stmtProd = $pdo->prepare("SELECT id, nome, preco, imagem, estoque FROM produtos WHERE id IN ({$inQuery})");
    $stmtProd->execute($ids);
    $prods = $stmtProd->fetchAll();

    foreach ($prods as $p) {
        $qtd = $_SESSION['carrinho'][$p['id']] ?? 0;
        if ($qtd > 0) {
            $subItem = (float)$p['preco'] * $qtd;
            $subtotalGeral += $subItem;

            $caminhoImg = 'assets/img/' . e($p['imagem']);
            if (!empty($p['imagem']) && file_exists(__DIR__ . '/uploads/' . $p['imagem'])) {
                $caminhoImg = 'uploads/' . e($p['imagem']);
            }

            $itensCheckout[] = [
                'id'         => $p['id'],
                'nome'       => $p['nome'],
                'preco'      => $p['preco'],
                'quantidade' => $qtd,
                'subtotal'   => $subItem,
                'imagem'     => $caminhoImg
            ];
        }
    }
} catch (PDOException $e) {
    error_log("Erro ao carregar itens do checkout: " . $e->getMessage());
}

$erros = [];
$forma_pagamento = $_POST['forma_pagamento'] ?? 'Pix';

// Endereço de entrega (pré-preenchido do cadastro do cliente ou sessão de frete)
$enderecoEnvio = [
    'cep'         => $_POST['cep'] ?? $_SESSION['frete_cep'] ?? $cliente['cep'] ?? '',
    'logradouro'  => $_POST['logradouro'] ?? $cliente['logradouro'] ?? '',
    'numero'      => $_POST['numero'] ?? $cliente['numero'] ?? '',
    'complemento' => $_POST['complemento'] ?? $cliente['complemento'] ?? '',
    'bairro'      => $_POST['bairro'] ?? $cliente['bairro'] ?? '',
    'cidade'      => $_POST['cidade'] ?? $cliente['cidade'] ?? '',
    'uf'          => strtoupper(trim($_POST['uf'] ?? $_SESSION['frete_uf'] ?? $cliente['uf'] ?? ''))
];

// ------------------------------------------------------------------------------
// 5. PROCESSAMENTO DO PEDIDO COM TRANSAÇÃO ATÔMICA PDO
// ------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação CSRF
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erros[] = 'Sessão expirada. Por favor, tente confirmar o pedido novamente.';
    } else {
        $enderecoEnvio['cep']         = limparNumeros($_POST['cep'] ?? '');
        $enderecoEnvio['logradouro']  = trim($_POST['logradouro'] ?? '');
        $enderecoEnvio['numero']      = trim($_POST['numero'] ?? '');
        $enderecoEnvio['complemento'] = trim($_POST['complemento'] ?? '');
        $enderecoEnvio['bairro']      = trim($_POST['bairro'] ?? '');
        $enderecoEnvio['cidade']      = trim($_POST['cidade'] ?? '');
        $enderecoEnvio['uf']          = strtoupper(trim($_POST['uf'] ?? ''));
        $forma_pagamento              = trim($_POST['forma_pagamento'] ?? 'Pix');

        // Validações básicas de endereço
        if (strlen($enderecoEnvio['cep']) !== 8) {
            $erros[] = 'Informe um CEP de entrega válido (8 números).';
        }
        if (empty($enderecoEnvio['logradouro']) || empty($enderecoEnvio['numero']) || empty($enderecoEnvio['bairro']) || empty($enderecoEnvio['cidade']) || empty($enderecoEnvio['uf'])) {
            $erros[] = 'Preencha todos os campos obrigatórios do endereço de entrega.';
        }

        $formasValidas = ['Pix', 'Cartão de Crédito', 'Boleto Bancário'];
        if (!in_array($forma_pagamento, $formasValidas)) {
            $erros[] = 'Selecione uma forma de pagamento válida.';
        }

        // Se não houver erros preliminares, inicia a TRANSAÇÃO NO BANCO
        if (empty($erros)) {
            try {
                // Início da Transação Atômica
                $pdo->beginTransaction();

                $subtotalReal = 0.0;
                $itensParaGravar = [];

                // 1. Revalidação de Estoque e Preços com SELECT ... FOR UPDATE
                $sqlLock = "SELECT id, nome, preco, estoque, ativo FROM produtos WHERE id = ? FOR UPDATE";
                $stmtLock = $pdo->prepare($sqlLock);

                foreach ($_SESSION['carrinho'] as $prodId => $qtdComprada) {
                    $stmtLock->execute([$prodId]);
                    $prodDb = $stmtLock->fetch();

                    if (!$prodDb || !$prodDb['ativo']) {
                        throw new Exception("O produto '{$prodDb['nome']}' não está mais disponível.");
                    }

                    if ($prodDb['estoque'] < $qtdComprada) {
                        throw new Exception("Estoque insuficiente para o produto '{$prodDb['nome']}'. Disponível: {$prodDb['estoque']} unidade(s).");
                    }

                    $precoUnit = (float)$prodDb['preco'];
                    $subtotalReal += $precoUnit * $qtdComprada;

                    $itensParaGravar[] = [
                        'produto_id'     => $prodDb['id'],
                        'quantidade'     => $qtdComprada,
                        'preco_unitario' => $precoUnit
                    ];
                }

                // 2. Cálculo Oficial do Frete no Servidor
                $freteFinal = 35.00;
                if ($subtotalReal >= 200.00) {
                    $freteFinal = 0.00; // Frete Grátis
                } else {
                    $ufDestino = $enderecoEnvio['uf'];
                    if ($ufDestino === 'PA') {
                        $freteFinal = 12.00;
                    } elseif (in_array($ufDestino, ['AM', 'AP', 'RR', 'TO', 'AC', 'RO', 'MA', 'PI', 'CE', 'RN', 'PB', 'PE', 'AL', 'SE', 'BA'])) {
                        $freteFinal = 25.00;
                    } elseif (in_array($ufDestino, ['MT', 'MS', 'GO', 'DF'])) {
                        $freteFinal = 30.00;
                    } elseif (in_array($ufDestino, ['SP', 'RJ', 'MG', 'ES'])) {
                        $freteFinal = 35.00;
                    } elseif (in_array($ufDestino, ['PR', 'SC', 'RS'])) {
                        $freteFinal = 40.00;
                    }
                }

                $totalFinal = $subtotalReal + $freteFinal;

                // Monta string completa do endereço para histórico de entrega
                $enderecoCompletoTexto = $enderecoEnvio['logradouro'] . ', ' . 
                                         $enderecoEnvio['numero'] . 
                                         (!empty($enderecoEnvio['complemento']) ? ' - ' . $enderecoEnvio['complemento'] : '') . ', ' . 
                                         $enderecoEnvio['bairro'] . ', ' . 
                                         $enderecoEnvio['cidade'] . ' - ' . 
                                         $enderecoEnvio['uf'];

                // 3. Inserção na Tabela COMPRAS
                $sqlCompra = "INSERT INTO compras (cliente_id, subtotal, frete, total, forma_pagamento, status, cep_entrega, endereco_entrega)
                              VALUES (?, ?, ?, ?, ?, 'Pago', ?, ?)";
                $stmtCompra = $pdo->prepare($sqlCompra);
                $stmtCompra->execute([
                    $clienteId,
                    $subtotalReal,
                    $freteFinal,
                    $totalFinal,
                    $forma_pagamento,
                    $enderecoEnvio['cep'],
                    $enderecoCompletoTexto
                ]);

                $novoPedidoId = (int)$pdo->lastInsertId();

                // 4. Inserção dos Itens e Baixa no Estoque dos Produtos
                $sqlItem = "INSERT INTO itens_compra (compra_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)";
                $stmtItem = $pdo->prepare($sqlItem);

                $sqlBaixa = "UPDATE produtos SET estoque = estoque - ? WHERE id = ?";
                $stmtBaixa = $pdo->prepare($sqlBaixa);

                foreach ($itensParaGravar as $itemG) {
                    // Grava o item com o preço unitário congelado
                    $stmtItem->execute([
                        $novoPedidoId,
                        $itemG['produto_id'],
                        $itemG['quantidade'],
                        $itemG['preco_unitario']
                    ]);

                    // Dá baixa imediata no estoque físico
                    $stmtBaixa->execute([
                        $itemG['quantidade'],
                        $itemG['produto_id']
                    ]);
                }

                // 5. Commit da Transação
                $pdo->commit();

                // 6. Limpeza do Carrinho e Sessão de Frete
                $_SESSION['carrinho'] = [];
                unset($_SESSION['frete_valor'], $_SESSION['frete_cep'], $_SESSION['frete_endereco']);

                // Redireciona para a página de confirmação do pedido
                header("Location: pedido_confirmado.php?id={$novoPedidoId}");
                exit;

            } catch (Exception $e) {
                // Rollback em caso de qualquer falha
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Falha na transação do checkout: " . $e->getMessage());
                $erros[] = $e->getMessage();
            }
        }
    }
}

// Cálculo estimado do frete atual para a tela
$freteDisplay = ($subtotalGeral >= 200.00) ? 0.00 : (isset($_SESSION['frete_valor']) ? (float)$_SESSION['frete_valor'] : 25.00);
$totalDisplay = $subtotalGeral + $freteDisplay;

require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 1.85rem; font-weight: 800;">Finalização da Compra</h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">Confirme seus dados de entrega, escolha a forma de pagamento e conclua seu pedido.</p>
</div>

<?php if (!empty($erros)): ?>
    <div class="alert alert-erro">
        <div>
            <strong>Atenção! Não foi possível concluir o pedido:</strong>
            <ul style="margin-top: 0.5rem; padding-left: 1.25rem;">
                <?php foreach ($erros as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <button type="button" class="alert-close" onclick="this.parentElement.remove();">&times;</button>
    </div>
<?php endif; ?>

<form action="checkout.php" method="POST" id="form-checkout" novalidate>
    <?= campoCSRF() ?>

    <div class="checkout-layout">
        <!-- Coluna da Esquerda: Etapas de Entrega e Pagamento -->
        <div>
            <!-- ETAPA 1: Endereço de Entrega -->
            <section class="checkout-step">
                <h2 class="checkout-step-title">
                    <span class="step-num">1</span>
                    Endereço de Entrega
                </h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cep" class="form-label">CEP *</label>
                        <input type="text" name="cep" id="cep" class="form-control" value="<?= e(formatarCep($enderecoEnvio['cep'])) ?>" required placeholder="00000-000">
                        <small id="aviso-cep" class="form-text-muted">Busca automática via ViaCEP.</small>
                    </div>
                    <div class="form-group">
                        <label for="uf" class="form-label">Estado (UF) *</label>
                        <input type="text" name="uf" id="uf" class="form-control" value="<?= e($enderecoEnvio['uf']) ?>" maxlength="2" required placeholder="Ex: PA, SP">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cidade" class="form-label">Cidade *</label>
                        <input type="text" name="cidade" id="cidade" class="form-control" value="<?= e($enderecoEnvio['cidade']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="bairro" class="form-label">Bairro *</label>
                        <input type="text" name="bairro" id="bairro" class="form-control" value="<?= e($enderecoEnvio['bairro']) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="logradouro" class="form-label">Logradouro (Rua, Avenida) *</label>
                    <input type="text" name="logradouro" id="logradouro" class="form-control" value="<?= e($enderecoEnvio['logradouro']) ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="numero" class="form-label">Número *</label>
                        <input type="text" name="numero" id="numero" class="form-control" value="<?= e($enderecoEnvio['numero']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="complemento" class="form-label">Complemento</label>
                        <input type="text" name="complemento" id="complemento" class="form-control" value="<?= e($enderecoEnvio['complemento']) ?>" placeholder="Apto, Bloco...">
                    </div>
                </div>
            </section>

            <!-- ETAPA 2: Forma de Pagamento Simulada -->
            <section class="checkout-step">
                <h2 class="checkout-step-title">
                    <span class="step-num">2</span>
                    Forma de Pagamento
                </h2>

                <div class="payment-options-grid">
                    <label class="payment-radio-label <?= $forma_pagamento === 'Pix' ? 'selected' : '' ?>">
                        <input type="radio" name="forma_pagamento" value="Pix" <?= $forma_pagamento === 'Pix' ? 'checked' : '' ?> onchange="atualizarSelecaoPagamento(this)">
                        <strong style="color: var(--primary);">Pix</strong>
                        <span style="font-size: 0.75rem; color: var(--text-muted);">Aprovação Instantânea</span>
                    </label>

                    <label class="payment-radio-label <?= $forma_pagamento === 'Cartão de Crédito' ? 'selected' : '' ?>">
                        <input type="radio" name="forma_pagamento" value="Cartão de Crédito" <?= $forma_pagamento === 'Cartão de Crédito' ? 'checked' : '' ?> onchange="atualizarSelecaoPagamento(this)">
                        <strong style="color: var(--text-main);">Cartão de Crédito</strong>
                        <span style="font-size: 0.75rem; color: var(--text-muted);">Em até 10x sem juros</span>
                    </label>

                    <label class="payment-radio-label <?= $forma_pagamento === 'Boleto Bancário' ? 'selected' : '' ?>">
                        <input type="radio" name="forma_pagamento" value="Boleto Bancário" <?= $forma_pagamento === 'Boleto Bancário' ? 'checked' : '' ?> onchange="atualizarSelecaoPagamento(this)">
                        <strong style="color: var(--text-main);">Boleto Bancário</strong>
                        <span style="font-size: 0.75rem; color: var(--text-muted);">Vencimento em 3 dias</span>
                    </label>
                </div>

                <div id="detalhe-pagamento-pix" style="background: var(--bg-surface-alt); padding: 1rem; border-radius: var(--radius-md); font-size: 0.85rem; color: var(--text-muted);">
                    O código Pix e o QR Code oficial serão gerados imediatamente após a confirmação do pedido.
                </div>
            </section>
        </div>

        <!-- Coluna da Direita: Resumo Financeiro do Pedido -->
        <div class="cart-summary-card">
            <h2 class="summary-title">Itens do Pedido (<?= count($itensCheckout) ?>)</h2>

            <div style="max-height: 280px; overflow-y: auto; margin-bottom: 1.25rem;">
                <?php foreach ($itensCheckout as $item): ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.85rem; font-size: 0.875rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <img src="<?= $item['imagem'] ?>" alt="<?= e($item['nome']) ?>" style="width: 40px; height: 40px; object-fit: contain; background: var(--bg-surface-alt); border-radius: 4px;">
                            <div>
                                <span style="font-weight: 600; display: block; max-width: 170px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= e($item['nome']) ?>
                                </span>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Qtd: <?= (int)$item['quantidade'] ?></span>
                            </div>
                        </div>
                        <span style="font-weight: 700;"><?= formatarPreco($item['subtotal']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-line">
                <span>Subtotal dos Produtos</span>
                <strong><?= formatarPreco($subtotalGeral) ?></strong>
            </div>

            <div class="summary-line">
                <span>Frete Estimado</span>
                <strong style="color: <?= $freteDisplay == 0 ? 'var(--success)' : 'inherit' ?>;">
                    <?= $freteDisplay == 0 ? 'FRETE GRÁTIS' : formatarPreco($freteDisplay) ?>
                </strong>
            </div>

            <div class="summary-line total">
                <span>Total a Pagar</span>
                <span style="color: var(--primary);"><?= formatarPreco($totalDisplay) ?></span>
            </div>

            <div style="margin-top: 1.75rem;">
                <button type="submit" class="btn btn-success btn-block" style="padding: 1.1rem; font-size: 1.15rem; font-weight: 700; box-shadow: var(--shadow-lg);">
                    Confirmar Compra
                </button>
            </div>

            <div style="margin-top: 1rem; text-align: center;">
                <a href="carrinho.php" style="color: var(--text-muted); font-size: 0.85rem;">
                    &larr; Voltar e Editar Carrinho
                </a>
            </div>
        </div>
    </div>
</form>

<script>
    function atualizarSelecaoPagamento(radio) {
        document.querySelectorAll('.payment-radio-label').forEach(label => label.classList.remove('selected'));
        if (radio.checked) {
            radio.closest('.payment-radio-label').classList.add('selected');
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
