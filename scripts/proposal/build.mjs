// Builds the client proposal (letter-size PDF) from the captured screenshots, in the site's own
// palette and fonts, matched to the company's own branding (beige page, red box headers, gold
// navigation bar, black-and-gold GA badge; Roboto Condensed and Trebuchet MS).
// Usage: npm run proposal (or node scripts/proposal/build.mjs after a capture).
import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
import path from 'node:path';

const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const ROOT = path.resolve('.');
const SHOTS = path.resolve('dist/proposal/shots');
const chunks = JSON.parse(fs.readFileSync(path.join(SHOTS, 'chunks.json'), 'utf8'));
const OUT_HTML = path.resolve('dist/proposal/proposal.html');
const OUT_PDF = path.resolve('dist/proposal/Allinson-Transport-Website-Proposal.pdf');
const MAX_MB = 9.5;
const DATE = new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
const TESTS = process.env.TEST_COUNT ?? '72';
const img = (f) => 'file://' + f;
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;');

// The site's self-hosted fonts (built by the Vite fonts plugin into public/build/assets).
const ASSETS = path.join(ROOT, 'public/build/assets');
const font = (prefix) => {
  const f = fs.readdirSync(ASSETS).find((n) => n.startsWith(prefix) && n.endsWith('.woff2'));
  if (!f) throw new Error(`Font ${prefix} not found in public/build/assets; run npm run build first.`);
  return 'file://' + path.join(ASSETS, f);
};
const FONTS = [
  ['Roboto Condensed', 700, 'roboto-condensed-700-normal'], ['Roboto Condensed', 800, 'roboto-condensed-800-normal'],
  ['Fira Sans', 400, 'fira-sans-400-normal'], ['Fira Sans', 700, 'fira-sans-700-normal'],
].map(([fam, w, p]) => `@font-face { font-family: "${fam}"; font-weight: ${w}; font-style: normal; src: url("${font(p)}") format("woff2"); }`).join('\n');

// The site's redrawn GA badge (after the gold-edged mark on the firm's current website).
const MARK = (h = 34) => `<svg class="mark" viewBox="0 0 213 124" height="${h}" width="${Math.round(h * 213 / 124)}"><g fill="#231f20" stroke="#d7b273" stroke-width="5"><path d="M3 62 76 4h53L84 58 62 69l22 19v33H62z"/><path d="M89 121V66L141 3l69 59-32 59h-43V86h-4v35z"/></g><path d="M133 45v29h27z" fill="#fff" stroke="#d9ae5c" stroke-width="3"/></svg>`;
const WORDMARK = (dark = true) => `<div class="brand">${MARK(dark ? 44 : 34)}<div><span class="n ${dark ? '' : 'ink'}">George Allinson</span><span class="s ${dark ? '' : 'ink'}">(Transport) Ltd · Darlington · Est. 1955</span></div></div>`;
const plate = (code, label, dark = false) => `<p class="plate ${dark ? 'dark' : ''}"><span class="c">${esc(code)}</span><span class="l">${esc(label)}</span></p>`;

/** Hand-written captions per slice (key = screen name, index = slice number), from what each slice shows. */
const CAPTIONS = {
  home: [
    'Hero on a licensed photograph of one of the company\'s lorries, with the trailer lettering "We care for your cargo" as the headline, the three things a customer comes to do (quote, warehousing, phone) and a despatch label with the published fleet figures. Below, the four figures exactly as the company publishes them, with the source.',
    'Six service lines from the company\'s own pages, each on one of its own photographs.',
    'The rest of the service lines.',
    'Three ways in (quote, warehousing, free logistics benchmark) and the five depots.',
    'Careers ("no Word form to download, print or post") and the sectors the company names.',
    'Footer with every page, the head office, the old site\'s links redirected, and the red-and-white rear-marking band.',
  ],
  services: [
    'Services page: each line of work in the company\'s own words, with its source page linked.',
    'UK trunking and distribution; Hiab, crane-lifted and abnormal loads.',
    'Container storage and handling; warehousing and order fulfilment.',
    'Logistics consultancy and technology, and the sectors the company names.',
  ],
  fleet: [
    'Fleet and warehousing: 120 vehicles, 250 trailers and 250,000 sq ft, as published, and the vehicle types.',
    'Tracking, speed limiting and load security, and the company\'s "lean and green" commitments.',
    'Warehousing laid out around the customer\'s product, and the container yard: 50,000 sq ft of hard standing for up to 500 TEU.',
  ],
  depots: [
    'Depots: the Faverdale head office with its published address and a directions link.',
    'The four regional depots the company names. Their addresses are not published anywhere current, so the page says "call head office" until they are confirmed.',
  ],
  about: [
    'About: family run and owned since 1955, quoted as the company publishes it.',
    'Values and the company\'s environmental commitments.',
    'Closing call to action.',
  ],
  contact: ['Contact: telephone, email, head office, a message form with topics (including a free benchmark), and the map link.'],
  quote: [
    'Quote request: type of job (full, part, pallets, Hiab, abnormal, store-then-deliver), goods, weight, ADR and tail lift. The form shows pallet or dimension fields only when the job needs them.',
    'Postcodes and dates, contact details and an optional file. Collection postcode decides which depot\'s traffic office prices it.',
  ],
  'quote-response': ['The customer\'s quote page, reached from the emailed link: the price, validity and job on a despatch label, with Accept and book or Decline. Traffic sees the answer at once.'],
  warehousing: [
    'Warehousing enquiry: pallet spaces, how long, the type of goods and the handling needed.',
    'Goods in and out, value-added services and dates.',
    'Contact details and consent.',
  ],
  careers: [
    'Careers: the four kinds of role and which depots are hiring, with a depot filter.',
    'Open vacancies (sample), each with its own page and Google job-posting data, and the end of the Word application form.',
  ],
  apply: [
    'Online application: role, depot and experience. Driving roles ask for licence categories and points.',
    'Driver CPC and tacho card expiry dates and endorsements, self-declared; right to work.',
    'Optional CV, consent and send. About five minutes on a phone.',
  ],
  documents: [
    'The applicant\'s private upload page, reached from a link HR emails: licence, CPC card, tacho card and right-to-work copies.',
    'Uploads go to private storage, outside the public website.',
  ],
  'admin-login': ['Staff sign-in. Individual accounts, four roles, and switched-off accounts are refused.'],
  'admin-dashboard': [
    'Traffic overview: quote requests waiting for a price at this planner\'s depot, collecting soonest first, then the week in numbers.',
    'Quote requests by week and the quote pipeline.',
  ],
  'admin-dashboard-hr': [
    'HR overview: drivers whose CPC, tacho card or licence has expired or expires within 60 days, then applications waiting and assessment drives booked.',
    'Card expiries by month for the next year, and the recruitment pipeline.',
  ],
  'admin-dashboard-warehouse': ['Warehouse overview: enquiries that need a call back, pallet spaces asked for and the enquiry pipeline.'],
  'admin-quotes': [
    'Quote register: filter by depot, status, service or collection date; ADR and abnormal loads flagged.',
    'The rest of the register, with CSV export.',
  ],
  'admin-quote': ['A quote request: collection and delivery, goods, weight and dimensions, the depot and planner, and the price form that emails the customer their accept-or-decline link.'],
  'admin-warehousing': ['Warehousing register: pallet spaces, duration and goods type at a glance.'],
  'admin-warehousing-one': ['A warehousing enquiry with status, notes and a full timeline.'],
  'admin-applications': [
    'Applications by status, role and depot; applicants waiting more than two days are flagged.',
    'The rest of the list, with CSV export.',
  ],
  'admin-application': [
    'An applicant: declared licence categories, points, CPC and tacho card expiry, right-to-work and references checks, assessment drive date and result.',
    'Documents from the applicant (each opening logged), Hire (blocked until every check is done) and the timeline.',
  ],
  'admin-staff': [
    'Driver compliance: every driver\'s CPC, tacho card and licence expiry, worst first.',
    'Filters for depot, role and "due within 60 days", with CSV export.',
  ],
  'admin-staff-one': ['A driver\'s record: card dates, who changed them and when, and the reminders sent.'],
  'admin-users': ['Staff accounts: add people, set their role and depot, switch accounts off.'],
};

/** Screen definitions: section, key, title, bullets. */
const SECTIONS = [
  { id: 'public', eyebrow: 'Part 1', code: '01', title: 'The public website', intro: 'A new allinsontransport.co.uk, written from the company\'s own pages and photographs. Every figure is quoted as the company publishes it, with the page it came from. Every inner page opens with the same charcoal band and despatch label, and every page works on a phone.', screens: [
    ['home', 'Home page', ['Leads with the company\'s GA badge, its own night photo of the depot and its own words, "We care for your cargo".', 'Three actions up front: request a quote, ask about warehousing, call 01325 461241.']],
    ['services', 'Services', ['Six lines of work, each with its source page.', 'Old addresses such as /page.php?id=3 redirect to the new pages.']],
    ['fleet', 'Fleet and warehousing', ['The published fleet, warehouse and container-yard figures, quoted exactly.']],
    ['depots', 'Depots', ['A map draws routes out from Darlington to each region.', 'Only the head office address is shown until the regional addresses are confirmed.']],
    ['about', 'About the company', ['Family run and owned since 1955, in the company\'s own words.']],
    ['contact', 'Contact', ['Telephone, email, head office and a message form.']],
  ]},
  { id: 'flows', eyebrow: 'Part 2', code: '02', title: 'Quotes, warehousing and drivers', intro: 'Three intake flows replace the phone tag and the downloadable Word form. A quote request goes to the depot that owns the collection and comes back as a price the customer can accept online. A warehousing enquiry reaches the warehouse. A driver applies on a phone in five minutes, and later uploads licence and card copies through a private link. Each sender gets a reference number on screen and by email.', screens: [
    ['quote', 'Request a quote', ['Full, part, pallets, Hiab, abnormal or store-then-deliver; postcodes, dates, weight, dimensions and ADR.', 'Routed by collection postcode to the depot that prices it.']],
    ['quote-response', 'The customer\'s quote', ['Emailed as a private link that expires; one click to accept or decline.']],
    ['warehousing', 'Warehousing enquiry', ['Pallet spaces, duration, goods type and handling.']],
    ['careers', 'Careers', ['C+E, Hiab, warehouse and transport-office roles, by depot.']],
    ['apply', 'Job application', ['Licence categories, CPC and tacho card expiry and endorsements, self-declared; no Word form.']],
    ['documents', 'Applicant documents', ['A private, expiring upload link HR sends when an applicant reaches checks.']],
  ]},
  { id: 'admin', eyebrow: 'Part 3', code: '03', title: 'The back office', intro: 'A private, role-based back office for the traffic offices, the warehouse and HR. Each role sees only its own registers, and the overview opens on the one number that role has to act on today.', screens: [
    ['admin-login', 'Staff sign-in', ['Individual accounts with four roles: admin, traffic, warehouse and HR.']],
    ['admin-dashboard', 'Overview for traffic', ['Each traffic planner sees their own depot\'s quotes first.']],
    ['admin-dashboard-hr', 'Overview for HR', ['HR sees drivers\' cards that are expired or due first.']],
    ['admin-dashboard-warehouse', 'Overview for the warehouse', ['The warehouse sees enquiries waiting for a call back.']],
    ['admin-quotes', 'Quote register', ['New, pricing, quoted, accepted or not taken, with the depot and planner on every request.']],
    ['admin-quote', 'Quote detail', ['Price it and email it from the record; the customer accepts or declines online.']],
    ['admin-warehousing', 'Warehousing register', ['Every enquiry with its pallet spaces and duration.']],
    ['admin-warehousing-one', 'Warehousing enquiry', ['Status, notes and a timeline.']],
    ['admin-applications', 'Applications', ['New, screening, checks, assessment drive, offer, hired.']],
    ['admin-application', 'Applicant detail', ['Right to work, references and the assessment drive tracked; hiring is blocked until they are done.', 'Hiring starts compliance tracking for the new driver.']],
    ['admin-staff', 'Driver compliance', ['CPC, tacho card and licence expiry for every driver.', 'HR is emailed each morning at 90, 60, 30 and 14 days, and on the day a card expires.']],
    ['admin-staff-one', 'Driver record', ['Every card-date change logged with who made it.']],
    ['admin-users', 'Staff and roles', ['Add staff, set role and depot, and switch accounts off.']],
  ]},
];
const SCREEN_COUNT = SECTIONS.reduce((n, s) => n + s.screens.length, 0);

const FEATURES = [
  ['Public website', ['Services, fleet, depots, about, contact', 'Figures quoted with their source page', 'The company\'s own photographs', 'Redirects from the old page.php links', 'Sitemap and Google structured data', 'Call / Jobs / Quote bar on phones', 'Light and dark themes']],
  ['Intake', ['Haulage quote requests routed by depot', 'Online quote acceptance for customers', 'Warehousing enquiries', 'Careers with Google job postings', 'Online driver applications (no Word form)', 'Private applicant document uploads', 'Contact form with a benchmark option', 'Reference number on screen and by email']],
  ['Back office', ['Overview that leads with each role\'s number', 'Quote register by depot', 'Warehousing register', 'Recruitment pipeline with checks', 'Driver CPC, tacho and licence tracking', 'Daily compliance email to HR', 'Notes and a timeline on every record', 'CSV exports', 'Four staff roles']],
  ['Platform', ['Laravel 13 on PHP 8.3', 'HTTPS throughout', 'All uploads stored privately', 'Each team opens only its own files', 'Rate-limited, spam-protected forms', 'UK GDPR privacy notice', `${TESTS} automated tests`]],
];

/**
 * Pack a screen's slices onto pages. A slice's height ÷ width is its share of a page. Every slice keeps
 * its own caption; slices never break across pages and keep their order. The layout is chosen by cost:
 * one point per page, one more for a page left about a third empty, half a point for shrinking slices
 * below 90% of a 1440px screen to share a page (never below 85%), and a quarter point for moving the
 * bullets off the first page. So a short slice joins its neighbour instead of sitting alone on a
 * half-blank page; ties go to the layout whose emptiest page is fullest.
 */
function pages(c, hasBullets) {
  const sizes = c.sizes ?? c.chunks.map(() => [1440, 1440]);
  // Page capacity as image height ÷ text width: about 1.22 below the heading, 0.98 when bullets sit on top.
  // The estimate leaves out caption height, so a page counts as gappy at 25% estimated (about 35% printed).
  const CAP = 0.08, GOOD_SCALE = 0.9, MIN_SCALE = 0.85, GAP = 0.25;
  const ratios = sizes.map(([w, h]) => h / w);
  const page = (from, to, bullets) => {
    const budget = bullets ? 0.98 : 1.22;
    const sum = ratios.slice(from, to).reduce((a, r) => a + r, 0);
    const n = to - from;
    const scale = Math.min(1, (budget - CAP * n) / sum);
    const readable = scale * 1440 / sizes[from][0]; // narrower captures carry larger type
    if (n > 1 && readable < MIN_SCALE) return null;
    const empty = 1 - (sum * scale + CAP * n) / budget;
    return { items: [...Array(n).keys()].map((k) => from + k), bullets, budget, scale, empty, cost: 1 + (empty > GAP ? 1 : 0) + (readable < GOOD_SCALE ? 0.5 : 0) + (bullets && from > 0 ? 0.25 : 0) };
  };
  // best(i, owed) = cheapest packing of slices i..end, where owed means the bullets still need a page.
  const n = ratios.length;
  const memo = new Map();
  const best = (i, owed) => {
    if (i === n) return owed ? null : { list: [], cost: 0, worst: 0 };
    const key = i + ':' + owed;
    if (memo.has(key)) return memo.get(key);
    let pick = null;
    for (const here of owed ? [true, false] : [false]) {
      for (let j = i + 1; j <= n; j++) {
        const pg = page(i, j, here);
        if (!pg) break;
        const rest = best(j, owed && !here);
        if (!rest) continue;
        const cand = { list: [pg, ...rest.list], cost: pg.cost + rest.cost, worst: Math.max(pg.empty, rest.worst) };
        if (!pick || cand.cost < pick.cost - 1e-9 || (Math.abs(cand.cost - pick.cost) < 1e-9 && cand.worst < pick.worst - 1e-9)) pick = cand;
      }
    }
    memo.set(key, pick);
    return pick;
  };
  return best(0, hasBullets).list;
}

function caption(key, i, c) {
  const text = CAPTIONS[key]?.[i];
  if (!text) return '';
  const n = c.chunks.length > 1 ? `${i + 1}/${c.chunks.length} · ` : '';
  return `<figcaption class="cap"><span class="n">${esc(n)}</span>${esc(text)}</figcaption>`;
}

function screenPages(sec, [key, title, bullets]) {
  const c = chunks[key];
  if (!c) throw new Error(`Screenshot missing: ${key}`);
  return pages(c, bullets.length > 0).map((p, pi) => `
    <section class="page shot">
      <header class="shot-head">
        <div><p class="eyebrow">${esc(sec.title)}${pi ? ' · continued' : ''}</p><h2>${esc(title)}</h2></div>
        <p class="url">${esc(c.url.split('?')[0].replace(/^(\/(?:your-quote|careers\/documents)\/).+$/, '$1…'))}</p>
      </header>
      ${p.bullets ? `<ul class="bullets">${bullets.map((b) => `<li>${esc(b)}</li>`).join('')}</ul>` : ''}
      ${p.items.map((i) => `<figure><img style="width:${(p.scale * 100).toFixed(1)}%" src="${img(c.chunks[i])}" alt="${esc(title)}">${caption(key, i, c)}</figure>`).join('')}
      ${foot()}
    </section>`).join('');
}

const foot = () => `<footer class="foot"><span class="fl">${MARK(12)}George Allinson (Transport) Ltd · Website proposal</span><span>Code Canvas Consultants Ltd · ${esc(DATE)}</span></footer>`;

const PITCH = path.resolve('scripts/proposal/assets');
const stills = (list) => `<div class="stills">${list.map(([k, t, d]) => k
  ? `<figure><img src="${img(chunks[k].chunks[0])}" alt="${esc(t)}"><figcaption><b>${esc(t)}</b>${esc(d)}</figcaption></figure>`
  : `<div class="still-note"><b>${esc(t)}</b>${d}</div>`).join('')}</div>`;
const notes = (list) => `<div class="phone-notes">${list.map(([t, d]) => `<div><b>${esc(t)}</b>${esc(d)}</div>`).join('')}</div>`;
const phones = (list) => `<div class="phones">${list.map(([k, c]) => `<figure><img src="${img(chunks[k].chunks[0])}" alt="${esc(c)}"><figcaption>${esc(c)}</figcaption></figure>`).join('')}</div>`;

const html = `<!doctype html><html lang="en-GB"><head><meta charset="utf-8"><title>George Allinson (Transport) Ltd – Website Proposal</title>
<style>

  ${FONTS}
  @page { size: Letter; margin: 0.5in 0.6in 0.55in; }
  :root { --cab: #231f20; --cab-950: #171415; --bg: #f4efe9; --surface-2: #ebe3d9; --ink: #231f20; --ink-2: #4a4044; --ink-3: #5c5154; --line: #dccfc1; --gold: #d7b273; --red: #c81c22; --livery: #d92026; --red-soft: #fbe6e3; --label: #fffdf8; --goldbar: linear-gradient(180deg, #ece0a0, #d9c433); }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; font-family: "Trebuchet MS", "Fira Sans", system-ui, sans-serif; color: var(--ink); font-size: 10.5pt; line-height: 1.45; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  h1, h2, .display { font-family: "Roboto Condensed", "Arial Narrow", sans-serif; font-weight: 800; text-transform: uppercase; letter-spacing: 0; color: var(--ink); margin: 0; line-height: 0.98; }
  h3 { font-family: "Trebuchet MS", "Fira Sans", system-ui, sans-serif; font-weight: 700; margin: 0; font-size: 11.5pt; }
  .page { page-break-after: always; break-after: page; height: 9.95in; display: flex; flex-direction: column; overflow: hidden; }
  .page:last-child { page-break-after: auto; }
  .eyebrow { font-size: 8pt; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: var(--red); margin: 0 0 4px; }
  /* junction-number plate, as on the site */
  .plate { align-self: flex-start; display: inline-flex; align-items: stretch; border: 1.5px solid currentColor; padding: 2px; font-size: 7.5pt; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; line-height: 1; color: var(--ink-2); margin: 0 0 12px; }
  .plate span { display: inline-flex; align-items: center; padding: 5px 7px 3px; }
  .plate .c { background: var(--ink); color: var(--label); font-family: "Roboto Condensed"; font-weight: 800; font-size: 10pt; letter-spacing: .04em; }
  .plate.dark { color: rgba(255,255,255,.85); } .plate.dark .c { background: var(--label); color: var(--cab); }
  .chevrons { height: 12px; background: repeating-linear-gradient(-45deg, var(--livery) 0 9px, #fff 9px 18px); }
  /* cover and dividers: the site's charcoal band */
  .dark-page { background: var(--cab); color: #fff; padding: 0.6in 0.6in 0.55in; position: relative; overflow: hidden; }
  .dark-page h1 { color: #fff; }
  .cover { justify-content: space-between; background-image: linear-gradient(180deg, rgba(23,20,21,.45), rgba(23,20,21,.94) 60%), url("${img(path.join(ROOT, 'public/img/own-depot-night-1400.webp'))}"); background-size: cover; background-position: 55% 0; }
  .cover h1 { font-size: 50pt; margin: 14px 0 16px; max-width: 6.4in; }
  .cover .accent { color: var(--gold); }
  .cover .lead { color: rgba(255,255,255,.88); font-size: 12.5pt; max-width: 6in; margin: 0; }
  .cover .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; border-top: 1px solid rgba(255,255,255,.2); padding-top: 16px; font-size: 9.5pt; color: rgba(255,255,255,.82); }
  .cover .meta b { display: block; color: var(--gold); font-size: 8pt; letter-spacing: .14em; text-transform: uppercase; margin-bottom: 4px; }
  .brand { display: flex; align-items: center; gap: 10px; } .brand .n { display: block; font-family: "Roboto Condensed"; font-weight: 800; font-size: 22pt; text-transform: uppercase; color: #fff; line-height: .9; } .brand .s { display: block; margin-top: 4px; font-size: 7pt; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: rgba(255,255,255,.72); }
  .brand .ink { color: var(--ink); } .brand .s.ink { color: var(--ink-3); }
  .mark { display: block; flex: none; }
  .divider { justify-content: center; }
  .divider h1 { font-size: 46pt; margin: 6px 0 18px; } .divider p.intro { color: rgba(255,255,255,.86); font-size: 12pt; max-width: 6.1in; margin: 0; }
  .divider ol { color: rgba(255,255,255,.85); columns: 2; font-size: 10pt; margin: 24px 0 0; padding-left: 18px; } .divider li { break-inside: avoid; margin: 3px 0; } .divider li::marker { color: var(--gold); font-weight: 700; }
  .divider .note { margin-top: 22px; font-size: 9pt; color: rgba(255,255,255,.66); max-width: 6in; }
  .divider .chevrons, .cover .chevrons { position: absolute; left: 0; right: 0; bottom: 0; }
  /* text pages */
  .prose h1 { font-size: 30pt; margin-bottom: 12px; }
  .prose h2 { font-size: 16pt; margin: 16px 0 6px; }
  .prose p { margin: 0 0 8px; }
  .prose ul.plain { margin: 0 0 8px; padding-left: 16px; } .prose ul.plain li { margin: 2px 0; }
  .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .card { border: 1px solid var(--line); padding: 11px 13px; background: #fff; break-inside: avoid; }
  .card h3 { margin: -11px -13px 8px; padding: 6px 13px 5px; color: #fff; background: linear-gradient(180deg, #da2a2c, #d92026 55%, #c41a20); font-size: 10.5pt; }
  .card ul { margin: 0; padding-left: 15px; } .card li { margin: 2px 0; font-size: 9.6pt; }
  .evidence { border-left: 4px solid var(--red); background: var(--bg); padding: 10px 14px 10px 30px; margin: 8px 0 10px; }
  .evidence li { margin: 3px 0; }
  .src { font-size: 8pt; color: var(--ink-3); }
  .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 12px 0 6px; }
  .stat { background: var(--cab); color: #fff; padding: 12px 12px 11px; }
  .stat b { display: block; font-family: "Roboto Condensed"; font-weight: 800; font-size: 28pt; line-height: .9; color: #fff; border-left: 3px solid var(--gold); padding-left: 8px; }
  .stat span { display: block; margin-top: 8px; font-size: 8.5pt; color: rgba(255,255,255,.8); line-height: 1.3; }
  table.feat { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
  table.feat th, table.feat td { border-bottom: 1px solid var(--line); padding: 8px 8px; text-align: left; vertical-align: top; }
  table.feat th { background: var(--surface-2); font-size: 8pt; text-transform: uppercase; letter-spacing: .1em; color: var(--ink-3); }
  .pill-row { display: flex; flex-wrap: wrap; gap: 5px; } .pill { border: 1px solid #e3b4b1; background: var(--red-soft); color: var(--red); padding: 2px 9px 1px; font-size: 8.5pt; font-weight: 600; }
  /* screenshot pages */
  .shot-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; border-bottom: 5px solid transparent; border-image: var(--goldbar) 1; box-shadow: 0 2px 0 var(--ink); padding-bottom: 6px; margin-bottom: 9px; }
  .shot-head h2 { font-size: 20pt; } .url { font-family: Menlo, Consolas, monospace; font-size: 8pt; color: var(--ink-3); margin: 0; white-space: nowrap; }
  .bullets { margin: 0 0 7px; padding-left: 16px; font-size: 9.3pt; } .bullets li { margin: 1px 0; } .bullets li::marker { color: var(--red); }
  figure { margin: 0 0 8px; display: block; text-align: center; }
  figure img { display: block; margin: 0 auto; border: 1px solid var(--line); }
  .cap { text-align: left; margin: 5px 0 0; font-size: 8.6pt; line-height: 1.35; color: var(--ink-2); border-left: 3px solid var(--livery); padding-left: 8px; }
  .cap .n { font-weight: 700; color: var(--ink-3); }
  .stills { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 16px; margin-top: 10px; }
  .stills figure { margin: 0; text-align: left; }
  .stills img { width: 100%; aspect-ratio: 16 / 10; object-fit: cover; object-position: top; border: 1px solid var(--line); }
  .stills figcaption { font-size: 8.6pt; line-height: 1.35; color: var(--ink-2); margin-top: 5px; border-left: 3px solid var(--livery); padding-left: 8px; }
  .stills figcaption b, .still-note b { display: block; color: var(--ink); font-size: 9.5pt; }
  .still-note { background: var(--bg); padding: 12px 14px; font-size: 9pt; color: var(--ink-2); }
  .still-note ul { margin: 6px 0 0; padding-left: 15px; } .still-note li { margin: 3px 0; } .still-note li::marker { color: var(--red); }
  .compare { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 10px; }
  .compare figure { margin: 0; text-align: left; }
  .compare img { width: 100%; aspect-ratio: 16 / 11; object-fit: cover; object-position: top; border: 1px solid var(--line); }
  .compare figcaption { font-size: 9pt; font-weight: 700; margin-top: 5px; }
  .swatches { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin: 10px 0 4px; }
  .swatches div { font-size: 8pt; color: var(--ink-2); } .swatches i { display: block; height: 26px; border: 1px solid var(--line); margin-bottom: 4px; }
  .phones { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 12px; }
  .phones figure { margin: 0; }
  .phones img { width: 100%; border-radius: 14px; border: 7px solid var(--cab); }
  .phone-notes { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 14px; }
  .phone-notes div { background: var(--bg); padding: 9px 12px; font-size: 9pt; color: var(--ink-2); }
  .phone-notes b { display: block; color: var(--ink); font-size: 9.5pt; margin-bottom: 2px; }
  .phones figcaption { text-align: center; font-size: 9pt; color: var(--ink-2); margin-top: 6px; font-weight: 600; }
  /* investment */
  .price { display: grid; grid-template-columns: 1.45fr 1fr; gap: 14px; align-items: stretch; margin: 6px 0 4px; }
  .price .big { background: var(--cab); color: #fff; padding: 16px 18px; }
  .price .big .amt { white-space: nowrap; font-family: "Roboto Condensed"; font-weight: 800; font-size: 34pt; line-height: 1; color: #fff; } .price .big .amt small { font-family: "Trebuchet MS", "Fira Sans"; font-size: 11pt; font-weight: 600; color: rgba(255,255,255,.75); }
  .price .big p { color: rgba(255,255,255,.84); margin: 8px 0 0; font-size: 9.6pt; }
  .price .year { border: 2px solid var(--red); padding: 14px 16px; background: var(--red-soft); }
  .price .year div { display: flex; justify-content: space-between; gap: 8px; padding: 4px 0; border-bottom: 1px dotted #d99a96; font-size: 10pt; } .price .year div:last-child { border: 0; }
  .price .year b { font-weight: 700; }
  .terms li { margin: 3px 0; font-size: 9.6pt; }
  .sla { width: 100%; border-collapse: collapse; font-size: 9pt; margin-top: 4px; } .sla td, .sla th { border-bottom: 1px solid var(--line); padding: 4px 6px; text-align: left; } .sla th { font-size: 7.8pt; letter-spacing: .1em; text-transform: uppercase; color: var(--ink-3); background: var(--surface-2); }
  .weeks { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 6px 0 10px; }
  .week { border: 1px solid var(--line); padding: 10px 12px; background: #fff; }
  .week b { display: inline-block; font-family: "Roboto Condensed"; font-weight: 800; font-size: 10pt; text-transform: uppercase; background: var(--ink); color: var(--label); padding: 3px 6px 1px; margin-bottom: 5px; }
  .week p { margin: 0; font-size: 9.5pt; }
  footer.foot { margin-top: auto; padding-top: 6px; font-size: 7.8pt; color: #857a7f; display: flex; justify-content: space-between; border-top: 1px solid var(--line); align-items: center; } footer.foot .fl { display: inline-flex; align-items: center; gap: 6px; }

</style></head><body>

<section class="page dark-page cover">
  ${WORDMARK()}
  <div>
    ${plate('GA', 'Website proposal · ' + DATE, true)}
    <h1>We care for your cargo. <span class="accent">Now the website can too.</span></h1>
    <p class="lead">A new allinsontransport.co.uk that works on a phone, with haulage quotes routed to the right depot and accepted online, warehousing enquiries, driver applications without the Word form, and a back office that tracks every driver's CPC, tacho card and licence.</p>
  </div>
  <div class="meta">
    <div><b>Prepared for</b>George Allinson and David Allinson, Directors<br>George Allinson (Transport) Ltd<br>Faverdale North, Darlington DL3 0PH</div>
    <div><b>Prepared by</b>Keron Lewis, Code Canvas Consultants Ltd<br>Port of Spain · codecanvastt.com<br>info@codecanvastt.com</div>
  </div>
  <div class="chevrons"></div>
</section>

<section class="page prose">
  ${plate('01', 'Executive summary')}
  <h1>Why rebuild now</h1>
  <p>George Allinson (Transport) Ltd has been family run since 1955. Its own website describes a modern fleet of 120 vehicles, 250 trailers and 250,000 sq ft of flexible warehousing, run from Faverdale with facilities in Yorkshire, Gloucestershire, Cambridgeshire and Liverpool. The website that says so is much older than the fleet. When we looked on 27 October 2026:</p>
  <ul class="evidence">
    <li><b>It is a 2011-era build.</b> The pages are hand-coded XHTML with no mobile layout, so on a phone a customer or driver pinches and zooms a desktop page.</li>
    <li><b>Its software is out of date.</b> It loads jQuery 1.5, a 2011 release, and much of the text is pasted from Word with its formatting markup.</li>
    <li><b>Drivers apply by downloading a Word file</b> called Employment-Application-Form-2012-Website.doc, then printing, filling in and returning it. That is hard to do from a phone.</li>
  </ul>
  <p class="src">Sources: allinsontransport.co.uk, home page, "About Us" and "Join Allinson Transport" (page.php?id=26), viewed 27 October 2026.</p>
  <p>This proposal replaces it with a website and back office built for a haulier that recruits drivers all year: quotes routed by collection postcode to the right depot and accepted online, warehousing enquiries, careers by depot with a five-minute online application, and compliance tracking that emails HR before a driver's CPC, tacho card or licence runs out. A working demo is built and can be shown live, on a laptop or a phone.</p>
  <div class="stat-grid">
    <div class="stat"><b>${SCREEN_COUNT}</b><span>screens designed, built and shown in this proposal</span></div>
    <div class="stat"><b>5</b><span>depots, each with its own quote queue</span></div>
    <div class="stat"><b>4</b><span>staff roles, each seeing only its own registers</span></div>
    <div class="stat"><b>${TESTS}</b><span>automated tests run before every release</span></div>
  </div>
  <h2>What the company gains</h2>
  <div class="cols">
    <div class="card"><h3>Customers and drivers</h3><ul><li>A site that reads properly on a phone</li><li>A quote request in two minutes, and a price they can accept online</li><li>A job application in five minutes, with no Word form</li></ul></div>
    <div class="card"><h3>Traffic, warehouse and HR</h3><ul><li>Every quote in its depot's queue, collecting soonest first</li><li>Applicants tracked through checks to an assessment drive</li><li>A morning email before any driver's card or CPC expires</li></ul></div>
  </div>
  ${foot()}
</section>

<section class="page prose">
  ${plate('02', 'At a glance')}
  <h1>What is included</h1>
  <table class="feat"><thead><tr><th style="width:20%">Area</th><th>Features</th></tr></thead><tbody>
    ${FEATURES.map(([a, f]) => `<tr><td><b>${esc(a)}</b></td><td><div class="pill-row">${f.map((x) => `<span class="pill">${esc(x)}</span>`).join('')}</div></td></tr>`).join('')}
  </tbody></table>
  <h2>How a quote flows</h2>
  <p>A shipper in Leeds needs a part load to Gloucester on Thursday. They choose the job type, enter the postcodes, dates, weight and pallet count, and tick ADR if the goods are hazardous. The site issues a reference such as <b>QR-26-00014</b>, emails it to them, places the request in the queue of the depot that owns the collection postcode, and alerts the traffic office. A planner prices it in the back office and presses Email the quote. The shipper receives a private link, sees the price and validity on a despatch label, and accepts or declines with one click. The planner sees the answer at once. If the request should go to another depot, traffic moves it.</p>
  <h2>How a driver is hired</h2>
  <p>A C+E driver finds the vacancy in Google job search, applies from a phone and declares licence categories, points, CPC and tacho card expiry. HR screens the application, sends a private link for licence and card copies, records the right-to-work and reference checks and books an assessment drive. The Hire button stays locked until every check is done. Hiring moves the driver straight into compliance tracking, and HR is emailed at 90, 60, 30 and 14 days before each card expires, and on the day it does.</p>
  <h2>Content and imagery</h2>
  <p>Every figure, service and sector on the site comes from the company's own pages and is quoted as published, with the page linked. Nothing is invented: no figures, customers, testimonials, accreditations or staff names. Most photographs are the company's own, from its current website, and stay only with its permission; the hero shows one of its lorries in a licensed Geograph photo, and the few others are licensed from Wikimedia Commons, credited on the site and marked illustrative. Depot addresses other than Faverdale are not published anywhere current, so the demo asks visitors to call head office until they are confirmed. Quotes, applicants and drivers in these screenshots are clearly marked samples with fictitious names.</p>
  ${foot()}
</section>

${SECTIONS.map((sec) => `
<section class="page dark-page divider">
  ${plate(sec.code, sec.eyebrow, true)}
  <h1>${esc(sec.title)}</h1>
  <p class="intro">${esc(sec.intro)}</p>
  <ol>${sec.screens.map((s) => `<li>${esc(s[1])}</li>`).join('')}</ol>
  <p class="note">Screens are from the working demo at 1440 pixels wide (back office at 1280). Quotes, enquiries, applicants, drivers, staff names and prices are sample data.</p>
  <div class="chevrons"></div>
</section>
${sec.screens.map((s) => screenPages(sec, s)).join('')}
`).join('')}

<section class="page">
  <header class="shot-head"><div><p class="eyebrow">Responsive design</p><h2>Built for phones first</h2></div><p class="url">390 × 844</p></header>
  <p style="margin:0 0 4px">Drivers look for work on a phone, and a transport buyer often checks a supplier between calls. Every page adapts to a small screen, and a bar with Call, Jobs and Get a quote stays within thumb reach.</p>
  ${phones([['m-home', 'Home'], ['m-quote', 'Quote request'], ['m-apply', 'Job application']])}
  ${notes([['Thumb-sized controls', 'Every button and choice is at least 44 pixels tall.'], ['The right keyboard', 'Phone fields open the number pad, and contact fields fill in from the phone\'s saved details.'], ['Light on data', 'Photos are served as WebP in several sizes, so a phone downloads the small one.']])}
  ${foot()}
</section>
<section class="page">
  <header class="shot-head"><div><p class="eyebrow">Responsive design · continued</p><h2>Customers and the office on a phone</h2></div><p class="url">390 × 844</p></header>
  <p style="margin:0 0 4px">A customer can accept a quote from the link in their email, and a planner or HR officer can check the queue from the yard.</p>
  ${phones([['m-quote-response', 'The customer\'s quote'], ['m-admin-dashboard', 'Back-office overview'], ['m-admin-application', 'Applicant checks']])}
  ${notes([['Accept in one tap', 'The quote link works on any phone and expires when the price does.'], ['Due first', 'Each overview opens on what that role has to act on today.'], ['One tap to call', 'Call or email the customer or applicant from the record.']])}
  ${foot()}
</section>

<section class="page">
  <header class="shot-head"><div><p class="eyebrow">Brand</p><h2>Still unmistakably Allinson</h2></div><p class="url">allinsontransport.co.uk</p></header>
  <p style="margin:0 0 4px">The preview keeps what customers and drivers already recognise from the current site and the fleet: the black-and-gold GA badge, the company name in bold capitals, the gold navigation bar, red box headings, the beige page and "We care for your cargo" from the cab roofs. The badge is redrawn as a sharp vector so it stays crisp at any size; the spacing, type sizes, buttons and phone layout are new.</p>
  <div class="compare">
    <figure><img src="${img(path.join(PITCH, 'current-site-2026-10-28.jpg'))}" alt="The current website"><figcaption>Today: allinsontransport.co.uk, October 2026</figcaption></figure>
    <figure><img src="${img(chunks['home'].chunks[0])}" alt="The new home page"><figcaption>The preview: same badge, colours and navigation, built for 2026</figcaption></figure>
  </div>
  <div class="swatches">
    <div><i style="background:#d92026"></i>Livery red #d92026</div>
    <div><i style="background:#d7b273"></i>Badge gold #d7b273</div>
    <div><i style="background:linear-gradient(180deg,#ece0a0,#d9c433)"></i>Navigation gold</div>
    <div><i style="background:#231f20"></i>Cab black #231f20</div>
    <div><i style="background:#f4efe9"></i>Page beige #f4efe9</div>
  </div>
  <p class="src">Colours are taken from the current site's stylesheet and images and from the fleet livery, darkened slightly where needed so text passes WCAG AA contrast. Type: Trebuchet MS, as on the current site, with Roboto Condensed for headings in the spirit of the name lettering. A dark theme is built from the same palette.</p>
  ${foot()}
</section>

<section class="page">
  <header class="shot-head"><div><p class="eyebrow">Motion and interaction</p><h2>Movement that means something</h2></div><p class="url">stills from the live demo</p></header>
  <p style="margin:0 0 4px">Each effect shows something true about the business: the badge, the published figures, the depots, a load moving from collection to delivery. It is light, built without heavy libraries, and switched off for anyone whose phone or computer asks for reduced motion.</p>
  ${stills([
    ['mo-hero', 'The badge draws itself', 'On arrival the GA badge draws in gold, "We care for your cargo" rises word by word and the despatch label drops into place, over the company\'s own night photo of the depot.'],
    ['mo-count', 'Published figures count up', '120 vehicles, 250 trailers and 250,000 sq ft count up once, as the company publishes them. The final numbers are in the page for search engines and screen readers.'],
    ['mo-map', 'Routes draw out from Darlington', 'Routes draw from Faverdale to each region. Only Faverdale is pinned as a site; the other markers show the region, until the depot addresses are confirmed.'],
    ['mo-quote', 'A lorry follows the quote', 'As the shipper types, the postcodes appear at each end of the road and the lorry moves along as each part of the form is complete.'],
  ])}
  ${foot()}
</section>

<section class="page">
  <header class="shot-head"><div><p class="eyebrow">Motion and interaction · continued</p><h2>From a 2012 Word form to a phone</h2></div><p class="url">stills from the live demo</p></header>
  ${stills([
    ['mo-story', 'Applying, told as a scroll story', 'The careers page strikes out the old Word file, then a red road fills and a lorry drives down the five steps as the driver scrolls: apply, call-back, documents, assessment drive, start.'],
    ['mo-thanks', 'Received, stamped and on its way', 'After any form, the consignment label carrying the reference is stamped "Received" and the lorry drives off.'],
    ['mo-theme', 'Light and dark themes', 'The dark theme is built from the cab black, livery red and badge gold and follows the visitor\'s phone setting; the switch in the header wipes it across from the button.'],
    [null, 'Considerate by design', '<ul><li>Reduced-motion setting: nothing moves, everything is shown.</li><li>No JavaScript: every word, figure and step is still on the page.</li><li>The moving band of services pauses on hover, on keyboard focus or with its own button.</li><li>The gold bar picks up the badge as you scroll, and a red line shows how far down the page you are.</li><li>Only smooth, low-cost properties are animated, so it stays fluid on older phones.</li></ul>'],
  ])}
  ${foot()}
</section>

<section class="page prose">
  ${plate('04', 'Technology, hosting & security')}
  <h1>Built properly, hosted for you</h1>
  <div class="cols">
    <div class="card"><h3>Platform</h3><ul><li>Laravel 13 on PHP 8.3 with a MySQL database: open source and widely supported, with no proprietary page builder</li><li>Tailwind CSS and Alpine.js; fonts served from your own site, not a third-party font service</li><li>No background servers; emails send directly and one daily task sends the compliance reminders</li><li>${TESTS} automated tests cover every form, role, register and private document</li></ul></div>
    <div class="card"><h3>Security and privacy</h3><ul><li>HTTPS enforced, with security headers on every page</li><li>Delivery notes, CVs and licence and card copies are stored outside the public website and opened only by signed-in staff with the right role</li><li>Every opening of a private file is logged</li><li>Quote and document links are signed, expire, and are never indexed</li><li>A privacy notice written for UK GDPR and the Data Protection Act 2018</li><li>Rate-limited, spam-trapped forms; login throttling and individual staff accounts</li></ul></div>
    <div class="card"><h3>Speed and search</h3><ul><li>Responsive WebP photography</li><li>XML sitemap, and schema.org data for the company and every job posting, so vacancies can appear in Google job search</li><li>The old page.php addresses redirect permanently to the new pages</li><li>Accessible: 17px body text, keyboard navigation, labelled forms, contrast checked in light and dark themes</li></ul></div>
    <div class="card"><h3>Ownership</h3><ul><li>Your content, photos, domain and data are always yours</li><li>Staff manage vacancies, quotes, applicants, drivers and users themselves</li><li>Hosting, backups, monitoring and updates are handled by Code Canvas under the monthly plan</li><li>Your Microsoft 365 email stays exactly where it is</li></ul></div>
  </div>
  <h2>Emails included</h2>
  <p>Every submission sends the sender a confirmation with their reference number and what happens next, and alerts the right inbox (traffic, the warehouse or HR) with a link to the record. Customers receive their price with a link to accept or decline; applicants receive their document-upload link; HR receives one morning email listing the drivers' cards that have reached a reminder date. Private files are never attached to emails.</p>
  ${foot()}
</section>

<section class="page prose">
  ${plate('05', 'Investment')}
  <h1>The US$1,000 a month plan</h1>
  <div class="price">
    <div class="big"><div class="amt">US$1,000 <small>per month</small></div><p><b>No build fee and no deposit.</b> Invoiced in US dollars. Billing starts at the end of the month in which the site launches, prorated for the days it was live; monthly invoices are due within 14 days.</p></div>
    <div class="year"><div><span>Year one</span><b>US$12,000</b></div><div><span>Or paid upfront for the year (10% off)</span><b>US$10,800</b></div><div><span>Build fee</span><b>US$0</b></div></div>
  </div>
  <div class="cols" style="margin-top:8px">
    <div class="card"><h3>Included every month</h3><ul>
      <li>The complete design and build shown in this proposal</li>
      <li>Managed hosting with SSL</li>
      <li>Daily backups plus a weekly off-site copy</li>
      <li>Security patches and software updates</li>
      <li>24/7 uptime monitoring</li>
      <li><b>Up to 10 hours a month</b> of changes and small improvements, such as new vacancies or depot details</li>
      <li>Staff training</li>
      <li>A monthly summary of traffic, enquiries and uptime</li>
      <li>Support by email, WhatsApp and phone, plus a quarterly review call</li>
      <li>A 30-day post-launch warranty</li>
    </ul></div>
    <div class="card"><h3>Terms</h3><ul class="terms">
      <li>No minimum term. 30 days' notice by email from either side.</li>
      <li>Price fixed for the first 24 months.</li>
      <li>10% off a year paid upfront: US$10,800.</li>
      <li><b>Handover fee US$7,000, only if you leave.</b> It buys the website outright and moves it to hosting in George Allinson (Transport) Ltd's name. It is waived if Code Canvas stops trading.</li>
      <li>Your content, domain and data are always yours.</li>
    </ul>
    <h3 style="margin-top:8px">Quoted separately, only with your approval</h3><ul>
      <li>Work beyond 10 hours in a month: US$50 an hour, or a fixed quote</li>
      <li>SMS reminders to drivers or customers</li>
      <li>Integration with your transport management system or telematics</li>
      <li>A customer login with track and trace; paid advertising and SEO campaigns; professional photography or drone footage</li>
    </ul></div>
  </div>
  <h2>Service levels</h2>
  <table class="sla"><thead><tr><th>Issue</th><th>Response</th><th>Fix target</th></tr></thead><tbody>
    <tr><td>Site down or forms not sending</td><td>1 hour, any day</td><td>4 hours</td></tr>
    <tr><td>Something on a page is broken</td><td>4 business hours</td><td>1 business day</td></tr>
    <tr><td>Content changes (text, photos, vacancies, depots)</td><td>Acknowledged in 1 business day</td><td>Done in 2 business days</td></tr>
  </tbody></table>
  ${foot()}
</section>

<section class="page prose">
  ${plate('06', 'Timeline')}
  <h1>Six weeks to launch</h1>
  <div class="weeks">
    <div class="week"><b>Week 1</b><p>Kick-off, brand assets and content: your logo files, photographs and confirmation of every fact and depot on the site.</p></div>
    <div class="week"><b>Week 2</b><p>Revision round 1. Forms, emails, depots and staff roles configured with your real inboxes.</p></div>
    <div class="week"><b>Week 3</b><p>Content load: depot addresses and postcode areas, open vacancies, your application questions.</p></div>
    <div class="week"><b>Week 4</b><p>Revision round 2.</p></div>
    <div class="week"><b>Week 5</b><p>Testing with your traffic, warehouse and HR staff on every form and screen; current drivers' card dates loaded.</p></div>
    <div class="week"><b>Week 6</b><p>Launch on HTTPS, redirects from the old addresses, Search Console, and staff training.</p></div>
  </div>
  <h2>What we need from George Allinson (Transport) Ltd</h2>
  <ul class="plain">
    <li>Your logo as a vector file (the demo redraws the GA badge from the current website) and permission to use your photographs.</li>
    <li>The address, phone number and postcode areas of each regional depot.</li>
    <li>Confirmation of the facts shown, and any accreditations or memberships you want listed, with certificate details.</li>
    <li>The staff who need accounts, their roles and depots, and the inboxes for each traffic office, the warehouse and HR.</li>
    <li>Your ICO registration number and retention periods for the privacy notice.</li>
    <li>Access to the allinsontransport.co.uk domain settings (your Microsoft 365 email records stay untouched).</li>
  </ul>
  <h2>When billing starts</h2>
  <p>Not on signing. The build, both revision rounds, testing and the preview site cost nothing. Billing starts at the end of the month in which the site launches, prorated for the days it was live. Nothing goes live until you approve it in writing.</p>
  ${foot()}
</section>

<section class="page prose">
  ${plate('07', 'Next steps')}
  <h1>From proposal to launch</h1>
  <ol class="plain" style="padding-left:18px">
    <li style="margin:5px 0"><b>See the demo live.</b> We walk through the website and the back office together, on a laptop and a phone.</li>
    <li style="margin:5px 0"><b>Tell us what to change.</b> Pages, wording, roles, depots or the application questions; revision round 1 opens straight away.</li>
    <li style="margin:5px 0"><b>Sign a one-page agreement</b> with the terms in this proposal: US$1,000 a month, no minimum term and 30 days' notice, no build fee, and a US$7,000 handover fee only if you leave.</li>
    <li style="margin:5px 0"><b>Kick-off the same week</b>, with launch six weeks later.</li>
  </ol>
  <div class="card" style="margin-top:18px; max-width:4.6in">
    <h3>Contact</h3>
    <p style="margin:0">Keron Lewis, Director<br>Code Canvas Consultants Ltd, Port of Spain<br>codecanvastt.com<br><a href="mailto:info@codecanvastt.com">info@codecanvastt.com</a><br><a href="https://calendly.com/keronlewis/30min">Book a call: calendly.com/keronlewis/30min</a></p>
  </div>
  <div style="margin-top:auto; margin-bottom:12px">${WORDMARK(false)}</div>
  ${foot()}
</section>
</body></html>`;

fs.writeFileSync(OUT_HTML, html);
const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox', '--allow-file-access-from-files'] });
const page = await browser.newPage();
await page.goto('file://' + OUT_HTML, { waitUntil: 'networkidle0' });
await page.evaluate(() => document.fonts.ready);
await page.pdf({ path: OUT_PDF, format: 'Letter', printBackground: true, preferCSSPageSize: true, displayHeaderFooter: false });
await browser.close();
const mb = Math.round(fs.statSync(OUT_PDF).size / 1024 / 1024 * 10) / 10;
console.log('PDF written:', OUT_PDF, mb + ' MB');
if (mb > MAX_MB) {
  console.error(`The PDF is over ${MAX_MB} MB, too large for most email attachments. Lower the JPEG quality in capture.mjs.`);
  process.exit(1);
}
