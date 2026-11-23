<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: admin/editar_produto.php
 * Edição completa dos dados de produtos, estoque e substituição de imagem
 * ==============================================================================
 */

$titulo_pagina = 'Editar Produto';
require_once __DIR__ . '/admin_header.php';

$produto_id = (int)($_GET['id'] ?? 0);

if ($produto_id <= 0) {
    setMensagem('erro', 'ID de produto inválido.');
    header('Location: listar_produtos.php');
    exit;
}

// Busca os dados atuais do produto
try {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$produto_id]);
    $produto = $stmt->fetch();

    if (!$produto) {
        setMensagem('erro', 'Produto não localizado.');
        header('Location: listar_produtos.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao carregar produto: " . $e->getMessage());
    setMensagem('erro', 'Falha ao buscar informações do produto.');
    header('Location: listar_produtos.php');
    exit;
}

$categorias = getCategorias($pdo);
$erros = [];

// Dados para os campos
$nome         = $produto['nome'];
$descricao    = $produto['descricao'];
$categoria_id = $produto['categoria_id'];
$preco        = number_format((float)$produto['preco'], 2, ',', '.');
$estoque      = $produto['estoque'];
$ativo        = $produto['ativo'];
$imagem_atual = $produto['imagem'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erros[] = 'Sessão expirada. Tente novamente.';
    } else {
        $nome         = trim($_POST['nome'] ?? '');
        $descricao    = trim($_POST['descricao'] ?? '');
        $categoria_id = (int)($_POST['categoria_id'] ?? 0);
        $preco_raw    = trim($_POST['preco'] ?? '');
        $estoque_raw  = trim($_POST['estoque'] ?? '');
        $ativo        = isset($_POST['ativo']) ? 1 : 0;

        // Normalização de preço
        $preco_limpo = str_replace(['R$', ' '], '', $preco_raw);
        if (str_contains($preco_limpo, ',') && str_contains($preco_limpo, '.')) {
            $preco_limpo = str_replace('.', '', $preco_limpo);
            $preco_limpo = str_replace(',', '.', $preco_limpo);
        } else {
            $preco_limpo = str_replace(',', '.', $preco_limpo);
        }
        $preco_val = (float)$preco_limpo;
        $estoque_val = (int)$estoque_raw;

        if (empty($nome) || strlen($nome) < 3) {
            $erros[] = 'Informe um nome válido para o produto.';
        }
        if ($categoria_id <= 0) {
            $erros[] = 'Selecione uma categoria válida.';
        }
        if ($preco_val <= 0) {
            $erros[] = 'O preço deve ser superior a zero.';
        }
        if ($estoque_val < 0) {
            $erros[] = 'O estoque não pode ser negativo.';
        }

        // Upload de nova imagem (opcional na edição)
        $novo_nome_imagem = $imagem_atual;

        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $arquivo = $_FILES['imagem'];
            $tamMaximo = 2 * 1024 * 1024;
            $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
            $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

            if ($arquivo['size'] > $tamMaximo) {
                $erros[] = 'A nova imagem ultrapassa o limite de 2 MB.';
            } elseif (!in_array($extensao, $extensoesPermitidas)) {
                $erros[] = 'Formato inválido. Use JPG, PNG, WEBP ou SVG.';
            } else {
                $novo_nome_imagem = uniqid('prod_', true) . '.' . $extensao;
                $destino = __DIR__ . '/../uploads/' . $novo_nome_imagem;

                if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
                    $erros[] = 'Falha ao salvar a nova imagem no servidor.';
                } else {
                    // Remove imagem antiga se estava em uploads/
                    if ($imagem_atual && file_exists(__DIR__ . '/../uploads/' . $imagem_atual)) {
                        @unlink(__DIR__ . '/../uploads/' . $imagem_atual);
                    }
                }
            }
        }

        // Atualização dos dados
        if (empty($erros)) {
            try {
                $sqlUpdate = "UPDATE produtos 
                              SET categoria_id = ?, nome = ?, descricao = ?, preco = ?, estoque = ?, imagem = ?, ativo = ?
                              WHERE id = ?";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    $categoria_id,
                    $nome,
                    $descricao,
                    $preco_val,
                    $estoque_val,
                    $novo_nome_imagem,
                    $ativo,
                    $produto_id
                ]);

                setMensagem('sucesso', "Produto #{$produto_id} atualizado com sucesso!");
                header('Location: listar_produtos.php');
                exit;

            } catch (PDOException $e) {
                error_log("Erro ao atualizar produto: " . $e->getMessage());
                $erros[] = 'Erro ao salvar alterações no banco de dados.';
            }
        }
    }
}

// Determina imagem atual para exibição
$caminhoImgAtual = '../assets/img/' . e($imagem_atual);
if (!empty($imagem_atual) && file_exists(__DIR__ . '/../uploads/' . $imagem_atual)) {
    $caminhoImgAtual = '../uploads/' . e($imagem_atual);
}
?>

<div class="admin-header-bar">
    <div>
        <h1 style="font-size: 1.75rem; font-weight: 800;">Editar Produto #<?= (int)$produto_id ?></h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Modifique as características, preços e estoque deste produto.</p>
    </div>
    <a href="listar_produtos.php" class="btn btn-outline">&larr; Voltar para a Lista</a>
</div>

<?php if (!empty($erros)): ?>
    <div class="alert alert-erro">
        <div>
            <strong>Erros ao atualizar:</strong>
            <ul style="margin-top: 0.5rem; padding-left: 1.25rem;">
                <?php foreach ($erros as $erro): ?>
                    <li><?= e($erro) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <button type="button" class="alert-close" onclick="this.parentElement.remove();">&times;</button>
    </div>
<?php endif; ?>

<div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 2rem;">
    <form action="editar_produto.php?id=<?= (int)$produto_id ?>" method="POST" enctype="multipart/form-data" novalidate>
        <?= campoCSRF() ?>

        <div class="form-row">
            <div class="form-group" style="grid-column: span 2;">
                <label for="nome" class="form-label">Nome do Produto *</label>
                <input type="text" name="nome" id="nome" class="form-control" value="<?= e($nome) ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="categoria_id" class="form-label">Categoria *</label>
                <select name="categoria_id" id="categoria_id" class="form-control" required>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoria_id == $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="preco" class="form-label">Preço Unitário (R$) *</label>
                <input type="text" name="preco" id="preco" class="form-control" value="<?= e($preco) ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="estoque" class="form-label">Quantidade em Estoque *</label>
                <input type="number" name="estoque" id="estoque" class="form-control" min="0" value="<?= e($estoque) ?>" required>
            </div>

            <div class="form-group">
                <label for="imagem" class="form-label">Trocar Imagem (Opcional)</label>
                <input type="file" name="imagem" id="imagem" class="form-control" accept="image/jpeg,image/png,image/webp,image/svg+xml">
                <small class="form-text-muted">Deixe em branco para manter a imagem atual.</small>
            </div>
        </div>

        <!-- Exibição da Imagem Atual -->
        <div style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--bg-surface-alt); border-radius: var(--radius-md);">
            <img src="<?= $caminhoImgAtual ?>" alt="Foto atual" style="width: 70px; height: 70px; object-fit: contain; background: #fff; border-radius: var(--radius-sm); padding: 4px;">
            <div>
                <strong>Imagem cadastrada atualmente:</strong>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;"><?= e($imagem_atual ?: 'Sem imagem') ?></p>
            </div>
        </div>

        <div class="form-group">
            <label for="descricao" class="form-label">Descrição Detalhada do Produto</label>
            <textarea name="descricao" id="descricao" class="form-control" rows="4"><?= e($descricao) ?></textarea>
        </div>

        <div class="form-group" style="margin-top: 1rem;">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 600;">
                <input type="checkbox" name="ativo" value="1" <?= $ativo ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                <span>Produto ativo e visível na vitrine</span>
            </label>
        </div>

        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.85rem 2rem;">Salvar Alterações</button>
            <a href="listar_produtos.php" class="btn btn-outline" style="padding: 0.85rem 1.5rem;">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
