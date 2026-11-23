<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: includes/funcoes.php
 * Funções utilitárias: Sessão, Segurança (CSRF/XSS), Autenticação, Formatações
 * ==============================================================================
 */

// Inicia a sessão com segurança se ainda não tiver sido iniciada
if (session_status() === PHP_SESSION_NONE) {
    // Configurações de cookies de sessão seguros
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

/**
 * Escapa strings contra ataques Cross-Site Scripting (XSS).
 * 
 * @param mixed $valor Valor a ser sanitizado
 * @return string Texto escapado seguro para saída HTML
 */
function e($valor): string {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Gera ou recupera o token CSRF da sessão atual.
 * 
 * @return string Token CSRF
 */
function gerarTokenCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Retorna o input oculto contendo o token CSRF pronto para formulários.
 * 
 * @return string HTML com input hidden csrf_token
 */
function campoCSRF(): string {
    $token = gerarTokenCSRF();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Valida o token CSRF enviado via requisição POST.
 * 
 * @param string|null $tokenEnviado
 * @return bool True se válido, False caso contrário
 */
function validarTokenCSRF(?string $tokenEnviado): bool {
    if (empty($_SESSION['csrf_token']) || empty($tokenEnviado)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $tokenEnviado);
}

/**
 * Define uma mensagem temporária (Flash Message) para exibição na interface.
 * 
 * @param string $tipo 'sucesso', 'erro', 'aviso' ou 'info'
 * @param string $texto Mensagem a ser exibida
 */
function setMensagem(string $tipo, string $texto): void {
    if (!isset($_SESSION['mensagens'])) {
        $_SESSION['mensagens'] = [];
    }
    $_SESSION['mensagens'][] = [
        'tipo'  => $tipo,
        'texto' => $texto
    ];
}

/**
 * Renderiza e limpa as mensagens flash pendentes da sessão.
 * 
 * @return string HTML das caixas de alerta
 */
function renderMensagens(): string {
    if (empty($_SESSION['mensagens'])) {
        return '';
    }

    $html = '<div class="alert-container">';
    foreach ($_SESSION['mensagens'] as $msg) {
        $tipoClasse = 'alert-' . e($msg['tipo']);
        $html .= '<div class="alert ' . $tipoClasse . '">';
        $html .= '<span>' . e($msg['texto']) . '</span>';
        $html .= '<button type="button" class="alert-close" onclick="this.parentElement.remove();">&times;</button>';
        $html .= '</div>';
    }
    $html .= '</div>';

    // Limpa as mensagens após renderizar
    unset($_SESSION['mensagens']);

    return $html;
}

/**
 * Formata um valor numérico para o padrão de moeda brasileira (Real - R$).
 * 
 * @param float|string $valor
 * @return string Exemplo: "R$ 1.299,00"
 */
function formatarPreco($valor): string {
    $num = (float)$valor;
    return 'R$ ' . number_format($num, 2, ',', '.');
}

/**
 * Formata uma data/hora vinda do MySQL (Y-m-d H:i:s) para o padrão brasileiro.
 * 
 * @param string|null $data
 * @param bool $incluirHora
 * @return string Exemplo: "20/11/2026 14:35"
 */
function formatarData(?string $data, bool $incluirHora = true): string {
    if (!$data) return '-';
    $formato = $incluirHora ? 'd/m/Y H:i' : 'd/m/Y';
    $timestamp = strtotime($data);
    return $timestamp ? date($formato, $timestamp) : $data;
}

/**
 * Limpa pontuações de CPF, CEP e telefones mantendo apenas números.
 * 
 * @param string|null $doc
 * @return string String com apenas números
 */
function limparNumeros(?string $doc): string {
    return preg_replace('/\D/', '', (string)$doc);
}

/**
 * Formata um CPF contendo 11 dígitos para o padrão 000.000.000-00.
 * 
 * @param string|null $cpf
 * @return string
 */
function formatarCpf(?string $cpf): string {
    $limpo = limparNumeros($cpf);
    if (strlen($limpo) !== 11) return (string)$cpf;
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $limpo);
}

/**
 * Formata um CEP contendo 8 dígitos para o padrão 00000-000.
 * 
 * @param string|null $cep
 * @return string
 */
function formatarCep(?string $cep): string {
    $limpo = limparNumeros($cep);
    if (strlen($limpo) !== 8) return (string)$cep;
    return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $limpo);
}

/**
 * Valida a autenticidade de um CPF com cálculo dos dois dígitos verificadores.
 * 
 * @param string $cpf
 * @return bool True se o CPF for válido
 */
function validarCpf(string $cpf): bool {
    $cpf = limparNumeros($cpf);

    if (strlen($cpf) !== 11) {
        return false;
    }

    // Rejeita sequências repetidas conhecidas (11111111111, etc.)
    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    // Cálculo do primeiro dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += (int)$cpf[$i] * (10 - $i);
    }
    $resto = $soma % 11;
    $digito1 = ($resto < 2) ? 0 : 11 - $resto;
    if ((int)$cpf[9] !== $digito1) {
        return false;
    }

    // Cálculo do segundo dígito verificador
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += (int)$cpf[$i] * (11 - $i);
    }
    $resto = $soma % 11;
    $digito2 = ($resto < 2) ? 0 : 11 - $resto;
    if ((int)$cpf[10] !== $digito2) {
        return false;
    }

    return true;
}

/**
 * Verifica se um cliente está autenticado na sessão.
 * 
 * @return bool
 */
function clienteEstaLogado(): bool {
    return !empty($_SESSION['cliente_id']);
}

/**
 * Retorna os dados do cliente atualmente autenticado.
 * 
 * @return array|null Dados do cliente ou null
 */
function getClienteLogado(): ?array {
    if (!clienteEstaLogado()) {
        return null;
    }
    return [
        'id'    => $_SESSION['cliente_id'],
        'nome'  => $_SESSION['cliente_nome'] ?? 'Cliente',
        'email' => $_SESSION['cliente_email'] ?? ''
    ];
}

/**
 * Garante que a página só seja acessada por um cliente autenticado.
 * Redireciona para login.php se não estiver autenticado.
 * 
 * @param string $redirecionarPara Página para retorno após login
 */
function exigirLoginCliente(string $redirecionarPara = 'login.php'): void {
    if (!clienteEstaLogado()) {
        setMensagem('aviso', 'Por favor, entre em sua conta para continuar.');
        header("Location: {$redirecionarPara}");
        exit;
    }
}

/**
 * Verifica se um administrador está autenticado na sessão.
 * 
 * @return bool
 */
function adminEstaLogado(): bool {
    return !empty($_SESSION['admin_id']);
}

/**
 * Garante que a página só seja acessada por um administrador.
 * 
 * @param string $redirecionarPara URL de login administrativo
 */
function exigirLoginAdmin(string $redirecionarPara = 'login.php'): void {
    if (!adminEstaLogado()) {
        setMensagem('erro', 'Acesso restrito a administradores. Faça login primeiro.');
        header("Location: {$redirecionarPara}");
        exit;
    }
}

/**
 * Retorna a quantidade total de itens presentes no carrinho da sessão.
 * 
 * @return int Total de unidades
 */
function contarItensCarrinho(): int {
    if (empty($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
        return 0;
    }
    return array_sum($_SESSION['carrinho']);
}

/**
 * Retorna todas as categorias ativas para exibição em menus e filtros.
 * 
 * @param PDO $pdo
 * @return array Lista de categorias
 */
function getCategorias(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao buscar categorias: " . $e->getMessage());
        return [];
    }
}
