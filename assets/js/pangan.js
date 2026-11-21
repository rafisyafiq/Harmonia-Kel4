/* HARMONIA — klaim pangan tanpa reload */
(function () {
    document.querySelectorAll('[data-ambil]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const id = btn.dataset.ambil;
            const pilih = document.querySelector('[data-porsi="' + id + '"]');
            const pesan = document.getElementById('pesan-' + id);
            const kontak = document.getElementById('kontak-' + id);
            btn.disabled = true;
            btn.textContent = 'Mengambil...';
            try {
                const body = new FormData();
                body.append('id', id);
                body.append('porsi', pilih ? pilih.value : '1');
                body.append('csrf', btn.dataset.csrf);
                const res = await fetch(btn.dataset.url, { method: 'POST', body: body, headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (!res.ok) {
                    btn.disabled = false;
                    btn.textContent = 'Ambil';
                    if (pesan) pesan.textContent = data.error || 'Gagal mengambil.';
                    return;
                }
                if (pesan) pesan.textContent = 'Berhasil. Sisa ' + data.porsi_sisa + ' porsi.';
                if (kontak) kontak.innerHTML = data.kontak ? 'Hubungi donor: <a href="tel:' + data.kontak + '">' + data.kontak + '</a>' : '';
                if (data.status === 'habis') {
                    btn.textContent = 'Habis';
                    btn.disabled = true;
                    if (pilih) pilih.disabled = true;
                } else {
                    btn.textContent = 'Ambil';
                    btn.disabled = false;
                }
            } catch (e) {
                btn.disabled = false;
                btn.textContent = 'Ambil';
                if (pesan) pesan.textContent = 'Gagal mengambil. Coba lagi.';
            }
        });
    });
})();
