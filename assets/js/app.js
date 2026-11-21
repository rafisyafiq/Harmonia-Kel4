/* HARMONIA — interaksi dasar */
document.addEventListener('click', function (e) {
    const toggle = e.target.closest('.nav-toggle');
    if (!toggle) return;
    const menu = document.getElementById(toggle.getAttribute('aria-controls'));
    const open = menu.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', String(open));
});

document.querySelectorAll('[data-counter]').forEach(function (el) {
    const out = document.getElementById(el.dataset.counter);
    if (!out) return;
    const update = function () { out.textContent = el.value.length + ' karakter'; };
    el.addEventListener('input', update);
    update();
});

document.querySelectorAll('form[data-busy]').forEach(function (form) {
    form.addEventListener('submit', function () {
        const btn = form.querySelector('[type="submit"]');
        if (!btn) return;
        btn.dataset.label = btn.textContent;
        btn.textContent = 'Memproses...';
        btn.disabled = true;
        setTimeout(function () { btn.textContent = btn.dataset.label; btn.disabled = false; }, 8000);
    });
});
