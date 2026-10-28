# Allinson Transport

Concept website and back office for George Allinson (Transport) Ltd (company 00960649), a family-run road haulage, warehousing and logistics company established in 1955, head office at Faverdale North, Faverdale Industrial Estate, Darlington DL3 0PH. Built as an unsolicited pitch by Code Canvas Consultants: the company has not commissioned it, so nothing on the site may go beyond what its own pages and Companies House state. The current allinsontransport.co.uk is a 2011-era hand-coded XHTML site (no mobile layout, jQuery 1.5, text pasted from Word), and drivers apply by downloading `Employment-Application-Form-2012-Website.doc`.

## What it does

- **Public site.** Home, services (six lines of work), fleet and warehousing, depots, about, contact, careers, privacy notice (UK GDPR / Data Protection Act 2018), photo credits and a site map. Every figure (120 vehicles, 250 trailers, 250,000 sq ft, the 50,000 sq ft container yard for 500 TEU, 45,000 kg stacker) is quoted exactly as the company publishes it, with the source page linked. en-GB, GBP, UK phone formatting. Mobile-first, 17px body text, light and dark themes, a Call / Jobs / Get a quote bar on phones. The old site's `page.php?id=N` addresses and `/index.php` redirect (301) to the new pages.
- **Four intake flows, each with a reference number and an email confirmation.**
  - Haulage quote request (`QR-26-00014`): full load, part load, pallets, Hiab, abnormal or store-then-deliver; goods, weight, pallets or dimensions (shown only when the job needs them), ADR and tail-lift flags, collection and delivery postcodes and dates, up to 3 files. The collection postcode area routes it to the depot that prices it (`App\Support\Depots::forPostcode()`, falling back to Darlington); traffic can move it.
  - Warehousing enquiry (`WH-…`): pallet spaces, duration, goods type, handling and dates.
  - Job application (`JA-…`): C+E driver, Hiab driver, warehouse or transport office, chosen by depot. Driving roles self-declare licence categories, points and endorsements, Driver CPC expiry and tacho card expiry. CV optional.
  - Contact form (`EN-…`), with a "free logistics benchmark" topic (`/contact?topic=benchmark`).

  Uploads go to the private `local` disk (`storage/app/private/{quotes,cvs,applicants}`). If a form comes back with an error, files that passed are held (`storage/app/private/held`) and pruned after 24 hours.
- **Signed links.** Traffic prices a quote and emails the customer a link (`/your-quote/{ulid}`, expires with the quote's validity or after 30 days) to accept or decline. HR emails an applicant a 14-day link (`/careers/documents/{ulid}`) to upload licence, CPC card, tacho card and right-to-work copies. Both are `noindex` and `no-referrer`.
- **Back office** at `/admin`, with four roles:
  - **Admin**: everything, including staff accounts.
  - **Traffic**: quote requests (their own depot first) and contact enquiries.
  - **Warehouse**: warehousing enquiries.
  - **HR**: vacancies, applications and driver compliance.

  The overview leads with one number per role: open quotes at the user's depot, warehousing enquiries waiting for a call back, or drivers whose CPC, tacho card or licence has expired or expires within 60 days. Applications move New → Screening → Checks (right to work, references, documents) → Assessment drive (date and result) → Offer → Hired; Hire is blocked until every check is done and creates the driver's compliance record. Every register has filters, search and CSV export; every record has a notes timeline; every opening of a private file is logged.
- **Compliance reminders.** `php artisan app:compliance-reminders` (daily 07:00 Europe/London) emails HR one digest of drivers whose card or CPC has crossed 90, 60, 30 or 14 days, or expires today. Each threshold is claimed once per card and resets when the date changes. `--dry-run` lists without sending.

## Requirements

PHP 8.3 or 8.4, Composer 2, Node 20+ for building assets (and Chrome for the proposal), SQLite locally or MySQL in production.

## Local setup

```sh
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seeder=DemoSeeder   # demo staff, 16 quotes, 6 warehousing enquiries, 6 vacancies, 18 applications, 18 staff
npm install && npm run build
php artisan serve --port=8115
```

Demo logins all use the password `password`: `admin@allinson-transport.test`, `traffic@allinson-transport.test` (Darlington), `traffic.yorkshire@allinson-transport.test`, `warehouse@allinson-transport.test`, `hr@allinson-transport.test`. Every demo customer, applicant and driver is fictional and marked "(sample)". `DatabaseSeeder` only creates the first admin from `ADMIN_*`. `DemoSeeder` refuses to run in production unless `ALLINSON_PREVIEW=true`.

## Checks

```sh
php artisan test                                          # feature tests for every flow, role, policy and private file
/opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpstan analyse
vendor/bin/pint --test
node scripts/screens.mjs <dir>                            # phone/desktop × light/dark screenshots, overflow report
```

The suite also passes on MySQL (`DB_CONNECTION=mysql DB_DATABASE=… php artisan test`).

## Deploying

See [INSTALL-HOSTINGER.md](INSTALL-HOSTINGER.md). `scripts/package-hostinger.sh` builds `dist/allinson-transport-hostinger.zip`, which extracts into `public_html/` with the app in `allinson-app/`. One cron job (`schedule:run` every minute) runs the compliance reminders. Mail for allinsontransport.co.uk is on Microsoft 365: send through an M365 mailbox and leave the MX, SPF, DKIM and autodiscover records alone.

## Proposal

With the dev server on 8115 and demo data loaded, `npm run proposal` captures every screen and builds `dist/proposal/Allinson-Transport-Website-Proposal.pdf` (Letter; the build fails over 9.5 MB). Set `TEST_COUNT` to the current test count.

The "Brand" and "Motion and interaction" pages use stills captured with motion switched on (`MOTION` in `scripts/proposal/capture.mjs`) and a screenshot of the current site (`scripts/proposal/assets/current-site-2026-10-28.jpg`). The thank-you still submits one contact form, so reseed the demo data first.

`node scripts/proposal/record.mjs out.webm` records the home-page motion (about 24 s); convert it with `ffmpeg -i out.webm -vf fps=30,format=yuv420p -c:v libx264 -crf 24 -movflags +faststart -an Allinson-Transport-Home-Motion.mp4`.

## Before launch: confirm with the company

- **Regional depots.** The company's site names facilities in Yorkshire, Gloucestershire, Cambridgeshire and Liverpool (and "Merseyside" on the UK Transport page), but no current source gives their addresses or phone numbers, so `/depots` shows only the Faverdale head office and asks visitors to call it. Get each address, phone and the depot names they use. An older source mentions a Cinderford (GL14) site from 2004; do not use it unless confirmed.
- **Postcode routing.** The postcode areas each depot prices (`allinson.depots.*.areas`) are a reasonable first split, not the company's. It is not shown publicly. Get the real split from the traffic offices.
- **Photo rights.** The `own-*` photos come from the company's current website; confirm it owns them and is happy for them to be reused. The home hero is `own-depot-night`, the night photo of the Faverdale depot that is the background of every page on the current site (`images/allinson_bg.jpg`, only 1400 px wide: ask for the original). The about and credits headers (`a40`, lorry 353 on the A40 at Monmouth) are CC BY-SA 2.0 from Geograph; `a1m`, `warehouse` and `tacho` are Wikimedia Commons and marked illustrative. All are credited on `/photo-credits`.
- **Accreditations and memberships.** The old site mentions ISO 9001 (QAS certificate CA14661), FORS Bronze, RHA and UKWA membership, Organic Food Federation registration and awards. None is shown, because none could be verified as current. Ask for current certificates before adding any.
- **The "12 Euro-5 HGVs" claim** on the old CSR page is dated and is not used.
- **Staff names.** The old site names Alan Barnfather and Michael Allinson. No staff are named on the new site. George and David Allinson (directors, Companies House) appear only as addressees of the proposal.
- **Fax number** on the old site is not shown; confirm whether it is still in use.
- **Old-site features not rebuilt:** the customer login and the downloadable brochure. Ask what the login is for (track and trace?) and whether the brochure should return.
- **Legacy redirects.** `allinson.legacy_pages` maps old `page.php?id=N` pages to new ones by their content; check against the live site's analytics before launch.
- **Privacy notice.** Add the ICO registration number, confirm the retention periods (unsuccessful applications 6 months, `allinson.careers.retention_months`) and the data controller wording.
- **Site credit.** The current site credits Vista Design. Confirm the company's arrangement with them before the new site replaces it.
- **Logo and brand.** `<x-badge>`, `<x-logo>` and `public/favicon.svg` are an SVG redraw of the black-and-gold GA badge in the current site's `title_logo.png`; ask for the original vector artwork and confirm the redraw may be used. The colours come from the current site's stylesheet and images (beige `#dccfc1` page, gold navigation bar `#e9db94`→`#d9c433`, red box headers `#e67d62`→`#da1f26`, link red `#d92026`, badge gold `#d8b058`) and from the fleet livery; the red is darkened to `#c81c22` for small text so it passes AA. The current site sets its text in Trebuchet MS, which the new site keeps (Fira Sans where it is not installed); headings use Roboto Condensed as the nearest open font to the condensed name lettering. Confirm whether the company has brand guidelines or a signwriter's font for the livery.
- **Wording taken from the current site.** "We care for your cargo" (cab roofs and current site), and from its Messages panel "Take the Allinson challenge" and "Show us your order book and we'll show you logistics savings" (used on the benchmark card). The same panel's "Outstanding safety award 2009", "Haulier of the year" and "ISO:9001 compliant" are not used (unverified, see above).
- **Depot map.** The UK outline is Natural Earth (public domain), credited on `/photo-credits`. Only Darlington is drawn as a site; the other markers sit in the middle of each region and are labelled as regions until the addresses are confirmed. Move them to the real sites when known (`resources/views/components/depot-map.blade.php`).
- **Motion.** The home, quote, careers, depots and thank-you pages have animation (see CLAUDE.md). Everything is off when the visitor's device asks for reduced motion, and the content is complete without JavaScript. Walk the company through it in the demo; any effect can be removed on request.
- **Inboxes** for `ALLINSON_NOTIFY_*`, the M365 sending mailbox, the staff list with roles and depots, and each current driver's CPC, tacho card and licence dates for the compliance register.
- Set `ALLINSON_PREVIEW=false` at launch (removes the concept banner and `noindex`).
