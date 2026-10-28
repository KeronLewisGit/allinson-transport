<x-layouts.site title="Privacy notice" description="How George Allinson (Transport) Ltd handles personal data sent through this website, under UK GDPR and the Data Protection Act 2018.">
    <x-page-header photo="own-depot-night" position="center 60%" kicker="Privacy" title="Privacy notice" lead="What we keep when you ask for a quote, enquire about warehousing, apply for a job or send a message, why we keep it and for how long." :label="['Law' => 'UK GDPR', 'Also' => 'DPA 2018', '*Controller' => ga('legal_name')]" />
    <section class="wrap prose-copy measure mt-12 md:mt-16">
        <h2>Who we are</h2>
        <p>The data controller is {{ ga('legal_name') }}, company number {{ ga('company_number') }}, registered in England and Wales, of {{ ga('address.street') }}, {{ ga('address.city') }}, {{ ga('address.county') }} {{ ga('address.postcode') }}. This notice is written for the UK General Data Protection Regulation (UK GDPR) and the Data Protection Act 2018. Questions about your data: <a href="mailto:{{ ga('email') }}">{{ ga('email') }}</a> or {{ format_phone(ga('phone')) }}.</p>

        <h2>What we collect, and our lawful basis</h2>
        <ul>
            <li><strong>Quote requests and warehousing enquiries:</strong> your name, company, email, phone, collection and delivery details, and any files you attach. Basis: taking steps at your request before entering a contract, and our legitimate interest in replying to business enquiries.</li>
            <li><strong>Job applications:</strong> your name, contact details, home postcode, experience, licence categories, Driver CPC and tacho card expiry dates, penalty points and endorsement codes, qualifications and CV. Basis: taking steps before entering an employment contract, and our legal obligations as an operator licence holder and employer (including the right-to-work check). Penalty points and endorsements relate to driving offences; we use them only to assess whether you can be insured and permitted to drive our vehicles, under Schedule 1 of the Data Protection Act 2018.</li>
            <li><strong>Copies of licences, cards and right-to-work documents:</strong> only if HR sends you a private upload link. Basis: legal obligation (right-to-work and driver checks).</li>
            <li><strong>Staff compliance records:</strong> for people we employ, card and licence expiry dates so we can remind drivers before they lapse. Basis: legal obligation.</li>
            <li><strong>Messages:</strong> your name, contact details and message. Basis: legitimate interest in replying.</li>
        </ul>

        <h2>Who sees it</h2>
        <p>Each kind of submission is seen only by the staff who handle it (the traffic office for quotes, the warehouse team for warehousing, HR for applications and compliance) and the site administrator. We do not sell your data or use it for marketing. Our website host and email provider process it for us under contract, within the UK or under UK adequacy arrangements.</p>

        <h2>Where it is kept</h2>
        <p>Uploads are stored in a private area of our web server that cannot be reached from the internet; staff open them only after signing in, and each opening is logged. Links we email to you (a quote to accept, or a page to upload documents) are private to you and expire.</p>

        <h2>How long we keep it</h2>
        <ul>
            <li>Quote requests and warehousing enquiries that do not lead to work: up to 2 years.</li>
            <li>Work we carry out: as long as accounting and contract law require (normally 6 years after the end of the financial year).</li>
            <li>Unsuccessful job applications: {{ ga('careers.retention_months') }} months after the vacancy closes, then deleted.</li>
            <li>Right-to-work copies for people we employ: for the employment and 2 years after it ends, as the Home Office requires.</li>
        </ul>

        <h2>Your rights</h2>
        <p>You can ask to see, correct or delete the personal data we hold about you, to restrict or object to how we use it, or to receive a copy to take elsewhere. Email <a href="mailto:{{ ga('email') }}">{{ ga('email') }}</a> and quote your reference if you have one. We reply within one month. If you are unhappy with how we handle your data, you can complain to the Information Commissioner's Office at <a href="https://ico.org.uk/make-a-complaint/" rel="noopener">ico.org.uk</a> or on 0303 123 1113.</p>

        <h2>Cookies</h2>
        <p>This site uses one strictly necessary session cookie to keep forms secure, and remembers your light or dark mode choice in your browser. There is no advertising, analytics or tracking, so no cookie banner is needed.</p>
        <p class="hint">Last updated {{ now()->format('F Y') }}. To be reviewed by the company, with its ICO registration number, before launch.</p>
    </section>
</x-layouts.site>
