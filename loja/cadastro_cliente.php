<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: cadastro_cliente.php
 * Cadastro completo de novos clientes com validação e integração ViaCEP
 * ==============================================================================
 */

$titulo_pagina = 'Criar Conta de Cliente';
require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';

// Se o cliente já estiver logado, redireciona para a página inicial
if (clienteEstaLogado()) {
    header('Location: index.php');
    exit;
}

$erros = [];
$valores = [
    'nome'        => '',
    'email'       => '',
    'cpf'         => '',
    'telefone'    => '',
    'cep'         => '',
    'logradouro'  => '',
    'numero'      => '',
    'complemento' => '',
    'bairro'      => '',
    'cidade'      => '',
    'uf'          => ''
];

// Processamento do formulário de cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validação do Token CSRF
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erros[] = 'Sessão expirada ou token de segurança inválido. Tente novamente.';
    } else {
        // 2. Coleta e higienização dos campos
        $valores['nome']        = trim($_POST['nome'] ?? '');
        $valores['email']       = trim($_POST['email'] ?? '');
        $valores['cpf']         = limparNumeros($_POST['cpf'] ?? '');
        $valores['telefone']    = trim($_POST['telefone'] ?? '');
        $valores['cep']         = limparNumeros($_POST['cep'] ?? '');
        $valores['logradouro']  = trim($_POST['logradouro'] ?? '');
        $valores['numero']      = trim($_POST['numero'] ?? '');
        $valores['complemento'] = trim($_POST['complemento'] ?? '');
        $valores['bairro']      = trim($_POST['bairro'] ?? '');
        $valores['cidade']      = trim($_POST['cidade'] ?? '');
        $valores['uf']          = strtoupper(trim($_POST['uf'] ?? ''));

        $senha           = $_POST['senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';

        // 3. Validações no Servidor (Backend)
        if (empty($valores['nome']) || strlen($valores['nome']) < 3) {
            $erros[] = 'Informe seu nome completo (mínimo de 3 caracteres).';
        }

        if (empty($valores['email']) || !filter_var($valores['email'], FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'Informe um endereço de e-mail válido.';
        }

        if (empty($valores['cpf']) || !validarCpf($valores['cpf'])) {
            $erros[] = 'O CPF informado é inválido.';
        }

        if (strlen($senha) < 6) {
            $erros[] = 'A senha deve conter no mínimo 6 caracteres.';
        }

        if ($senha !== $confirmar_senha) {
            $erros[] = 'A confirmação de senha não coincide com a senha digitada.';
        }

        if (strlen($valores['cep']) !== 8) {
            $erros[] = 'O CEP deve conter 8 dígitos.';
        }

        if (empty($valores['logradouro']) || empty($valores['numero']) || empty($valores['bairro']) || empty($valores['cidade']) || empty($valores['uf'])) {
            $erros[] = 'Preencha todos os campos obrigatórios do endereço.';
        }

        // 4. Verificação de Duplicidade (E-mail e CPF)
        if (empty($erros)) {
            try {
                $stmtCheck = $pdo->prepare("SELECT id, email, cpf FROM clientes WHERE email = ? OR cpf = ? LIMIT 1");
                $stmtCheck->execute([$valores['email'], $valores['cpf']]);
                $existente = $stmtCheck->fetch();

                if ($existente) {
                    if ($existente['email'] === $valores['email']) {
                        $erros[] = 'Este e-mail já está registrado em nossa loja.';
                    }
                    if ($existente['cpf'] === $valores['cpf']) {
                        $erros[] = 'Este CPF já está cadastrado em nossa loja.';
                    }
                }
            } catch (PDOException $e) {
                error_log("Erro ao verificar duplicidade de cliente: " . $e->getMessage());
                $erros[] = 'Erro ao processar os dados. Tente novamente mais tarde.';
            }
        }

        // 5. Gravação no Banco de Dados com Hash Seguro
        if (empty($erros)) {
            try {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                $sqlInsert = "INSERT INTO clientes (nome, email, senha_hash, cpf, telefone, cep, logradouro, numero, complemento, bairro, cidade, uf)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtInsert = $pdo->prepare($sqlInsert);
                $stmtInsert->execute([
                    $valores['nome'],
                    $valores['email'],
                    $senha_hash,
                    $valores['cpf'],
                    $valores['telefone'],
                    $valores['cep'],
                    $valores['logradouro'],
                    $valores['numero'],
                    $valores['complemento'],
                    $valores['bairro'],
                    $valores['cidade'],
                    $valores['uf']
                ]);

                // Mensagem de sucesso e redirecionamento para o login
                setMensagem('sucesso', 'Cadastro concluído com sucesso! Acesse sua conta com seu e-mail e senha.');
                header('Location: login.php');
                exit;

            } catch (PDOException $e) {
                error_log("Erro ao cadastrar cliente: " . $e->getMessage());
                $erros[] = 'Ocorreu um erro ao salvar o cadastro. Por favor, tente novamente.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper" style="max-width: 680px;">
    <div class="auth-header">
        <h1 class="auth-title">Crie sua Conta</h1>
        <p class="auth-subtitle">Cadastre-se na TechWave Store para finalizar suas compras com rapidez e segurança.</p>
    </div>

    <!-- Exibição de Erros de Validação -->
    <?php if (!empty($erros)): ?>
        <div class="alert alert-erro">
            <div>
                <strong>Atenção! Verifique as informações:</strong>
                <ul style="margin-top: 0.5rem; padding-left: 1.25rem; list-style: disc;">
                    <?php foreach ($erros as $erro): ?>
                        <li><?= e($erro) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <button type="button" class="alert-close" onclick="this.parentElement.remove();">&times;</button>
        </div>
    <?php endif; ?>

    <form action="cadastro_cliente.php" method="POST" id="form-cadastro-cliente" novalidate>
        <?= campoCSRF() ?>

        <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--primary);">1. Dados Pessoais e Acesso</h3>
        
        <div class="form-group">
            <label for="nome" class="form-label">Nome Completo *</label>
            <input type="text" name="nome" id="nome" class="form-control" value="<?= e($valores['nome']) ?>" required placeholder="Ex: Carlos Alberto da Silva">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email" class="form-label">E-mail *</label>
                <input type="email" name="email" id="email" class="form-control" value="<?= e($valores['email']) ?>" required placeholder="seu@email.com">
            </div>
            <div class="form-group">
                <label for="cpf" class="form-label">CPF *</label>
                <input type="text" name="cpf" id="cpf" class="form-control" value="<?= e(formatarCpf($valores['cpf'])) ?>" required placeholder="000.000.000-00">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="telefone" class="form-label">Telefone / WhatsApp</label>
                <input type="tel" name="telefone" id="telefone" class="form-control" value="<?= e($valores['telefone']) ?>" placeholder="(91) 98000-0000">
            </div>
            <div class="form-group">
                <!-- Espaço de alinhamento -->
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="senha" class="form-label">Senha *</label>
                <input type="password" name="senha" id="senha" class="form-control" minlength="6" required placeholder="Mínimo 6 caracteres">
            </div>
            <div class="form-group">
                <label for="confirmar_senha" class="form-label">Confirmar Senha *</label>
                <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" minlength="6" required placeholder="Repita sua senha">
                <small id="erro-confirmar-senha" class="form-text-muted" style="color: var(--danger); display: none;"></small>
            </div>
        </div>

        <h3 style="font-size: 1.1rem; margin: 1.75rem 0 1rem; color: var(--primary);">2. Endereço de Entrega (ViaCEP)</h3>

        <div class="form-row">
            <div class="form-group">
                <label for="cep" class="form-label">CEP *</label>
                <input type="text" name="cep" id="cep" class="form-control" value="<?= e(formatarCep($valores['cep'])) ?>" required placeholder="00000-000">
                <small id="aviso-cep" class="form-text-muted">Digite o CEP para buscar o endereço automaticamente.</small>
            </div>
            <div class="form-group">
                <label for="uf" class="form-label">Estado (UF) *</label>
                <input type="text" name="uf" id="uf" class="form-control" value="<?= e($valores['uf']) ?>" maxlength="2" required placeholder="Ex: PA, SP, RJ">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group" style="grid-column: span 1;">
                <label for="cidade" class="form-label">Cidade *</label>
                <input type="text" name="cidade" id="cidade" class="form-control" value="<?= e($valores['cidade']) ?>" required placeholder="Cidade">
            </div>
            <div class="form-group" style="grid-column: span 1;">
                <label for="bairro" class="form-label">Bairro *</label>
                <input type="text" name="bairro" id="bairro" class="form-control" value="<?= e($valores['bairro']) ?>" required placeholder="Bairro">
            </div>
        </div>

        <div class="form-group">
            <label for="logradouro" class="form-label">Logradouro (Rua, Avenida) *</label>
            <input type="text" name="logradouro" id="logradouro" class="form-control" value="<?= e($valores['logradouro']) ?>" required placeholder="Rua / Avenida">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="numero" class="form-label">Número *</label>
                <input type="text" name="numero" id="numero" class="form-control" value="<?= e($valores['numero']) ?>" required placeholder="Número">
            </div>
            <div class="form-group">
                <label for="complemento" class="form-label">Complemento</label>
                <input type="text" name="complemento" id="complemento" class="form-control" value="<?= e($valores['complemento']) ?>" placeholder="Apto, Bloco, Sala...">
            </div>
        </div>

        <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary btn-block" style="padding: 0.85rem; font-size: 1rem;">
                Concluir Cadastro
            </button>
        </div>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
            Já possui uma conta? <a href="login.php" style="font-weight: 600;">Faça Login aqui</a>.
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
