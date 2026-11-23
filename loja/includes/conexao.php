<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: includes/conexao.php
 * Conexão com o banco de dados MySQL/MariaDB utilizando PDO
 * ==============================================================================
 */

// Configurações de conexão padrão do ambiente XAMPP
$db_host = 'localhost';
$db_port = '3306';
$db_name = 'loja_virtual';
$db_user = 'root';
$db_pass = '';
$db_charset = 'utf8mb4';

// Data Source Name (DSN)
$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset={$db_charset}";

// Opções de configuração do PDO para segurança e integridade
$opcoes = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lança exceções em caso de erro
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Retorna arrays associativos por padrão
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Utiliza prepared statements reais do banco
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    // Instancia a conexão PDO
    $pdo = new PDO($dsn, $db_user, $db_pass, $opcoes);
} catch (PDOException $e) {
    // Registra o erro no log interno do servidor Apache/PHP
    error_log("Erro de Conexão com o Banco de Dados [loja_virtual]: " . $e->getMessage());
    
    // Não expõe detalhes sensíveis do servidor ao usuário final
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <h2 style='color:#ef4444;'>Ops! Falha ao conectar ao banco de dados.</h2>
            <p>Certifique-se de que o MySQL está ativo no XAMPP Control Panel e que o script <code>sql/banco.sql</code> foi importado no phpMyAdmin.</p>
         </div>");
}
