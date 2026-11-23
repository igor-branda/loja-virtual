<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: logout.php
 * Encerramento seguro da sessão do cliente
 * ==============================================================================
 */

require_once __DIR__ . '/includes/funcoes.php';

// Remove as variáveis de autenticação do cliente
unset($_SESSION['cliente_id']);
unset($_SESSION['cliente_nome']);
unset($_SESSION['cliente_email']);

// Define mensagem de despedida amigável
setMensagem('info', 'Sua sessão foi encerrada com sucesso. Volte sempre!');

// Redireciona para a vitrine
header('Location: index.php');
exit;
