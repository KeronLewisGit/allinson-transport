/*
 * Motion and interaction. Progressive throughout: the server sends finished content, and these
 * functions only add movement on top. With prefers-reduced-motion they do nothing that moves.
 * Only transform, opacity and SVG stroke are animated; scroll work uses IntersectionObserver
 * and requestAnimationFrame, and listens to scroll only while the element is on screen.
 */
const reduced = () => matchMedia('(prefers-reduced-motion: reduce)').matches;

const onScreen = (el, cb, options = { rootMargin: '0px 0px -12% 0px' }) => {
    if (!('IntersectionObserver' in window)) { cb(); return; }
    const io = new IntersectionObserver((entries) => {
        entries.forEach((e) => { if (e.isIntersecting) { io.disconnect(); cb(); } });
    }, options);
    io.observe(el);
};

/* Gold bar: once the dark brand band has scrolled away, the GA badge takes the home slot. */
function stickyBar() {
    const bar = document.querySelector('[data-goldbar]');
    const brand = document.querySelector('[data-brandbar]');
    if (!bar || !brand || !('IntersectionObserver' in window)) return;
    new IntersectionObserver(([e]) => bar.classList.toggle('is-stuck', !e.isIntersecting), { threshold: 0 }).observe(brand);
}

/* Reading progress, for browsers without CSS scroll timelines. */
function scrollProgress() {
    const bar = document.querySelector('.scroll-progress');
    if (!bar || reduced() || (window.CSS && CSS.supports('animation-timeline: scroll()'))) return;
    let queued = false;
    const draw = () => {
        queued = false;
        const max = document.documentElement.scrollHeight - innerHeight;
        bar.style.transform = `scaleX(${max > 0 ? Math.min(1, scrollY / max) : 0})`;
    };
    addEventListener('scroll', () => { if (!queued) { queued = true; requestAnimationFrame(draw); } }, { passive: true });
    draw();
}

/* Headline words rise in one after another. The text stays one readable string for screen readers. */
function splitWords() {
    if (reduced()) return;
    document.querySelectorAll('[data-words]').forEach((el) => {
        const text = el.textContent.trim();
        el.setAttribute('aria-label', text);
        el.innerHTML = text.split(/\s+/).map((w, i) => `<span class="w" aria-hidden="true" style="--i:${i}">${w.replace(/</g, '&lt;')}</span>`).join(' ');
        el.classList.add('is-split');
    });
}

/* Published figures count up from zero the first time they are seen. */
function countUp() {
    if (reduced()) return;
    const fmt = new Intl.NumberFormat('en-GB');
    document.querySelectorAll('[data-count]').forEach((el) => {
        const final = el.textContent.trim();
        const target = Number(final.replace(/[^\d.]/g, ''));
        if (!Number.isFinite(target) || target <= 0) return;
        // Hold the finished width so nothing beside the number moves while it counts.
        el.style.display = 'inline-block';
        el.style.width = `${el.getBoundingClientRect().width}px`;
        el.textContent = '0';
        onScreen(el, () => {
            const start = performance.now();
            const dur = 1600;
            el.classList.add('is-counting');
            const tick = (now) => {
                const t = Math.min(1, (now - start) / dur);
                const eased = 1 - Math.pow(1 - t, 3);
                el.textContent = t < 1 ? fmt.format(Math.round(target * eased)) : final;
                if (t < 1) requestAnimationFrame(tick); else el.classList.remove('is-counting');
            };
            requestAnimationFrame(tick);
        });
    });
}

/* Depot map: routes draw out from Darlington when the map is on screen. */
function depotMaps() {
    if (reduced()) return;
    document.querySelectorAll('.depot-map').forEach((map) => {
        map.classList.add('is-armed');
        map.querySelectorAll('.route').forEach((r) => r.addEventListener('animationend', () => r.classList.add('done'), { once: true }));
        onScreen(map, () => map.classList.add('is-visible'));
    });
}

/* "No Word form": the old file name is struck through when it comes into view. */
function strikes() {
    if (reduced()) return;
    document.querySelectorAll('[data-strike]').forEach((el) => {
        el.style.setProperty('--strike', '0');
        onScreen(el, () => setTimeout(() => el.style.setProperty('--strike', '1'), 300));
    });
}

/*
 * Driver application story: the red road fills down the steps and the lorry drives with it,
 * tied to scroll position while the story is on screen.
 */
function stories() {
    if (reduced()) return;
    document.querySelectorAll('[data-story]').forEach((story) => {
        const steps = [...story.querySelectorAll('.story-step')];
        let active = false;
        let queued = false;
        const update = () => {
            queued = false;
            const box = story.getBoundingClientRect();
            const mid = innerHeight * 0.55;
            const p = Math.min(1, Math.max(0, (mid - box.top) / box.height));
            story.style.setProperty('--story', p.toFixed(4));
            steps.forEach((s) => s.classList.toggle('is-on', s.getBoundingClientRect().top < mid));
        };
        const onScroll = () => { if (active && !queued) { queued = true; requestAnimationFrame(update); } };
        const size = () => story.style.setProperty('--story-h', `${story.querySelector('.story-road').offsetHeight}px`);
        size();
        if ('ResizeObserver' in window) new ResizeObserver(size).observe(story);
        story.style.setProperty('--story', '0');
        new IntersectionObserver(([e]) => { active = e.isIntersecting; if (active) update(); }).observe(story);
        addEventListener('scroll', onScroll, { passive: true });
    });
}

/* Theme switch with a circular wipe from the button, where View Transitions are supported. */
window.toggleTheme = (event) => {
    const root = document.documentElement;
    const current = root.dataset.theme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    const next = current === 'dark' ? 'light' : 'dark';
    const apply = () => {
        root.dataset.theme = next;
        try { localStorage.setItem('theme', next); } catch (e) { /* storage blocked: the choice lasts this page only */ }
    };
    if (!document.startViewTransition || reduced()) { apply(); return; }
    const btn = event?.currentTarget?.getBoundingClientRect?.();
    const x = btn ? btn.left + btn.width / 2 : innerWidth;
    const y = btn ? btn.top + btn.height / 2 : 0;
    root.style.setProperty('--wipe-x', `${x}px`);
    root.style.setProperty('--wipe-y', `${y}px`);
    root.style.setProperty('--wipe-r', `${Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y))}px`);
    root.classList.add('theme-wipe');
    const t = document.startViewTransition(apply);
    t.finished.finally(() => root.classList.remove('theme-wipe'));
};

export function startMotion() {
    stickyBar();
    scrollProgress();
    splitWords();
    countUp();
    depotMaps();
    strikes();
    stories();
}
