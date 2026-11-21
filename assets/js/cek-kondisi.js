/* HARMONIA — cek kondisi tanpa reload */
(function () {
    const form = document.getElementById('form-cek');
    if (!form) return;

    const batas = Number(form.dataset.batas) || 1;
    const ambang = Number(form.dataset.ambang) || 1.5;
    const box = document.getElementById('hasil-cek');
    const nilai = document.getElementById('hasil-nilai');
    const bar = document.getElementById('hasil-bar');
    const status = document.getElementById('hasil-status');
    const catatan = document.getElementById('hasil-catatan');
    const aksi = document.getElementById('hasil-aksi');
    const tombol = document.getElementById('hasil-tombol');

    const LABEL = { rentan: 'Rentan', dipantau: 'Perlu dipantau', cukup: 'Cukup' };
    const TONE = { rentan: 'warn', dipantau: 'info', cukup: 'ok' };

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = form.querySelector('[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Menghitung...';
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json' }
            });
            const data = await res.json();
            if (!res.ok) {
                alert((data.errors && Object.values(data.errors)[0]) || 'Periksa kembali isian.');
                return;
            }
            box.hidden = false;
            nilai.textContent = 'Rp ' + data.per_kapita.toLocaleString('id-ID');
            const puncak = batas * ambang;
            bar.style.width = Math.min(100, Math.round(data.per_kapita / puncak * 100)) + '%';
            bar.style.background = data.status === 'cukup' ? 'var(--sage-ink)' : (data.status === 'dipantau' ? 'var(--sky-ink)' : 'var(--peach-ink)');
            status.innerHTML = '<span class="chip chip--' + (TONE[data.status] || 'netral') + '">' + (LABEL[data.status] || data.status) + '</span>';
            catatan.textContent = data.status === 'cukup'
                ? 'Pengeluaran per orang berada di atas ambang pemantauan.'
                : 'Anda dapat mengajukan bantuan bila memenuhi syarat. Keputusan ada pada petugas RW.';
            aksi.hidden = data.status === 'cukup';
            if (tombol) tombol.href = form.dataset.ajukan + '?check_id=' + data.check_id;
            box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } catch (err) {
            alert('Gagal menghitung. Periksa koneksi lalu coba lagi.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Hitung sekarang';
        }
    });
})();
