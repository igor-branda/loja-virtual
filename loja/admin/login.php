<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: admin/login.php
 * Autenticação do painel de administração da loja
 * ==============================================================================
 */

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

// Se o administrador já estiver logado, redireciona diretamente para o painel
if (adminEstaLogado()) {
    header('Location: index.php');
    exit;
}

$erro = '';
$usuario_digitado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erro = 'Sessão expirada. Tente novamente.';
    } else {
        $usuario_digitado = trim($_POST['usuario'] ?? '');
        $senha_digitada   = $_POST['senha'] ?? '';

        if (empty($usuario_digitado) || empty($senha_digitada)) {
            $erro = 'Informe o usuário e a senha de administrador.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, usuario, senha_hash FROM administradores WHERE usuario = ? LIMIT 1");
                $stmt->execute([$usuario_digitado]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($senha_digitada, $admin['senha_hash'])) {
                    session_regenerate_id(true);

                    $_SESSION['admin_id']      = $admin['id'];
                    $_SESSION['admin_usuario'] = $admin['usuario'];

                    setMensagem('sucesso', 'Login administrativo efetuado com sucesso!');
                    header('Location: index.php');
                    exit;
                } else {
                    $erro = 'Usuário ou senha de administrador inválidos.';
                }
            } catch (PDOException $e) {
                error_log("Erro no login admin: " . $e->getMessage());
                $erro = 'Erro interno do servidor ao processar autenticação.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Administrador | TechWave Store</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background-color: #0f172a; display: flex; align-items: center; justify-content: center; min-height: 100vh;">

    <div class="auth-wrapper" style="width: 100%; max-width: 440px; margin: 2rem; border-color: #334155;">
        <div class="auth-header">
            <div style="display: flex; justify-content: center; margin-bottom: 1rem;">
                <img src="../assets/img/logo.svg" alt="TechWave Store" width="190" height="45">
            </div>
            <h1 class="auth-title" style="font-size: 1.5rem;">Acesso Administrativo</h1>
            <p class="auth-subtitle">Gestão de catálogo, pedidos e relatórios de vendas.</p>
        </div>

        <?= renderMensagens() ?>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-erro">
                <span><?= e($erro) ?></span>
                <button type="button" class="alert-close" onclick="this.parentElement.remove();">&times;</button>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" novalidate>
            <?= campoCSRF() ?>

            <div class="form-group">
                <label for="usuario" class="form-label">Usuário Administrador</label>
                <input type="text" 
                       name="usuario" 
                       id="usuario" 
                       class="form-control" 
                       value="<?= e($usuario_digitado) ?>" 
                       required 
                       autofocus 
                       placeholder="Ex: admin">
            </div>

            <div class="form-group">
                <label for="senha" class="form-label">Senha de Acesso</label>
                <input type="password" 
                       name="senha" 
                       id="senha" 
                       class="form-control" 
                       required 
                       placeholder="Sua senha administrativa">
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary btn-block" style="padding: 0.85rem;">
                    Acessar Painel
                </button>
            </div>

            <div style="margin-top: 1.5rem; text-align: center;">
                <a href="../index.php" style="color: var(--text-muted); font-size: 0.875rem;">
                    &larr; Voltar para a Loja Virtual
                </a>
            </div>

            <div style="margin-top: 1.5rem; padding: 0.75rem; background: var(--bg-surface-alt); border-radius: var(--radius-md); font-size: 0.8rem; color: var(--text-muted); text-align: center;">
                <strong>Credenciais de Administrador de Teste:</strong><br>
                Usuário: <code>admin</code> | Senha: <code>admin123</code>
            </div>
        </form>
    </div>

</body>
</html>
