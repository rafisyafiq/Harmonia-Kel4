/* HARMONIA — grafik Canvas native (tanpa library) */
(function () {
    const NAVY = '#131E3A', MUTED = '#5A657D', GRID = '#E2E7ED';
    const BAR = '#2E7D60', LINE = '#2563EB';

    function prepare(canvas) {
        const box = canvas.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        canvas.width = Math.max(1, Math.round(box.width * dpr));
        canvas.height = Math.max(1, Math.round(box.height * dpr));
        const ctx = canvas.getContext('2d');
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        return { ctx: ctx, w: box.width, h: box.height };
    }

    function frame(ctx, w, h, labels, max) {
        const pad = { l: 34, r: 8, t: 12, b: 26 };
        ctx.clearRect(0, 0, w, h);
        ctx.font = '11px -apple-system, "Segoe UI", sans-serif';
        ctx.textBaseline = 'middle';
        for (let i = 0; i <= 3; i++) {
            const y = pad.t + (h - pad.t - pad.b) * (1 - i / 3);
            ctx.strokeStyle = GRID;
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(pad.l, y + 0.5);
            ctx.lineTo(w - pad.r, y + 0.5);
            ctx.stroke();
            ctx.fillStyle = MUTED;
            ctx.textAlign = 'right';
            ctx.fillText(Math.round(max * i / 3).toLocaleString('id-ID'), pad.l - 6, y);
        }
        ctx.fillStyle = MUTED;
        ctx.textAlign = 'center';
        const every = Math.max(1, Math.ceil(labels.length / 8));
        labels.forEach(function (label, i) {
            if (i % every !== 0 && i !== labels.length - 1) return;
            const inner = w - pad.l - pad.r;
            const x = pad.l + inner * (labels.length > 1 ? i / (labels.length - 1) : 0.5);
            ctx.fillText(label, x, h - pad.b / 2);
        });
        return pad;
    }

    function bar(canvas, labels, values) {
        const { ctx, w, h } = prepare(canvas);
        const max = Math.max.apply(null, values.concat([1]));
        const pad = frame(ctx, w, h, labels, Math.ceil(max));
        const inner = w - pad.l - pad.r;
        const slot = inner / values.length;
        const bw = Math.min(34, slot * 0.55);
        values.forEach(function (v, i) {
            const bh = (h - pad.t - pad.b) * (v / max);
            const x = pad.l + slot * i + (slot - bw) / 2;
            const y = h - pad.b - bh;
            ctx.fillStyle = BAR;
            ctx.beginPath();
            if (ctx.roundRect) ctx.roundRect(x, y, bw, bh, [6, 6, 0, 0]);
            else ctx.rect(x, y, bw, bh);
            ctx.fill();
        });
        canvas._geom = { pad: pad, slot: slot, w: w, h: h };
    }

    function line(canvas, labels, values) {
        const { ctx, w, h } = prepare(canvas);
        const max = Math.max.apply(null, values.concat([1]));
        const pad = frame(ctx, w, h, labels, Math.ceil(max));
        const inner = w - pad.l - pad.r;
        const step = labels.length > 1 ? inner / (labels.length - 1) : 0;
        const pts = values.map(function (v, i) {
            return [pad.l + step * i, h - pad.b - (h - pad.t - pad.b) * (v / max)];
        });
        if (!pts.length) return;
        const grad = ctx.createLinearGradient(0, pad.t, 0, h - pad.b);
        grad.addColorStop(0, 'rgba(37, 99, 235, .22)');
        grad.addColorStop(1, 'rgba(37, 99, 235, 0)');
        ctx.beginPath();
        ctx.moveTo(pts[0][0], h - pad.b);
        ctx.lineTo(pts[0][0], pts[0][1]);
        for (let i = 1; i < pts.length; i++) {
            const p = pts[i - 1], c = pts[i];
            const mx = (p[0] + c[0]) / 2;
            ctx.bezierCurveTo(mx, p[1], mx, c[1], c[0], c[1]);
        }
        ctx.lineTo(pts[pts.length - 1][0], h - pad.b);
        ctx.closePath();
        ctx.fillStyle = grad;
        ctx.fill();
        ctx.beginPath();
        ctx.moveTo(pts[0][0], pts[0][1]);
        for (let i = 1; i < pts.length; i++) {
            const p = pts[i - 1], c = pts[i];
            const mx = (p[0] + c[0]) / 2;
            ctx.bezierCurveTo(mx, p[1], mx, c[1], c[0], c[1]);
        }
        ctx.strokeStyle = LINE;
        ctx.lineWidth = 2;
        ctx.stroke();
        pts.forEach(function (p) {
            ctx.beginPath();
            ctx.arc(p[0], p[1], 3, 0, Math.PI * 2);
            ctx.fillStyle = '#fff';
            ctx.fill();
            ctx.strokeStyle = LINE;
            ctx.lineWidth = 2;
            ctx.stroke();
        });
        canvas._geom = { pad: pad, pts: pts, w: w, h: h };
    }

    function tip(canvas, i) {
        const c = canvas._chart, g = canvas._geom;
        if (!c || !g || !g.pts && !g.slot) return;
        const ctx = canvas.getContext('2d');
        let x, y;
        if (c.kind === 'bar') {
            const max = Math.max.apply(null, c.values.concat([1]));
            x = g.pad.l + g.slot * i + g.slot / 2;
            y = g.h - g.pad.b - (g.h - g.pad.t - g.pad.b) * (c.values[i] / max);
        } else {
            x = g.pts[i][0];
            y = g.pts[i][1];
        }
        const text = c.labels[i] + ': ' + Number(c.values[i]).toLocaleString('id-ID') + (c.unit || '');
        ctx.font = '12px -apple-system, "Segoe UI", sans-serif';
        const tw = ctx.measureText(text).width;
        const bw = tw + 16, bh = 24;
        const bx = Math.min(Math.max(x - bw / 2, 4), g.w - bw - 4);
        let by = y - bh - 8;
        if (by < 2) by = y + 10;
        ctx.fillStyle = NAVY;
        ctx.beginPath();
        if (ctx.roundRect) ctx.roundRect(bx, by, bw, bh, 6);
        else ctx.rect(bx, by, bw, bh);
        ctx.fill();
        ctx.fillStyle = '#fff';
        ctx.textAlign = 'left';
        ctx.textBaseline = 'middle';
        ctx.fillText(text, bx + 8, by + bh / 2);
    }

    function redraw(canvas) {
        const c = canvas._chart;
        if (!c) return;
        (c.kind === 'bar' ? bar : line)(canvas, c.labels, c.values);
        if (canvas._hover !== null && canvas._hover !== undefined) tip(canvas, canvas._hover);
    }

    function bind(canvas) {
        if (canvas.dataset.hoverBound) return;
        canvas.dataset.hoverBound = '1';
        canvas.addEventListener('mousemove', function (e) {
            const c = canvas._chart, g = canvas._geom;
            if (!c || !g) return;
            const x = e.clientX - canvas.getBoundingClientRect().left;
            let idx;
            if (c.kind === 'bar') {
                idx = Math.floor((x - g.pad.l) / g.slot);
            } else {
                idx = 0;
                g.pts.forEach(function (p, i) {
                    if (Math.abs(p[0] - x) < Math.abs(g.pts[idx][0] - x)) idx = i;
                });
            }
            if (idx < 0 || idx >= c.values.length) {
                canvas._hover = null;
                redraw(canvas);
                return;
            }
            if (idx === canvas._hover) return;
            canvas._hover = idx;
            redraw(canvas);
        });
        canvas.addEventListener('mouseleave', function () {
            canvas._hover = null;
            redraw(canvas);
        });
    }

    function draw(data) {
        const charts = [
            { sel: '[data-chart="sampah"]', values: data.bulan.sampah, kind: 'bar', unit: ' kg', empty: '[data-empty="sampah"]' },
            { sel: '[data-chart="laporan"]', values: data.bulan.laporan, kind: 'line', unit: '', empty: '[data-empty="laporan"]' }
        ];
        charts.forEach(function (c) {
            const canvas = document.querySelector(c.sel);
            if (!canvas) return;
            const has = c.values.some(function (v) { return v > 0; });
            const empty = document.querySelector(c.empty);
            canvas.hidden = !has;
            if (empty) empty.hidden = has;
            canvas._chart = { kind: c.kind, labels: data.bulan.labels, values: c.values, unit: c.unit };
            canvas._hover = null;
            bind(canvas);
            if (has) (c.kind === 'bar' ? bar : line)(canvas, data.bulan.labels, c.values);
        });
    }

    let stats = null;
    fetch(window.HARMONIA_API || 'api/stats.php', { headers: { Accept: 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) { stats = data; draw(data); })
        .catch(function () {
            document.querySelectorAll('[data-chart]').forEach(function (c) { c.hidden = true; });
            document.querySelectorAll('.chart-empty').forEach(function (el) {
                el.hidden = false;
                el.textContent = 'Grafik tidak dapat dimuat.';
            });
        });

    let timer;
    window.addEventListener('resize', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            if (!stats) return;
            document.querySelectorAll('[data-chart]').forEach(function (canvas) {
                if (canvas.hidden || !canvas._chart) return;
                redraw(canvas);
            });
        }, 150);
    });
})();
