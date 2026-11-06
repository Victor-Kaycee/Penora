/**
 * analytics.js
 * Canvas-only chart with animated draw-in, smooth curves, tooltips on hover.
 * Data injected from PHP: window.chartData = { views: [...7], likes: [...7] }
 */
(function () {
    /* ── constants ───────────────────────────────────────────── */
    const DARK       = '#5a0505';
    const RED        = '#eb2020';
    const FILL_DARK  = 'rgba(90,5,5,0.10)';
    const FILL_RED   = 'rgba(235,32,32,0.08)';
    const MUTED      = '#9ca3af';
    const GRID_COLOR = '#e5e7eb';
    const PAD        = { top: 20, right: 20, bottom: 10, left: 10 };

    /* ── fallback demo data so the chart always shows something ─ */
    const raw = window.chartData || {};
    const DATA = {
        views: (raw.views && raw.views.length === 7) ? raw.views : [4,9,6,15,10,18,12],
        likes: (raw.likes && raw.likes.length === 7) ? raw.likes : [2,5,4,8,6,11,7]
    };

    /* ── find canvas ─────────────────────────────────────────── */
    const canvas = document.getElementById('activityChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    /* ── state ───────────────────────────────────────────────── */
    let animProgress = 0;   // 0 → 1 during intro animation
    let animFrame    = null;
    let mouseX       = -1;
    let mouseY       = -1;
    let dpr          = 1;

    /* ── sizing ──────────────────────────────────────────────── */
    function fit() {
        dpr = window.devicePixelRatio || 1;
        // use getBoundingClientRect for reliable dimensions
        const rect = canvas.getBoundingClientRect();
        const w = Math.max(rect.width,  100);
        const h = Math.max(rect.height, 100);

        canvas.width  = Math.round(w * dpr);
        canvas.height = Math.round(h * dpr);
        // reset transform before scaling
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.scale(dpr, dpr);

        return { w, h };
    }

    /* ── spline helper (Catmull-Rom) ─────────────────────────── */
    function splinePath(pts, progress) {
        if (pts.length < 2) return;
        // clip points by progress
        const totalLen = pts.length - 1;
        const cutAt    = progress * totalLen;

        ctx.moveTo(pts[0].x, pts[0].y);

        for (let i = 0; i < totalLen; i++) {
            if (i >= cutAt) break;

            const p0 = pts[Math.max(i - 1, 0)];
            const p1 = pts[i];
            const p2 = pts[i + 1];
            const p3 = pts[Math.min(i + 2, totalLen)];

            let cp1x = p1.x + (p2.x - p0.x) / 6;
            let cp1y = p1.y + (p2.y - p0.y) / 6;
            let cp2x = p2.x - (p3.x - p1.x) / 6;
            let cp2y = p2.y - (p3.y - p1.y) / 6;

            // if this is the last fully drawn segment, interpolate endpoint
            if (i + 1 > cutAt) {
                const t = cutAt - i;
                const ex = p1.x + (p2.x - p1.x) * t;
                const ey = p1.y + (p2.y - p1.y) * t;
                ctx.bezierCurveTo(cp1x, cp1y, cp2x, cp2y, ex, ey);
            } else {
                ctx.bezierCurveTo(cp1x, cp1y, cp2x, cp2y, p2.x, p2.y);
            }
        }
    }

    /* ── build pixel points ──────────────────────────────────── */
    function buildPts(values, maxVal, plotW, plotH, ox, oy) {
        return values.map((v, i) => ({
            x: ox + (i / (values.length - 1)) * plotW,
            y: oy + plotH - (maxVal > 0 ? (v / maxVal) * plotH : 0),
            v
        }));
    }

    /* ── draw one series ─────────────────────────────────────── */
    function drawSeries(pts, color, fillColor, progress, plotH, baseY) {
        if (pts.length < 2) return;

        const visibleCount = Math.floor(progress * (pts.length - 1));
        const lastPt       = pts[Math.min(visibleCount, pts.length - 1)];

        /* fill area */
        ctx.save();
        ctx.beginPath();
        splinePath(pts, progress);
        ctx.lineTo(lastPt.x, baseY);
        ctx.lineTo(pts[0].x, baseY);
        ctx.closePath();
        const grad = ctx.createLinearGradient(0, baseY - plotH, 0, baseY);
        grad.addColorStop(0,   fillColor.replace('0.10', '0.18').replace('0.08', '0.14'));
        grad.addColorStop(1,   fillColor.replace('0.10', '0.0').replace('0.08', '0.0'));
        ctx.fillStyle = grad;
        ctx.fill();
        ctx.restore();

        /* line */
        ctx.save();
        ctx.beginPath();
        splinePath(pts, progress);
        ctx.strokeStyle = color;
        ctx.lineWidth   = 2.5;
        ctx.lineJoin    = 'round';
        ctx.lineCap     = 'round';
        ctx.stroke();
        ctx.restore();

        /* dots — only on fully drawn points */
        for (let i = 0; i <= visibleCount && i < pts.length; i++) {
            const p   = pts[i];
            const isHovered = mouseX >= 0 &&
                              Math.abs(mouseX - p.x) < 18 &&
                              Math.abs(mouseY - p.y) < 18;

            ctx.beginPath();
            ctx.arc(p.x, p.y, isHovered ? 6 : 4, 0, Math.PI * 2);
            ctx.fillStyle   = color;
            ctx.fill();
            ctx.strokeStyle = '#fff';
            ctx.lineWidth   = 2;
            ctx.stroke();
        }
    }

    /* ── draw tooltip ────────────────────────────────────────── */
    function drawTooltip(viewsPts, likesPts, w) {
        if (mouseX < 0) return;

        // find nearest x index
        let nearest = 0;
        let minDist = Infinity;
        viewsPts.forEach((p, i) => {
            const d = Math.abs(mouseX - p.x);
            if (d < minDist) { minDist = d; nearest = i; }
        });

        if (minDist > 40) return;

        const vp = viewsPts[nearest];
        const lp = likesPts[nearest];

        const lines  = [`Views: ${vp.v}`, `Likes: ${lp.v}`];
        const TW     = 110;
        const TH     = 54;
        const RADIUS = 8;
        let tx = vp.x + 14;
        let ty = vp.y - TH - 10;
        if (tx + TW > w - 10)  tx = vp.x - TW - 14;
        if (ty < 6)             ty = vp.y + 14;

        ctx.save();
        ctx.shadowColor   = 'rgba(0,0,0,0.12)';
        ctx.shadowBlur    = 12;
        ctx.shadowOffsetY = 4;

        // box
        ctx.beginPath();
        ctx.roundRect(tx, ty, TW, TH, RADIUS);
        ctx.fillStyle = '#1f2937';
        ctx.fill();
        ctx.restore();

        // text
        ctx.save();
        ctx.font      = '600 12px "Outfit", sans-serif';
        ctx.fillStyle = '#fff';
        ctx.fillText(lines[0], tx + 12, ty + 20);
        ctx.fillStyle = RED;
        ctx.fillText(lines[1], tx + 12, ty + 38);
        ctx.restore();

        // vertical guide line
        ctx.save();
        ctx.setLineDash([4, 4]);
        ctx.strokeStyle = MUTED;
        ctx.lineWidth   = 1;
        ctx.globalAlpha = 0.5;
        ctx.beginPath();
        ctx.moveTo(vp.x, vp.y);
        ctx.lineTo(vp.x, vp.y + 200);
        ctx.stroke();
        ctx.restore();
    }

    /* ── update y-axis labels ────────────────────────────────── */
    function updateYLabels(maxVal) {
        const el = document.getElementById('yLabels');
        if (!el) return;
        const fmt  = v => v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v;
        const steps = [maxVal, Math.round(maxVal * 0.66), Math.round(maxVal * 0.33), 0];
        el.innerHTML = steps.map(v => `<span>${fmt(v)}</span>`).join('');
    }

    /* ── main draw ───────────────────────────────────────────── */
    function draw(progress) {
        const { w, h } = fit();  // re-fit every frame so resize is immediate

        ctx.clearRect(0, 0, w, h);

        const ox     = PAD.left;
        const oy     = PAD.top;
        const plotW  = w - PAD.left - PAD.right;
        const plotH  = h - PAD.top  - PAD.bottom;
        const baseY  = oy + plotH;

        const allVals = [...DATA.views, ...DATA.likes];
        const maxVal  = Math.max(...allVals, 1);

        updateYLabels(maxVal);

        /* grid lines */
        ctx.save();
        ctx.strokeStyle = GRID_COLOR;
        ctx.lineWidth   = 1;
        [0.25, 0.5, 0.75].forEach(f => {
            const y = oy + plotH * f;
            ctx.beginPath();
            ctx.moveTo(ox, y);
            ctx.lineTo(ox + plotW, y);
            ctx.stroke();
        });
        ctx.restore();

        const viewsPts = buildPts(DATA.views, maxVal, plotW, plotH, ox, oy);
        const likesPts = buildPts(DATA.likes, maxVal, plotW, plotH, ox, oy);

        drawSeries(viewsPts, DARK, FILL_DARK, progress, plotH, baseY);
        drawSeries(likesPts, RED,  FILL_RED,  progress, plotH, baseY);

        if (progress >= 1) {
            drawTooltip(viewsPts, likesPts, w);
        }
    }

    /* ── animation loop ──────────────────────────────────────── */
    const DURATION = 1200; // ms
    let startTime  = null;

    function animate(ts) {
        if (!startTime) startTime = ts;
        const elapsed = ts - startTime;
        // ease-out cubic
        const t = Math.min(elapsed / DURATION, 1);
        animProgress = t < 1 ? 1 - Math.pow(1 - t, 3) : 1;

        draw(animProgress);

        if (animProgress < 1) {
            animFrame = requestAnimationFrame(animate);
        } else {
            animFrame = null;
        }
    }

    function startAnimation() {
        if (animFrame) cancelAnimationFrame(animFrame);
        startTime    = null;
        animProgress = 0;
        animFrame    = requestAnimationFrame(animate);
    }

    /* ── mouse tracking ──────────────────────────────────────── */
    canvas.addEventListener('mousemove', e => {
        const rect = canvas.getBoundingClientRect();
        mouseX = e.clientX - rect.left;
        mouseY = e.clientY - rect.top;
        if (animProgress >= 1) draw(1);   // redraw for tooltip only after animation
    });

    canvas.addEventListener('mouseleave', () => {
        mouseX = -1;
        mouseY = -1;
        if (animProgress >= 1) draw(1);
    });

    /* ── resize (debounced) ──────────────────────────────────── */
    let resizeTimer = null;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            if (animProgress >= 1) draw(1);
        }, 120);
    });

    /* ── kick off ────────────────────────────────────────────── */
    // Use requestAnimationFrame to ensure the element is painted and has dimensions
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            startAnimation();
        });
    });

})();