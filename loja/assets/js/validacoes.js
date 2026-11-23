/**
 * ==============================================================================
 * PROJETO FINAL: LOJA VIRTUAL - TechWave Store
 * Arquivo: assets/js/validacoes.js
 * Máscaras de entrada, validações no lado do cliente e utilitários de formulário
 * ==============================================================================
 */

document.addEventListener('DOMContentLoaded', () => {

    // --------------------------------------------------------------------------
    // 1. MÁSCARAS DE ENTRADA (CPF, Telefone, CEP)
    // --------------------------------------------------------------------------

    /**
     * Aplica máscara de CPF: 000.000.000-00
     */
    const mascaraCPF = (valor) => {
        return valor
            .replace(/\D/g, '')
            .slice(0, 11)
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    };

    /**
     * Aplica máscara de Telefone: (00) 00000-0000 ou (00) 0000-0000
     */
    const mascaraTelefone = (valor) => {
        let limpo = valor.replace(/\D/g, '').slice(0, 11);
        if (limpo.length > 10) {
            return limpo.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
        } else if (limpo.length > 5) {
            return limpo.replace(/^(\d{2})(\d{4})(\d{0,4})$/, '($1) $2-$3');
        } else if (limpo.length > 2) {
            return limpo.replace(/^(\d{2})(\d{0,5})$/, '($1) $2');
        }
        return limpo;
    };

    /**
     * Aplica máscara de CEP: 00000-000
     */
    const mascaraCEP = (valor) => {
        return valor
            .replace(/\D/g, '')
            .slice(0, 8)
            .replace(/^(\d{5})(\d{1,3})$/, '$1-$2');
    };

    // Associa eventos de digitação nos campos com data-mask ou por id/name
    document.querySelectorAll('input[name="cpf"], #cpf').forEach(input => {
        input.addEventListener('input', (e) => {
            e.target.value = mascaraCPF(e.target.value);
        });
    });

    document.querySelectorAll('input[name="telefone"], #telefone').forEach(input => {
        input.addEventListener('input', (e) => {
            e.target.value = mascaraTelefone(e.target.value);
        });
    });

    document.querySelectorAll('input[name="cep"], #cep, #cep_calculo').forEach(input => {
        input.addEventListener('input', (e) => {
            e.target.value = mascaraCEP(e.target.value);
        });
    });

    // --------------------------------------------------------------------------
    // 2. VALIDAÇÃO DE SENHA E CONFIRMAÇÃO DE SENHA
    // --------------------------------------------------------------------------
    const formCadastro = document.querySelector('#form-cadastro-cliente');
    if (formCadastro) {
        const inputSenha = formCadastro.querySelector('#senha');
        const inputConfirmar = formCadastro.querySelector('#confirmar_senha');
        const msgErroSenha = document.querySelector('#erro-confirmar-senha');

        const validarSenhas = () => {
            if (!inputSenha || !inputConfirmar) return true;
            if (inputSenha.value !== inputConfirmar.value) {
                if (msgErroSenha) {
                    msgErroSenha.textContent = 'As senhas não coincidem.';
                    msgErroSenha.style.display = 'block';
                }
                inputConfirmar.setCustomValidity('As senhas não coincidem.');
                return false;
            } else {
                if (msgErroSenha) {
                    msgErroSenha.style.display = 'none';
                }
                inputConfirmar.setCustomValidity('');
                return true;
            }
        };

        if (inputSenha && inputConfirmar) {
            inputSenha.addEventListener('input', validarSenhas);
            inputConfirmar.addEventListener('input', validarSenhas);
        }

        formCadastro.addEventListener('submit', (e) => {
            if (!validarSenhas()) {
                e.preventDefault();
                inputConfirmar.focus();
            }
        });
    }

});
