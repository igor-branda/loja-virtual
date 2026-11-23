<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: includes/footer.php
 * Rodapé padrão e importação dos scripts JavaScript
 * ==============================================================================
 */

if (!isset($caminho_base)) {
    $caminho_base = '';
}
?>
    </main> <!-- Fechamento da tag main-content aberta no header.php -->

    <!-- Toast de Notificações Assíncronas (Feedback visual de AJAX) -->
    <div id="toast-notification" class="toast-notification" role="status" aria-live="polite"></div>

    <!-- Rodapé Principal da Loja -->
    <footer class="site-footer">
        <div class="container footer-grid">
            <!-- Coluna 1: Sobre a Loja -->
            <div class="footer-col">
                <div class="footer-brand">
                    <img src="<?= $caminho_base ?>assets/img/logo.svg" alt="TechWave Store" width="180" height="42">
                </div>
                <p class="footer-description">
                    Sua loja virtual de referência em tecnologia, inovação e eletrônicos de alta performance. Entregamos qualidade com agilidade e total segurança para todo o Brasil.
                </p>
                <div class="security-badges">
                    <span class="badge-pill">Compra 100% Segura</span>
                    <span class="badge-pill">Certificado SSL 256-bit</span>
                </div>
            </div>

            <!-- Coluna 2: Navegação e Institucional -->
            <div class="footer-col">
                <h3 class="footer-title">Institucional</h3>
                <ul class="footer-links">
                    <li><a href="<?= $caminho_base ?>index.php">Início / Vitrine</a></li>
                    <li><a href="<?= $caminho_base ?>meus_pedidos.php">Meus Pedidos</a></li>
                    <li><a href="<?= $caminho_base ?>carrinho.php">Carrinho de Compras</a></li>
                    <li><a href="<?= $caminho_base ?>admin/login.php">Painel Administrativo</a></li>
                </ul>
            </div>

            <!-- Coluna 3: Formas de Pagamento e Envio -->
            <div class="footer-col">
                <h3 class="footer-title">Pagamento & Entrega</h3>
                <p class="footer-text">Aceitamos as principais formas de pagamento:</p>
                <div class="payment-methods">
                    <span class="pay-tag pix">Pix (Aprovação Instantânea)</span>
                    <span class="pay-tag card">Cartão de Crédito em até 12x</span>
                    <span class="pay-tag boleto">Boleto Bancário</span>
                </div>
                <h4 class="footer-subtitle">Política de Frete:</h4>
                <p class="footer-text text-sm">
                    Frete Grátis nas compras acima de R$ 200,00. Cálculo automatizado por região via integração <strong>ViaCEP</strong>.
                </p>
            </div>

            <!-- Coluna 4: Informações do Projeto -->
            <div class="footer-col">
                <h3 class="footer-title">Projeto Acadêmico</h3>
                <p class="footer-text">
                    Desenvolvimento Web Full Stack com HTML5 semântico, CSS3 responsivo, JavaScript puro (ES6+), PHP 8 (PDO) e banco de dados MySQL/MariaDB.
                </p>
                <div class="tech-stack-tags">
                    <span class="tech-tag">HTML5</span>
                    <span class="tech-tag">CSS3</span>
                    <span class="tech-tag">JavaScript</span>
                    <span class="tech-tag">PHP 8 PDO</span>
                    <span class="tech-tag">MySQL</span>
                </div>
            </div>
        </div>

        <!-- Barra Inferior de Direitos Autorais -->
        <div class="footer-bottom">
            <div class="container footer-bottom-content">
                <p>&copy; <?= date('Y') ?> TechWave Store. Todos os direitos reservados. Projeto Final de Web Coding.</p>
                <p class="footer-credits">Ambiente XAMPP • phpMyAdmin • API ViaCEP</p>
            </div>
        </div>
    </footer>

    <!-- Inclusão dos Scripts JavaScript Globais -->
    <script src="<?= $caminho_base ?>assets/js/validacoes.js"></script>
    <script src="<?= $caminho_base ?>assets/js/carrinho.js"></script>
    <script src="<?= $caminho_base ?>assets/js/cep.js"></script>

</body>
</html>
