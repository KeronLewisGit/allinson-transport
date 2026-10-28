import Alpine from 'alpinejs';
import { startMotion } from './motion';

document.documentElement.classList.add('js'); // also set in <head>; kept for safety

/* Scroll reveal */
const reveal = () => {
    const items = document.querySelectorAll('[data-reveal]');
    if (!('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-visible'));
        return;
    }
    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                io.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -8% 0px' });
    items.forEach((el) => io.observe(el));
};

/* Submit buttons: one press only, with a visible "Sending…" state. */
Alpine.data('sendForm', () => ({
    submitting: false,
    submit(e) {
        if (!e.target.checkValidity()) return;
        this.submitting = true;
    },
}));

/* Quote request: the service decides which load fields show (pallets, dimensions, ADR details). */
Alpine.data('quoteForm', (service, adr) => ({
    submitting: false,
    service,
    adr,
    get pallets() { return ['part_load', 'pallets', 'warehousing'].includes(this.service); },
    get dimensions() { return ['hiab', 'abnormal'].includes(this.service); },
    get warehousing() { return this.service === 'warehousing'; },
    // Route strip: the lorry moves a third of the way for each finished section of the form.
    from: '',
    to: '',
    done: [false, false, false],
    get progress() { return this.submitting ? 1 : this.done.filter(Boolean).length / 3; },
    init() { this.$nextTick(() => this.track()); },
    track() {
        const f = this.$refs.form;
        if (!f) return;
        const v = (n) => (f.elements.namedItem(n)?.value ?? '').trim();
        const pc = (n) => v(n).toUpperCase().replace(/\s+/g, ' ').slice(0, 9);
        this.from = pc('collection_postcode');
        this.to = pc('delivery_postcode');
        this.done = [
            v('goods') !== '' && v('weight_kg') !== '',
            this.from.length >= 5 && this.to.length >= 5 && v('collection_date') !== '',
            v('contact_name') !== '' && v('email').includes('@') && v('phone').length >= 7,
        ];
    },
    submit(e) {
        if (!e.target.checkValidity()) return;
        this.submitting = true;
    },
}));

/* Driver application: driving roles ask for licence, CPC and tacho card details. */
Alpine.data('applyForm', (role, drives, points) => ({
    submitting: false,
    role,
    drives,
    points,
    get driving() { return this.drives.includes(this.role); },
    submit(e) {
        if (!e.target.checkValidity()) return;
        this.submitting = true;
    },
}));

/* Column chart hover: one tooltip that follows the focused or hovered column. */
Alpine.data('columnChart', () => ({
    tip: null,
    show(el) {
        const box = el.getBoundingClientRect();
        const host = this.$root.getBoundingClientRect();
        this.tip = { label: el.dataset.label, value: el.dataset.value, x: box.left - host.left + box.width / 2, y: box.top - host.top };
    },
    hide() { this.tip = null; },
}));

/* Dashboard chrome: density, mobile nav, "/" to search. */
Alpine.data('adminShell', () => ({
    nav: false,
    density: (() => { try { return localStorage.getItem('density') || 'comfortable'; } catch (e) { return 'comfortable'; } })(),
    init() {
        this.$el.dataset.density = this.density;
        window.addEventListener('keydown', (e) => {
            const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName);
            if (e.key === '/' && !typing) {
                const search = document.querySelector('[data-search]');
                if (search) { e.preventDefault(); search.focus(); }
            }
        });
    },
    toggleDensity() {
        this.density = this.density === 'compact' ? 'comfortable' : 'compact';
        this.$el.dataset.density = this.density;
        try { localStorage.setItem('density', this.density); } catch (e) { /* ignore */ }
    },
}));

window.Alpine = Alpine;
Alpine.start();
const ready = () => { reveal(); startMotion(); };
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready);
else ready();
