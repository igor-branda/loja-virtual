<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: admin/listar_produtos.php
 * Gestão completa do catálogo: listagem, status e exclusão com proteção relacional
 * ==============================================================================
 */

$titulo_pagina = 'Gerenciar Produtos';
require_once __DIR__ . '/admin_header.php';

// Processamento de Exclusão / Inativação de Produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'excluir') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('erro', 'Sessão expirada. Tente novamente.');
    } else {
        $produto_id = (int)($_POST['produto_id'] ?? 0);

        if ($produto_id > 0) {
            try {
                // 1. Verifica se o produto tem vendas registradas em itens_compra
                $stmtCheckVendas = $pdo->prepare("SELECT COUNT(*) FROM itens_compra WHERE produto_id = ?");
                $stmtCheckVendas->execute([$produto_id]);
                $totalVendas = (int)$stmtCheckVendas->fetchColumn();

                if ($totalVendas > 0) {
                    // Proteção de integridade referencial: inativa em vez de deletar
                    $stmtInativar = $pdo->prepare("UPDATE produtos SET ativo = 0 WHERE id = ?");
                    $stmtInativar->execute([$produto_id]);

                    setMensagem('aviso', "O produto #{$produto_id} possui {$totalVendas} venda(s) registrada(s). Ele foi INATIVADO para preservar o histórico de compras.");
                } else {
                    // Sem vendas vinculadas: busca imagem para limpeza e exclui
                    $stmtImg = $pdo->prepare("SELECT imagem FROM produtos WHERE id = ?");
                    $stmtImg->execute([$produto_id]);
                    $img = $stmtImg->fetchColumn();

                    $stmtDel = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
                    $stmtDel->execute([$produto_id]);

                    // Remove arquivo de upload se existir
                    if ($img && file_exists(__DIR__ . '/../uploads/' . $img)) {
                        @unlink(__DIR__ . '/../uploads/' . $img);
                    }

                    setMensagem('sucesso', "Produto #{$produto_id} excluído com sucesso do catálogo.");
                }

                header('Location: listar_produtos.php');
                exit;

            } catch (PDOException $e) {
                error_log("Erro ao excluir/inativar produto: " . $e->getMessage());
                setMensagem('erro', 'Erro ao processar a exclusão do produto.');
            }
        }
    }
}

// Busca todos os produtos com suas respectivas categorias
try {
    $sql = "SELECT p.*, c.nome AS categoria_nome 
            FROM produtos p
            JOIN categorias c ON c.id = p.categoria_id
            ORDER BY p.id DESC";
    $produtos = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao listar produtos: " . $e->getMessage());
    $produtos = [];
}
?>

<div class="admin-header-bar">
    <div>
        <h1 style="font-size: 1.75rem; font-weight: 800;">Catálogo de Produtos</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Total de <?= count($produtos) ?> item(ns) cadastrado(s).</p>
    </div>
    <a href="cadastro_produto.php" class="btn btn-primary">+ Cadastrar Novo Produto</a>
</div>

<div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm);">
    <?php if (empty($produtos)): ?>
        <p style="padding: 2.5rem; text-align: center; color: var(--text-muted);">
            Nenhum produto cadastrado até o momento. <a href="cadastro_produto.php">Clique aqui para adicionar</a>.
        </p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Foto</th>
                        <th>ID</th>
                        <th>Nome do Produto</th>
                        <th>Categoria</th>
                        <th>Preço</th>
                        <th>Estoque</th>
                        <th>Status</th>
                        <th style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos as $p): 
                        // Determina o caminho correto da imagem (uploads ou assets/img)
                        $caminhoImg = '../assets/img/' . e($p['imagem']);
                        if (!empty($p['imagem']) && file_exists(__DIR__ . '/../uploads/' . $p['imagem'])) {
                            $caminhoImg = '../uploads/' . e($p['imagem']);
                        }
                    ?>
                        <tr>
                            <td>
                                <img src="<?= $caminhoImg ?>" 
                                     alt="<?= e($p['nome']) ?>" 
                                     style="width: 50px; height: 50px; object-fit: contain; background: var(--bg-surface-alt); border-radius: var(--radius-sm); padding: 2px;">
                            </td>
                            <td>#<?= (int)$p['id'] ?></td>
                            <td>
                                <strong><?= e($p['nome']) ?></strong>
                            </td>
                            <td><span style="font-size: 0.85rem; color: var(--text-muted);"><?= e($p['categoria_nome']) ?></span></td>
                            <td><strong><?= formatarPreco($p['preco']) ?></strong></td>
                            <td>
                                <?php if ($p['estoque'] == 0): ?>
                                    <span style="color: var(--danger); font-weight: 700; font-size: 0.85rem;">Esgotado (0)</span>
                                <?php elseif ($p['estoque'] <= 5): ?>
                                    <span style="color: var(--warning); font-weight: 700; font-size: 0.85rem;">Baixo (<?= (int)$p['estoque'] ?>)</span>
                                <?php else: ?>
                                    <span style="color: var(--success); font-weight: 600; font-size: 0.85rem;"><?= (int)$p['estoque'] ?> un.</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['ativo']): ?>
                                    <span style="background: var(--success-bg); color: #065f46; padding: 2px 8px; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700;">Ativo</span>
                                <?php else: ?>
                                    <span style="background: var(--danger-bg); color: #991b1b; padding: 2px 8px; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700;">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="editar_produto.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline">Editar</a>

                                <form action="listar_produtos.php" method="POST" style="display: inline-block;" onsubmit="return confirm('Deseja realmente remover ou inativar este produto?');">
                                    <?= campoCSRF() ?>
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="produto_id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
