/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: assets/js/cep.js
 * Consulta de CEP via API ViaCEP, autopreenchimento e cálculo de frete
 * ==============================================================================
 */

document.addEventListener('DOMContentLoaded', () => {

    /**
     * Consulta dados de endereço na API pública ViaCEP
     * @param {string} cep
     * @returns {Promise<object>}
     */
    async function consultarViaCEP(cep) {
        const cepLimpo = cep.replace(/\D/g, '');
        if (cepLimpo.length !== 8) {
            throw new Error('O CEP deve conter exatamente 8 dígitos.');
        }

        const url = `https://viacep.com.br/ws/${cepLimpo}/json/`;
        const resposta = await fetch(url);
        
        if (!resposta.ok) {
            throw new Error('Falha ao comunicar com a API dos Correios/ViaCEP.');
        }

        const dados = await resposta.json();
        if (dados.erro) {
            throw new Error('CEP não encontrado na base de dados.');
        }

        return dados;
    }

    // --------------------------------------------------------------------------
    // 1. AUTOPREENCHIMENTO DE ENDEREÇO (CADASTRO / CHECKOUT)
    // --------------------------------------------------------------------------
    const campoCep = document.querySelector('#cep');
    if (campoCep) {
        const campoLogradouro = document.querySelector('#logradouro');
        const campoBairro = document.querySelector('#bairro');
        const campoCidade = document.querySelector('#cidade');
        const campoUf = document.querySelector('#uf');
        const campoNumero = document.querySelector('#numero');
        const avisoCep = document.querySelector('#aviso-cep');

        const executarBuscaEndereco = async () => {
            const valor = campoCep.value.replace(/\D/g, '');

            if (valor.length === 8) {
                if (avisoCep) {
                    avisoCep.textContent = 'Buscando endereço via ViaCEP...';
                    avisoCep.style.color = '#0284c7';
                }

                try {
                    const endereco = await consultarViaCEP(valor);

                    if (campoLogradouro) campoLogradouro.value = endereco.logradouro || '';
                    if (campoBairro) campoBairro.value = endereco.bairro || '';
                    if (campoCidade) campoCidade.value = endereco.localidade || '';
                    if (campoUf) campoUf.value = endereco.uf || '';

                    if (avisoCep) {
                        avisoCep.textContent = 'Endereço preenchido automaticamente!';
                        avisoCep.style.color = '#10b981';
                    }

                    if (campoNumero) campoNumero.focus();

                } catch (erro) {
                    if (avisoCep) {
                        avisoCep.textContent = erro.message || 'Erro ao consultar o CEP.';
                        avisoCep.style.color = '#ef4444';
                    }
                }
            }
        };

        campoCep.addEventListener('blur', executarBuscaEndereco);
    }

    // --------------------------------------------------------------------------
    // 2. CÁLCULO DE FRETE NO CARRINHO DE COMPRAS (carrinho.php)
    // --------------------------------------------------------------------------
    const btnCalcularFreteCarrinho = document.querySelector('#btn-calcular-frete');
    const inputCepCalculo = document.querySelector('#cep_calculo');
    const resultadoFreteBox = document.querySelector('#resultado-frete');
    const valorFreteDisplay = document.querySelector('#valor-frete-display');
    const valorTotalDisplay = document.querySelector('#valor-total-display');

    if (btnCalcularFreteCarrinho && inputCepCalculo) {
        const calcularFreteCarrinho = async () => {
            const cepDigitado = inputCepCalculo.value.replace(/\D/g, '');

            if (cepDigitado.length !== 8) {
                alert('Por favor, informe um CEP válido com 8 dígitos.');
                inputCepCalculo.focus();
                return;
            }

            const textoOriginal = btnCalcularFreteCarrinho.textContent;
            btnCalcularFreteCarrinho.disabled = true;
            btnCalcularFreteCarrinho.textContent = 'Calculando...';

            if (resultadoFreteBox) {
                resultadoFreteBox.style.display = 'block';
                resultadoFreteBox.innerHTML = '<span style="color: #0284c7;">Consultando ViaCEP...</span>';
            }

            try {
                // 1. Busca localização no ViaCEP
                const dadosCep = await consultarViaCEP(cepDigitado);

                // 2. Envia para o servidor calcular o frete com regras da TechWave
                const respostaServidor = await fetch('api/frete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        cep: cepDigitado,
                        uf: dadosCep.uf
                    })
                });

                const dadosFrete = await respostaServidor.json();

                if (dadosFrete.sucesso) {
                    // Exibe endereço e prazo
                    if (resultadoFreteBox) {
                        resultadoFreteBox.innerHTML = `
                            <div style="margin-top: 0.5rem; line-height: 1.4; color: var(--text-main);">
                                <strong>Destino:</strong> ${dadosCep.localidade}/${dadosCep.uf} (${dadosCep.bairro || 'Região Central'})<br>
                                <strong>Prazo Estimado:</strong> ${dadosFrete.prazo}
                            </div>
                        `;
                    }

                    // Atualiza o display do valor do frete
                    if (valorFreteDisplay) {
                        if (dadosFrete.frete_gratis) {
                            valorFreteDisplay.innerHTML = '<span style="color: var(--success); font-weight: 700;">FRETE GRÁTIS</span>';
                        } else {
                            valorFreteDisplay.textContent = dadosFrete.valor_formatado;
                        }
                    }

                    // Atualiza total geral
                    if (valorTotalDisplay && dadosFrete.total) {
                        const totalFormatado = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(dadosFrete.total);
                        valorTotalDisplay.textContent = totalFormatado;
                    }

                    if (typeof mostrarToast === 'function') {
                        mostrarToast('Frete calculado com sucesso!', 'sucesso');
                    }

                } else {
                    if (resultadoFreteBox) {
                        resultadoFreteBox.innerHTML = `<span style="color: var(--danger);">${dadosFrete.mensagem}</span>`;
                    }
                }

            } catch (erro) {
                if (resultadoFreteBox) {
                    resultadoFreteBox.innerHTML = `<span style="color: var(--danger);">${erro.message || 'Erro ao calcular frete.'}</span>`;
                }
            } finally {
                btnCalcularFreteCarrinho.disabled = false;
                btnCalcularFreteCarrinho.textContent = textoOriginal;
            }
        };

        btnCalcularFreteCarrinho.addEventListener('click', calcularFreteCarrinho);
        inputCepCalculo.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') {
                calcularFreteCarrinho();
            }
        });
    }

    // --------------------------------------------------------------------------
    // 3. CÁLCULO DE FRETE RÁPIDO NA PÁGINA DO PRODUTO (produto.php)
    // --------------------------------------------------------------------------
    const btnFreteProduto = document.querySelector('#btn-calcular-frete-produto');
    const inputCepProduto = document.querySelector('#cep_calculo');
    const boxResultadoProduto = document.querySelector('#resultado-frete-produto');

    if (btnFreteProduto && inputCepProduto && boxResultadoProduto) {
        btnFreteProduto.addEventListener('click', async () => {
            const cep = inputCepProduto.value.replace(/\D/g, '');
            if (cep.length !== 8) {
                alert('Informe um CEP válido com 8 números.');
                return;
            }

            btnFreteProduto.disabled = true;
            btnFreteProduto.textContent = 'Calculando...';
            boxResultadoProduto.style.display = 'block';
            boxResultadoProduto.innerHTML = '<span style="color: #0284c7;">Consultando ViaCEP...</span>';

            try {
                const dadosCep = await consultarViaCEP(cep);

                const res = await fetch('api/frete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cep: cep, uf: dadosCep.uf })
                });
                const frete = await res.json();

                if (frete.sucesso) {
                    boxResultadoProduto.innerHTML = `
                        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); padding: 0.75rem; border-radius: var(--radius-sm); margin-top: 0.5rem;">
                            <strong>Destino:</strong> ${dadosCep.localidade}/${dadosCep.uf}<br>
                            <strong>Valor:</strong> ${frete.valor_formatado}<br>
                            <strong>Prazo:</strong> ${frete.prazo}
                        </div>
                    `;
                } else {
                    boxResultadoProduto.innerHTML = `<span style="color: var(--danger);">${frete.mensagem}</span>`;
                }
            } catch (err) {
                boxResultadoProduto.innerHTML = `<span style="color: var(--danger);">${err.message || 'Erro ao consultar frete.'}</span>`;
            } finally {
                btnFreteProduto.disabled = false;
                btnFreteProduto.textContent = 'Calcular';
            }
        });
    }

});
