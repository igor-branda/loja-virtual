<?php
/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: admin/admin_footer.php
 * Rodapé exclusivo da área administrativa
 * ==============================================================================
 */
?>
        </div> <!-- Fechamento da container do admin_header -->
    </main>

    <footer style="background-color: #0f172a; color: #64748b; padding: 1.5rem 0; font-size: 0.8125rem; border-top: 1px solid #1e293b; margin-top: auto;">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
            <span>TechWave Store &bull; Módulo Administrativo</span>
            <span>Autenticado como: <strong><?= e($_SESSION['admin_usuario'] ?? 'admin') ?></strong></span>
        </div>
    </footer>

    <!-- Scripts utilitários -->
    <script src="../assets/js/validacoes.js"></script>
</body>
</html>
