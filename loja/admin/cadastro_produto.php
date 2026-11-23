<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: admin/cadastro_produto.php
 * Cadastro de novos produtos com validação de dados e upload seguro de imagens
 * ==============================================================================
 */

$titulo_pagina = 'Cadastrar Novo Produto';
require_once __DIR__ . '/admin_header.php';

// Busca as categorias existentes para o select
$categorias = getCategorias($pdo);

$erros = [];
$nome = '';
$descricao = '';
$categoria_id = '';
$preco = '';
$estoque = '10';
$ativo = '1';

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

        // Normaliza o preço (aceita 1299,90 ou 1299.90)
        $preco_limpo = str_replace(['R$', ' '], '', $preco_raw);
        if (str_contains($preco_limpo, ',') && str_contains($preco_limpo, '.')) {
            $preco_limpo = str_replace('.', '', $preco_limpo);
            $preco_limpo = str_replace(',', '.', $preco_limpo);
        } else {
            $preco_limpo = str_replace(',', '.', $preco_limpo);
        }
        $preco_val = (float)$preco_limpo;
        $estoque_val = (int)$estoque_raw;

        // Validações
        if (empty($nome) || strlen($nome) < 3) {
            $erros[] = 'Informe um nome para o produto com pelo menos 3 caracteres.';
        }

        if ($categoria_id <= 0) {
            $erros[] = 'Selecione uma categoria válida.';
        }

        if ($preco_val <= 0) {
            $erros[] = 'Informe um preço válido maior que zero.';
        }

        if ($estoque_val < 0) {
            $erros[] = 'O estoque não pode ser negativo.';
        }

        // Processamento de Upload da Imagem
        $nome_imagem_final = null;

        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $arquivo = $_FILES['imagem'];
            $tamMaximo = 2 * 1024 * 1024; // 2MB
            $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp', 'svg'];

            $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

            if ($arquivo['size'] > $tamMaximo) {
                $erros[] = 'A imagem enviada ultrapassa o limite permitido de 2 MB.';
            } elseif (!in_array($extensao, $extensoesPermitidas)) {
                $erros[] = 'Formato de imagem inválido. Formatos permitidos: JPG, PNG, WEBP ou SVG.';
            } else {
                // Gera nome único seguro
                $nome_imagem_final = uniqid('prod_', true) . '.' . $extensao;
                $caminhoDestino = __DIR__ . '/../uploads/' . $nome_imagem_final;

                if (!move_uploaded_file($arquivo['tmp_name'], $caminhoDestino)) {
                    $erros[] = 'Falha ao salvar a imagem no servidor. Verifique as permissões da pasta uploads/.';
                }
            }
        }

        // Se não houve erros, insere no banco
        if (empty($erros)) {
            try {
                $sql = "INSERT INTO produtos (categoria_id, nome, descricao, preco, estoque, imagem, ativo)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $categoria_id,
                    $nome,
                    $descricao,
                    $preco_val,
                    $estoque_val,
                    $nome_imagem_final,
                    $ativo
                ]);

                setMensagem('sucesso', "Produto '{$nome}' cadastrado com sucesso!");
                header('Location: listar_produtos.php');
                exit;

            } catch (PDOException $e) {
                error_log("Erro ao cadastrar produto: " . $e->getMessage());
                $erros[] = 'Erro ao registrar o produto no banco de dados.';
            }
        }
    }
}
?>

<div class="admin-header-bar">
    <div>
        <h1 style="font-size: 1.75rem; font-weight: 800;">Cadastro de Produto</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Adicione uma nova mercadoria ao catálogo da TechWave Store.</p>
    </div>
    <a href="listar_produtos.php" class="btn btn-outline">&larr; Voltar para a Lista</a>
</div>

<?php if (!empty($erros)): ?>
    <div class="alert alert-erro">
        <div>
            <strong>Erros no formulário:</strong>
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
    <form action="cadastro_produto.php" method="POST" enctype="multipart/form-data" novalidate>
        <?= campoCSRF() ?>

        <div class="form-row">
            <div class="form-group" style="grid-column: span 2;">
                <label for="nome" class="form-label">Nome do Produto *</label>
                <input type="text" name="nome" id="nome" class="form-control" value="<?= e($nome) ?>" required placeholder="Ex: Console PlayStation 5 Slim 1TB">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="categoria_id" class="form-label">Categoria *</label>
                <select name="categoria_id" id="categoria_id" class="form-control" required>
                    <option value="">Selecione uma categoria...</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoria_id == $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="preco" class="form-label">Preço Unitário (R$) *</label>
                <input type="text" name="preco" id="preco" class="form-control" value="<?= e($preco) ?>" required placeholder="Ex: 3.499,90">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="estoque" class="form-label">Quantidade em Estoque *</label>
                <input type="number" name="estoque" id="estoque" class="form-control" min="0" value="<?= e($estoque) ?>" required>
            </div>

            <div class="form-group">
                <label for="imagem" class="form-label">Foto do Produto (Upload)</label>
                <input type="file" name="imagem" id="imagem" class="form-control" accept="image/jpeg,image/png,image/webp,image/svg+xml">
                <small class="form-text-muted">Formatos: JPG, PNG, WEBP ou SVG (Máx. 2MB).</small>
            </div>
        </div>

        <div class="form-group">
            <label for="descricao" class="form-label">Descrição Detalhada do Produto</label>
            <textarea name="descricao" id="descricao" class="form-control" rows="4" placeholder="Especificações técnicas, dimensões, conectividade e diferenciais..."><?= e($descricao) ?></textarea>
        </div>

        <div class="form-group" style="margin-top: 1rem;">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 600;">
                <input type="checkbox" name="ativo" value="1" <?= $ativo ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                <span>Produto ativo e visível na vitrine da loja</span>
            </label>
        </div>

        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.85rem 2rem;">Salvar Produto</button>
            <a href="listar_produtos.php" class="btn btn-outline" style="padding: 0.85rem 1.5rem;">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
