/* HARMONIA — dukungan usulan tanpa reload */
(function () {
    document.querySelectorAll('[data-dukung]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const id = btn.dataset.dukung;
            btn.disabled = true;
            const lama = btn.textContent;
            btn.textContent = 'Mengirim...';
            try {
                const body = new FormData();
                body.append('id', id);
                body.append('csrf', btn.dataset.csrf);
                const res = await fetch(btn.dataset.url, { method: 'POST', body: body, headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (!res.ok) {
                    btn.textContent = data.sudah_dukung ? 'Sudah didukung' : 'Gagal';
                    return;
                }
                const jumlah = document.getElementById('dukungan-' + id);
                const bar = document.getElementById('progress-' + id);
                if (jumlah) jumlah.textContent = data.jumlah;
                if (bar) bar.style.width = Math.min(100, Math.round(data.jumlah / Number(btn.dataset.target) * 100)) + '%';
                if (data.status !== 'dibuka') {
                    const chip = document.getElementById('status-usulan-' + id);
                    if (chip) chip.innerHTML = '<span class="chip chip--ok">Tercapai</span>';
                }
                btn.textContent = 'Sudah didukung';
            } catch (e) {
                btn.textContent = lama;
                btn.disabled = false;
                alert('Gagal mengirim dukungan. Coba lagi.');
            }
        });
    });
})();
