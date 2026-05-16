// js/mascaras.js — Máscaras de CPF e Telefone

document.addEventListener('DOMContentLoaded', () => {
  const cpfEl = document.getElementById('cpf');
  const telEl = document.getElementById('telefone');

  if (cpfEl) {
    cpfEl.addEventListener('input', () => {
      let v = cpfEl.value.replace(/\D/g, '').slice(0, 11);
      v = v.replace(/(\d{3})(\d)/, '$1.$2');
      v = v.replace(/(\d{3})(\d)/, '$1.$2');
      v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
      cpfEl.value = v;
    });
  }

  if (telEl) {
    telEl.addEventListener('input', () => {
      let v = telEl.value.replace(/\D/g, '').slice(0, 11);
      if (v.length <= 10) {
        v = v.replace(/(\d{2})(\d)/, '($1) $2');
        v = v.replace(/(\d{4})(\d)/, '$1-$2');
      } else {
        v = v.replace(/(\d{2})(\d)/, '($1) $2');
        v = v.replace(/(\d{5})(\d)/, '$1-$2');
      }
      telEl.value = v;
    });
  }
});
