<?php

/*
|--------------------------------------------------------------------------
| George Allinson (Transport) Ltd: facts the site is built from
|--------------------------------------------------------------------------
| Every fact here is from the firm's own website (allinsontransport.co.uk, read 27 October
| 2026; the same wording is in the Wayback Machine back to 2012–2016) or from Companies House
| (company 00960649). The source page is named next to each figure ('sources' below) and the
| public pages cite it. Nothing is invented: no staff names, awards, accreditations,
| customers or figures beyond what a source states. Depot addresses other than the Darlington
| head office were not found in any current public source, so none is shown. See "Before
| launch" in README.md. Read with ga('key').
*/

return [

    'name' => 'George Allinson (Transport) Ltd',
    'legal_name' => 'George Allinson (Transport) Limited',
    'short_name' => 'Allinson Transport',
    'alternate_names' => ['Allinson Transport', 'George Allinson Transport', 'George Allinson (Transport) Limited'],
    'company_number' => '00960649',
    'founded' => 1955,
    'incorporated' => '21 August 1969',
    'description' => 'George Allinson (Transport) Ltd is a family-run road haulage, warehousing and logistics company, established in 1955, with its head office at Faverdale, Darlington, and facilities in Yorkshire, Gloucestershire, Cambridgeshire and Liverpool.',
    'footer_line' => 'Family-run road haulage, warehousing and logistics, established in 1955.',
    // Lettered on the firm's trailers and cab sides ("We care for your cargo").
    'tagline' => 'We care for your cargo',

    'phone' => '01325 461241',
    'email' => env('ALLINSON_PUBLIC_EMAIL', 'info@allinsontransport.co.uk'),
    'address' => [
        'street' => 'Faverdale North, Faverdale Industrial Estate',
        'city' => 'Darlington',
        'county' => 'County Durham',
        'postcode' => 'DL3 0PH',
        'country' => 'United Kingdom',
    ],
    'website' => 'www.allinsontransport.co.uk',
    'map_url' => 'https://www.google.com/maps/search/?api=1&query=George+Allinson+Transport+Faverdale+North+Darlington+DL3+0PH',

    // true on the temporary preview domain: banner + noindex. Set false at launch.
    'preview' => (bool) env('ALLINSON_PREVIEW', true),

    // Who is emailed about new submissions. Each area falls back to "default".
    'notify' => [
        'default' => env('ALLINSON_NOTIFY_EMAIL', ''),
        'quotes' => env('ALLINSON_NOTIFY_QUOTES', ''),
        'warehousing' => env('ALLINSON_NOTIFY_WAREHOUSING', ''),
        'recruitment' => env('ALLINSON_NOTIFY_RECRUITMENT', ''),
        'compliance' => env('ALLINSON_NOTIFY_COMPLIANCE', ''),
        'enquiries' => env('ALLINSON_NOTIFY_ENQUIRIES', ''),
    ],

    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    | The firm's published figures, quoted exactly as its own site states them, with the page.
    */
    'sources' => [
        'home' => ['label' => 'allinsontransport.co.uk, home page', 'url' => 'https://allinsontransport.co.uk/'],
        'transport' => ['label' => 'allinsontransport.co.uk, "Transport"', 'url' => 'https://allinsontransport.co.uk/page.php?id=3'],
        'uk' => ['label' => 'allinsontransport.co.uk, "UK Transport"', 'url' => 'https://allinsontransport.co.uk/page.php?id=11'],
        'warehousing' => ['label' => 'allinsontransport.co.uk, "Warehousing"', 'url' => 'https://allinsontransport.co.uk/page.php?id=4'],
        'containers' => ['label' => 'allinsontransport.co.uk, "Container storage and handling"', 'url' => 'https://allinsontransport.co.uk/page.php?id=12'],
        'technology' => ['label' => 'allinsontransport.co.uk, "Technology"', 'url' => 'https://allinsontransport.co.uk/page.php?id=16'],
        'csr' => ['label' => 'allinsontransport.co.uk, "CSR"', 'url' => 'https://allinsontransport.co.uk/page.php?id=25'],
        'about' => ['label' => 'allinsontransport.co.uk, "About Us"', 'url' => 'https://allinsontransport.co.uk/page.php?id=7'],
        'why' => ['label' => 'allinsontransport.co.uk, "Why George Allinson?"', 'url' => 'https://allinsontransport.co.uk/page.php?id=2'],
        'careers' => ['label' => 'allinsontransport.co.uk, "Join Allinson Transport"', 'url' => 'https://allinsontransport.co.uk/page.php?id=26'],
        'companies_house' => ['label' => 'Companies House, company 00960649', 'url' => 'https://find-and-update.company-information.service.gov.uk/company/00960649'],
    ],

    'figures' => [
        'vehicles' => ['value' => '120', 'label' => 'vehicles in a modern fleet', 'source' => 'home'],
        'trailers' => ['value' => '250', 'label' => 'trailers', 'source' => 'home'],
        'warehousing' => ['value' => '250,000', 'unit' => 'sq ft', 'label' => 'of flexible warehousing', 'source' => 'home'],
        'founded' => ['value' => '1955', 'label' => 'established, and still family-run and owned', 'source' => 'about'],
        'container_yard' => ['value' => '50,000', 'unit' => 'sq ft', 'label' => 'of hard-standing container storage, for up to 500 TEU', 'source' => 'containers'],
        'stacker' => ['value' => '45,000', 'unit' => 'kg', 'label' => 'containers lifted and stacked five high', 'source' => 'containers'],
    ],

    /*
    | Depots. The firm's site names the regions ("facilities in County Durham, Yorkshire,
    | Cambridgeshire, Gloucestershire and Liverpool", home page; "the North-east, Cambridgeshire,
    | Gloucestershire, Merseyside and Yorkshire", UK Transport page). Only the Darlington head
    | office address is published. 'areas' are the postcode areas whose collections a depot
    | prices by default (App\Support\Depots::forPostcode()); traffic can move a request to another depot.
    | This first split is a reasonable guess for the firm to correct.
    */
    'depots' => [
        'darlington' => [
            'name' => 'Darlington',
            'label' => 'Darlington head office',
            'region' => 'County Durham, North East',
            'address' => 'Faverdale North, Faverdale Industrial Estate, Darlington, County Durham DL3 0PH',
            'confirmed' => true,
            'note' => 'Head office, transport, warehousing and the container yard serving Teesport and the Port of Tyne.',
            'areas' => ['DL', 'TS', 'DH', 'NE', 'SR', 'CA', 'TD', 'EH', 'G', 'ML', 'KA', 'PA', 'FK', 'KY', 'DD', 'PH', 'AB', 'IV', 'KW', 'HS', 'ZE', 'DG'],
        ],
        'yorkshire' => [
            'name' => 'Yorkshire',
            'label' => 'Yorkshire depot',
            'region' => 'Yorkshire',
            'address' => null,
            'confirmed' => false,
            'note' => 'Named on the firm\'s site; address to be confirmed.',
            'areas' => ['YO', 'HG', 'LS', 'BD', 'HX', 'HD', 'WF', 'HU', 'DN', 'S', 'LN'],
        ],
        'liverpool' => [
            'name' => 'Liverpool',
            'label' => 'Liverpool depot',
            'region' => 'Merseyside',
            'address' => null,
            'confirmed' => false,
            'note' => 'Named on the firm\'s site; address to be confirmed.',
            'areas' => ['L', 'CH', 'WA', 'WN', 'PR', 'BB', 'BL', 'OL', 'M', 'SK', 'CW', 'ST', 'LL', 'FY', 'LA'],
        ],
        'cambridgeshire' => [
            'name' => 'Cambridgeshire',
            'label' => 'Cambridgeshire depot',
            'region' => 'Cambridgeshire, East of England',
            'address' => null,
            'confirmed' => false,
            'note' => 'Named on the firm\'s site; address to be confirmed.',
            'areas' => ['CB', 'PE', 'IP', 'NR', 'CO', 'CM', 'SG', 'LU', 'MK', 'NN', 'LE', 'NG', 'DE', 'AL', 'EN', 'IG', 'RM', 'SS', 'E', 'EC', 'N', 'NW', 'SE', 'SW', 'W', 'WC', 'HA', 'HP', 'WD', 'UB', 'TW', 'KT', 'CR', 'BR', 'DA', 'ME', 'CT', 'TN', 'RH', 'BN'],
        ],
        'gloucestershire' => [
            'name' => 'Gloucestershire',
            'label' => 'Gloucestershire depot',
            'region' => 'Gloucestershire, South West',
            'address' => null,
            'confirmed' => false,
            'note' => 'Named on the firm\'s site; address to be confirmed.',
            'areas' => ['GL', 'HR', 'WR', 'BS', 'BA', 'SN', 'OX', 'RG', 'SL', 'GU', 'SO', 'PO', 'SP', 'BH', 'DT', 'TA', 'EX', 'TQ', 'PL', 'TR', 'NP', 'CF', 'SA', 'LD', 'SY', 'TF', 'B', 'CV', 'DY', 'WV', 'WS'],
        ],
    ],

    /*
    | Services, in the firm's own words where it has them (each 'source' is the page).
    */
    'services' => [
        'regional' => [
            'tab' => 'Transport',
            'title' => 'Regional transport and consolidation',
            'icon' => 'truck',
            'photo' => 'own-tipper',
            'source' => 'home',
            'summary' => 'For North East businesses: full-load efficiency across the UK, even for a single pallet.',
            'body' => 'Allinson consolidates loads from businesses around the North East, so a customer with one pallet gets the cost and reliability of full-load transport throughout the UK. Fewer part-empty lorries also means a smaller carbon footprint.',
            'points' => ['From a single pallet to a full load', 'Consolidated loads leaving the North East daily', 'Personal service from a family firm'],
        ],
        'national' => [
            'tab' => 'Distribution',
            'title' => 'UK trunking and distribution',
            'icon' => 'route',
            'photo' => 'own-reefer',
            'source' => 'uk',
            'summary' => 'Trunking and distribution anywhere in the UK, from depots in five regions.',
            'body' => 'A modern fleet of 120 vehicles and 250 trailers runs trunking and distribution throughout the UK from facilities in the North East, Yorkshire, Merseyside, Cambridgeshire and Gloucestershire. Allinson can also place resources at a customer\'s premises, or turn one of its warehouses into a regional distribution centre.',
            'points' => ['Full and part loads, nationwide', 'Dedicated resources at your premises on request', 'Regional distribution centres in our warehousing'],
        ],
        'specialist' => [
            'tab' => 'Hiab',
            'title' => 'Hiab, crane-lifted and abnormal loads',
            'icon' => 'crane',
            'photo' => 'own-hiab',
            'source' => 'home',
            'summary' => 'From a single pallet to 15-metre crane-lifted extrusions and out-of-gauge freight.',
            'body' => 'Allinson\'s fleet handles everything from refrigerated goods to 15-metre crane-lifted extrusions. Unusual or out-of-gauge loads, and jobs that have defeated other carriers, are the kind of problem the team likes to be asked about.',
            'points' => ['Lorry-mounted crane (Hiab) deliveries', 'Long and out-of-gauge loads', 'Loads other carriers turned down'],
        ],
        'containers' => [
            'tab' => 'Containers',
            'title' => 'Container storage and handling',
            'icon' => 'container',
            'photo' => 'own-container',
            'source' => 'containers',
            'summary' => 'Off the quay at Teesport or the Port of Tyne and into our yard for destuffing, storage or re-loading.',
            'body' => 'Close to two major ports, Teesport and the Port of Tyne, Allinson brings containers off the quay into its nearby depot for destuffing. The 50,000 sq ft hard-standing yard stores up to 500 TEU, and a Fantuzzi lift and stacker handles containers up to 45,000 kg, five high. Cargo can be stored, cross-docked, picked or re-loaded for export.',
            'points' => ['Import destuffing and export loading', 'Up to 500 TEU of container storage', 'Inventory, cross-docking and order fulfilment for the cargo'],
        ],
        'warehousing' => [
            'tab' => 'Warehousing',
            'title' => 'Warehousing and order fulfilment',
            'icon' => 'warehouse',
            'photo' => 'own-warehouse',
            'source' => 'warehousing',
            'summary' => '250,000 sq ft of flexible space with hi-cube racking and stock you can see online.',
            'body' => 'The warehousing is laid out to suit each customer\'s product, with hi-cube racking and a warehouse management system that tracks stock at pallet, carton or item level, with batch numbers and rotation dates, visible online in real time. Orders arriving online, by email or phone are picked, packed, labelled and delivered with proof of delivery, including for online retailers who never handle their own product.',
            'points' => ['Pallet, carton and item-level stock, online', 'Pick, pack, label and deliver', 'E-retail fulfilment to the final mile'],
        ],
        'consultancy' => [
            'tab' => 'Logistics',
            'title' => 'Logistics consultancy and technology',
            'icon' => 'chart',
            'photo' => 'own-loading',
            'source' => 'why',
            'summary' => 'Show us six months of orders and we will benchmark your logistics costs, free.',
            'body' => 'Allinson\'s managers offer consultancy to new and existing customers, including a free benchmark: share an order profile for six months, in confidence, and the team compares it with what is possible. Behind the service is Mandata transport management, GPS tracking with geo-fenced delivery alerts, EDI order placement and proof of delivery online.',
            'points' => ['Free six-month logistics benchmark', 'GPS tracking and geo-fenced ETA alerts', 'EDI orders and online proof of delivery'],
        ],
    ],

    // "We have particular expertise in", from the "Why George Allinson?" page.
    'sectors' => ['Consumer and retail products', 'Polymers and plastics', 'Flooring', 'Manufacturing', 'Large industrial components', 'Automotive', 'Food and food packaging'],

    'about' => [
        'intro' => 'George Allinson (Transport) Ltd was established in 1955 and is a family run and owned business. The company combines traditional values of customer service, integrity and excellence with a modern, innovative and progressive approach to logistics.',
        'values' => [
            ['Lean and green', 'Efficient supply-chain management saves customers money, and the same savings cut fuel and emissions.'],
            ['Efficient', 'The job is to help customers succeed by getting their goods to their own customers reliably and professionally.'],
            ['Safe', 'Safety is part of the culture and the operation. Allinson founded a national safety forum with one of its customers.'],
            ['Well trained', 'Training goes beyond compliance: the firm\'s Driver CPC programme started in 2010.'],
        ],
        'green' => [
            'The fleet\'s top speed is limited to 52 mph, which the firm says makes no appreciable difference to journey times but saves fuel.',
            'Telematics flag wasteful driving such as idling, over-revving, and harsh braking or acceleration.',
            'Routeing and scheduling keep each lorry optimally loaded and empty running to a minimum.',
        ],
    ],

    'careers' => [
        'intro' => 'Allinson always welcomes applications from people who want to work in logistics. The firm is seeking drivers with C+E vocational licences, particularly in the North East, Gloucestershire, Yorkshire and Merseyside, and welcomes applicants with Hiab or equivalent crane qualifications.',
        'licence_categories' => ['B' => 'B (car)', 'C1' => 'C1 (7.5 t)', 'C1E' => 'C1+E', 'C' => 'C (rigid)', 'CE' => 'C+E (artic)'],
        'forklift' => ['counterbalance' => 'Counterbalance', 'reach' => 'Reach truck', 'vna' => 'Very narrow aisle (VNA)', 'pallet_truck' => 'Powered pallet truck', 'telehandler' => 'Telehandler'],
        // Months an application is kept after the vacancy closes (privacy notice).
        'retention_months' => 6,
    ],

    /*
    | Photos in public/img as {key}-{width}.webp. 'widths' lists the files made: the firm's own
    | photos are small (600 px) on its site, so they are never upscaled.
    */
    'photos' => [
        'a40' => ['w' => 1600, 'h' => 1200, 'widths' => [480, 960, 1600]],
        'a1m' => ['w' => 1920, 'h' => 1080, 'widths' => [480, 960, 1920]],
        'warehouse' => ['w' => 1920, 'h' => 1496, 'widths' => [480, 960, 1920]],
        'tacho' => ['w' => 1920, 'h' => 680, 'widths' => [480, 960, 1920]],
        'own-depot-night' => ['w' => 1400, 'h' => 680, 'widths' => [480, 960, 1400]],
        'own-lineup' => ['w' => 700, 'h' => 461, 'widths' => [480, 700]],
        'own-loading' => ['w' => 600, 'h' => 400, 'widths' => [480, 600]],
        'own-loading-2' => ['w' => 600, 'h' => 400, 'widths' => [480, 600]],
        'own-hiab' => ['w' => 600, 'h' => 400, 'widths' => [480, 600]],
        'own-reefer' => ['w' => 600, 'h' => 400, 'widths' => [480, 600]],
        'own-tipper' => ['w' => 600, 'h' => 400, 'widths' => [480, 600]],
        'own-container' => ['w' => 600, 'h' => 400, 'widths' => [480, 600]],
        'own-warehouse' => ['w' => 600, 'h' => 400, 'widths' => [480, 600]],
        'own-forklift' => ['w' => 600, 'h' => 398, 'widths' => [480, 600]],
        'own-coils' => ['w' => 600, 'h' => 400, 'widths' => [480, 600]],
        'own-warehouse-hall' => ['w' => 600, 'h' => 180, 'widths' => [480, 600]],
    ],
    // Photos that show the firm itself (its own, or the Commons photo of its lorry). Every
    // other photo is labelled "Illustrative photo".
    'own_photos' => ['a40', 'own-depot-night', 'own-lineup', 'own-loading', 'own-loading-2', 'own-hiab', 'own-reefer', 'own-tipper', 'own-container', 'own-warehouse', 'own-forklift', 'own-coils', 'own-warehouse-hall'],
    'og_photo' => 'a40',

    // The old site's page.php?id=N addresses, sent to the new pages by LegacyController.
    'legacy_pages' => [
        1 => 'home', 2 => 'about', 3 => 'services', 4 => 'fleet', 5 => 'services', 6 => 'home', 7 => 'about',
        10 => 'services', 11 => 'services', 12 => 'services', 13 => 'services', 14 => 'services', 15 => 'services',
        16 => 'services', 17 => 'about', 19 => 'sitemap.page', 20 => 'privacy', 21 => 'home', 22 => 'contact',
        23 => 'services', 24 => 'about', 25 => 'about', 26 => 'careers.index',
    ],
];
