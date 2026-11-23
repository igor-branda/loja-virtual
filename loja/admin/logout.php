<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: admin/logout.php
 * Encerramento da sessão de administrador
 * ==============================================================================
 */

require_once __DIR__ . '/../includes/funcoes.php';

unset($_SESSION['admin_id']);
unset($_SESSION['admin_usuario']);

setMensagem('info', 'Sessão administrativa encerrada com sucesso.');
header('Location: login.php');
exit;
