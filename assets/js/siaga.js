/* HARMONIA — konfirmasi siaga */
(function () {
    const pesan = document.getElementById('pesan-siaga');
    document.querySelectorAll('[data-siaga]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const catatan = document.getElementById('catatan');
            btn.disabled = true;
            try {
                const body = new FormData();
                body.append('status', btn.dataset.siaga);
                body.append('catatan', catatan ? catatan.value : '');
                body.append('csrf', btn.dataset.csrf);
                const res = await fetch(btn.dataset.url, { method: 'POST', body: body, headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (pesan) pesan.textContent = res.ok ? 'Tersimpan: ' + data.label + '. Petugas dapat melihat konfirmasi ini.' : (data.error || 'Gagal menyimpan.');
            } catch (e) {
                if (pesan) pesan.textContent = 'Gagal menyimpan. Coba lagi.';
            } finally {
                btn.disabled = false;
            }
        });
    });
})();
