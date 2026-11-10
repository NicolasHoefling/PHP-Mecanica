'use strict';

const modo = document.querySelector('select[name="modo"]');
if (modo) {
    const dependentes = modo.form.querySelectorAll('[data-modos]');
    const atualizar = () => {
        dependentes.forEach((grupo) => {
            const ativo = grupo.dataset.modos.split(' ').includes(modo.value);
            grupo.hidden = !ativo;
            grupo.querySelectorAll('input, select').forEach((controle) => {
                controle.disabled = !ativo;
                controle.required = ativo;
            });
        });
    };
    modo.addEventListener('change', atualizar);
    atualizar();
}
