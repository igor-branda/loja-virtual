/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: assets/js/carrinho.js
 * Manipulação assíncrona do carrinho via API (fetch), Toast e atualização de DOM
 * ==============================================================================
 */

// Função global para exibir notificações Toast
function mostrarToast(mensagem, tipo = 'sucesso') {
    const toast = document.getElementById('toast-notification');
    if (!toast) return;

    toast.textContent = mensagem;
    toast.className = `toast-notification show ${tipo}`;

    setTimeout(() => {
        toast.className = 'toast-notification';
    }, 3500);
}

// Atualiza o contador de itens no header
function atualizarBadgeCarrinho(totalItens) {
    const badge = document.getElementById('cart-count');
    if (!badge) return;

    badge.textContent = totalItens;
    if (totalItens > 0) {
        badge.classList.add('visible');
    } else {
        badge.classList.remove('visible');
    }
}

document.addEventListener('DOMContentLoaded', () => {

    // --------------------------------------------------------------------------
    // 1. ADICIONAR PRODUTO AO CARRINHO (Botão da Vitrine e Detalhe)
    // --------------------------------------------------------------------------
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-adicionar-carrinho');
        if (!btn) return;

        const produtoId = btn.getAttribute('data-id');
        if (!produtoId) return;

        let quantidade = 1;
        const seletorQtd = btn.getAttribute('data-qtd-input');
        if (seletorQtd) {
            const inputQtd = document.querySelector(seletorQtd);
            if (inputQtd) {
                quantidade = parseInt(inputQtd.value, 10) || 1;
            }
        }

        const textoOriginal = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Adicionando...';

        try {
            const resposta = await fetch('api/carrinho.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    acao: 'adicionar',
                    produto_id: produtoId,
                    quantidade: quantidade
                })
            });

            const dados = await resposta.json();

            if (dados.sucesso) {
                atualizarBadgeCarrinho(dados.total_itens);
                mostrarToast(dados.mensagem, 'sucesso');
            } else {
                mostrarToast(dados.mensagem || 'Não foi possível adicionar o produto.', 'erro');
            }

        } catch (erro) {
            console.error('Erro na requisição do carrinho:', erro);
            mostrarToast('Erro ao comunicar com o servidor.', 'erro');
        } finally {
            btn.disabled = false;
            btn.innerHTML = textoOriginal;
        }
    });

    // --------------------------------------------------------------------------
    // 2. AÇÕES DENTRO DA PÁGINA DO CARRINHO (carrinho.php)
    // --------------------------------------------------------------------------
    const tabelaCarrinho = document.querySelector('#tabela-carrinho');
    if (tabelaCarrinho) {

        // Aumentar quantidade
        tabelaCarrinho.addEventListener('click', async (e) => {
            const btnPlus = e.target.closest('.btn-cart-plus');
            if (btnPlus) {
                const tr = btnPlus.closest('tr');
                const produtoId = tr.getAttribute('data-id');
                const inputQtd = tr.querySelector('.input-cart-qtd');
                const qtdAtual = parseInt(inputQtd.value, 10) || 1;
                await atualizarQuantidadeItem(produtoId, qtdAtual + 1);
            }

            const btnMinus = e.target.closest('.btn-cart-minus');
            if (btnMinus) {
                const tr = btnMinus.closest('tr');
                const produtoId = tr.getAttribute('data-id');
                const inputQtd = tr.querySelector('.input-cart-qtd');
                const qtdAtual = parseInt(inputQtd.value, 10) || 1;
                if (qtdAtual > 1) {
                    await atualizarQuantidadeItem(produtoId, qtdAtual - 1);
                } else {
                    if (confirm('Deseja remover este item do carrinho?')) {
                        await removerItemCarrinho(produtoId);
                    }
                }
            }

            const btnRemove = e.target.closest('.btn-cart-remove');
            if (btnRemove) {
                const tr = btnRemove.closest('tr');
                const produtoId = tr.getAttribute('data-id');
                if (confirm('Deseja remover este produto do seu carrinho?')) {
                    await removerItemCarrinho(produtoId);
                }
            }
        });

        // Botão para limpar todo o carrinho
        const btnLimpar = document.querySelector('#btn-limpar-carrinho');
        if (btnLimpar) {
            btnLimpar.addEventListener('click', async () => {
                if (confirm('Tem certeza de que deseja esvaziar todo o carrinho?')) {
                    try {
                        const resposta = await fetch('api/carrinho.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ acao: 'limpar' })
                        });
                        const dados = await resposta.json();
                        if (dados.sucesso) {
                            window.location.reload();
                        }
                    } catch (err) {
                        alert('Erro ao esvaziar carrinho.');
                    }
                }
            });
        }
    }

    /**
     * Atualiza a quantidade do produto via API
     */
    async function atualizarQuantidadeItem(produtoId, novaQtd) {
        try {
            const resposta = await fetch('api/carrinho.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    acao: 'atualizar',
                    produto_id: produtoId,
                    quantidade: novaQtd
                })
            });

            const dados = await resposta.json();
            if (dados.sucesso) {
                window.location.reload(); // Recarrega para recalcular frete e totais com precisão
            } else {
                mostrarToast(dados.mensagem, 'erro');
            }
        } catch (erro) {
            console.error('Erro ao atualizar item:', erro);
        }
    }

    /**
     * Remove o item do carrinho via API
     */
    async function removerItemCarrinho(produtoId) {
        try {
            const resposta = await fetch('api/carrinho.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    acao: 'remover',
                    produto_id: produtoId
                })
            });

            const dados = await resposta.json();
            if (dados.sucesso) {
                window.location.reload();
            } else {
                mostrarToast(dados.mensagem, 'erro');
            }
        } catch (erro) {
            console.error('Erro ao remover item:', erro);
        }
    }

});
