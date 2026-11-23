<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: login.php
 * Autenticação de clientes com segurança PDO, password_verify e CSRF
 * ==============================================================================
 */

$titulo_pagina = 'Entrar na sua Conta';
require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';

// Se já estiver logado, redireciona para a página principal
if (clienteEstaLogado()) {
    header('Location: index.php');
    exit;
}

$erro = '';
$email_digitado = '';
$url_retorno = $_GET['retorno'] ?? 'index.php';

// Processamento da tentativa de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validação CSRF
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erro = 'Sessão expirada ou requisição inválida. Tente novamente.';
    } else {
        $email_digitado = trim($_POST['email'] ?? '');
        $senha_digitada = $_POST['senha'] ?? '';
        $url_retorno    = $_POST['retorno'] ?? 'index.php';

        if (empty($email_digitado) || empty($senha_digitada)) {
            $erro = 'Preencha o e-mail e a senha de acesso.';
        } else {
            try {
                // 2. Consulta segura com Prepared Statement
                $stmt = $pdo->prepare("SELECT id, nome, email, senha_hash FROM clientes WHERE email = ? LIMIT 1");
                $stmt->execute([$email_digitado]);
                $cliente = $stmt->fetch();

                // 3. Validação da senha criptografada com password_verify
                if ($cliente && password_verify($senha_digitada, $cliente['senha_hash'])) {
                    // Prevenção contra fixação de sessão
                    session_regenerate_id(true);

                    // Grava os dados do cliente na sessão
                    $_SESSION['cliente_id']    = $cliente['id'];
                    $_SESSION['cliente_nome']  = $cliente['nome'];
                    $_SESSION['cliente_email'] = $cliente['email'];

                    setMensagem('sucesso', 'Bem-vindo de volta, ' . htmlspecialchars($cliente['nome']) . '!');
                    
                    // Sanitiza a URL de retorno para evitar redirecionamento aberto
                    $destino = (str_starts_with($url_retorno, '/') || !str_contains($url_retorno, '://')) 
                        ? $url_retorno 
                        : 'index.php';

                    header("Location: {$destino}");
                    exit;

                } else {
                    $erro = 'E-mail ou senha incorretos. Verifique suas credenciais.';
                }

            } catch (PDOException $e) {
                error_log("Erro no login de cliente: " . $e->getMessage());
                $erro = 'Ocorreu um erro no servidor. Tente novamente em alguns instantes.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-header">
        <h1 class="auth-title">Acesse sua Conta</h1>
        <p class="auth-subtitle">Informe seu e-mail e senha para gerenciar pedidos e finalizar compras.</p>
    </div>

    <?php if (!empty($erro)): ?>
        <div class="alert alert-erro">
            <span><?= e($erro) ?></span>
            <button type="button" class="alert-close" onclick="this.parentElement.remove();">&times;</button>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST" novalidate>
        <?= campoCSRF() ?>
        <input type="hidden" name="retorno" value="<?= e($url_retorno) ?>">

        <div class="form-group">
            <label for="email" class="form-label">E-mail Cadastrado</label>
            <input type="email" 
                   name="email" 
                   id="email" 
                   class="form-control" 
                   value="<?= e($email_digitado) ?>" 
                   required 
                   autofocus 
                   placeholder="seu@email.com">
        </div>

        <div class="form-group">
            <label for="senha" class="form-label">Senha</label>
            <input type="password" 
                   name="senha" 
                   id="senha" 
                   class="form-control" 
                   required 
                   placeholder="Sua senha secreta">
        </div>

        <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary btn-block" style="padding: 0.85rem; font-size: 1rem;">
                Entrar na Loja
            </button>
        </div>

        <div style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem; color: var(--text-muted);">
            Ainda não tem conta? 
            <a href="cadastro_cliente.php" style="font-weight: 600;">Cadastre-se gratuitamente</a>.
        </div>

        <!-- Dica de teste acadêmico -->
        <div style="margin-top: 1.5rem; padding: 0.75rem; background: var(--bg-surface-alt); border-radius: var(--radius-md); font-size: 0.8rem; color: var(--text-muted); text-align: center;">
            <strong>Dados de teste inicial:</strong><br>
            E-mail: <code>carlos@email.com</code> | Senha: <code>cliente123</code>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
