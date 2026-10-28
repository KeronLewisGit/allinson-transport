<?php

namespace Database\Seeders;

use App\Enums\ApplicantDocumentKind;
use App\Enums\ApplicationStatus;
use App\Enums\AssessmentResult;
use App\Enums\EmploymentType;
use App\Enums\EnquiryStatus;
use App\Enums\EnquiryTopic;
use App\Enums\GoodsType;
use App\Enums\JobRole;
use App\Enums\JobStatus;
use App\Enums\QuoteService;
use App\Enums\QuoteStatus;
use App\Enums\ReferenceStatus;
use App\Enums\Role;
use App\Enums\StorageDuration;
use App\Enums\WarehousingStatus;
use App\Models\Enquiry;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Quote;
use App\Models\StaffMember;
use App\Models\User;
use App\Models\WarehousingEnquiry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo data for the pitch. Every company, person, load and vacancy here is fictional; companies
 * and vacancies are labelled "(sample)". Logins: admin|traffic|warehouse|hr@allinson-transport.test
 * (and traffic.yorkshire@) / password. Never run in production.
 */
class DemoSeeder extends Seeder
{
    public const string DOMAIN = 'allinson-transport.test';

    private const array FIRST = ['Gary', 'Leanne', 'Craig', 'Joanne', 'Darren', 'Kirsty', 'Lee', 'Gemma', 'Stuart', 'Nicola', 'Mark', 'Hayley', 'Paul', 'Rachel', 'Shaun', 'Claire', 'Wayne', 'Donna', 'Steven', 'Lisa', 'Ian', 'Karen'];

    private const array LAST = ['Robson', 'Hutchinson', 'Atkinson', 'Bell', 'Thompson', 'Dodds', 'Wilkinson', 'Charlton', 'Hall', 'Pearson', 'Heslop', 'Walton', 'Lowes', 'Peacock', 'Mason', 'Shaw', 'Nicholson', 'Elliott', 'Raine', 'Swales'];

    public function run(): void
    {
        // Fictional data and "password" logins: never on the live site. A pitch preview runs
        // APP_ENV=production with ALLINSON_PREVIEW=true, so it is allowed there.
        if (app()->isProduction() && ! ga('preview')) {
            $this->command?->error('DemoSeeder does not run in production unless ALLINSON_PREVIEW=true (a pitch preview).');

            return;
        }
        mt_srand(1955);
        $staff = $this->users();
        $this->quotes($staff);
        $this->warehousing($staff['warehouse']);
        $applications = $this->careers($staff['hr']);
        $this->staff($staff['hr'], $applications);
        $this->enquiries();
    }

    /** @return array<string, User> */
    private function users(): array
    {
        $out = [];
        foreach ([
            'admin' => ['Demo Administrator', Role::Admin, null],
            'traffic' => ['Demo Traffic Planner', Role::Traffic, 'darlington'],
            'warehouse' => ['Demo Warehouse Manager', Role::Warehouse, 'darlington'],
            'hr' => ['Demo HR Officer', Role::Hr, null],
            'traffic.yorkshire' => ['Demo Traffic Planner (Yorkshire)', Role::Traffic, 'yorkshire'],
        ] as $key => [$name, $role, $depot]) {
            $out[$key] = User::updateOrCreate(['email' => $key.'@'.self::DOMAIN], ['name' => $name, 'password' => Hash::make('password'), 'role' => $role, 'depot' => $depot, 'active' => true]);
        }

        return $out;
    }

    private function name(): string
    {
        return self::FIRST[mt_rand(0, count(self::FIRST) - 1)].' '.self::LAST[mt_rand(0, count(self::LAST) - 1)];
    }

    private function mobile(): string
    {
        return '07700 900'.str_pad((string) mt_rand(0, 999), 3, '0', STR_PAD_LEFT);
    }

    /** A tiny valid PDF on the private disk, so "open" works in the demo. */
    private function pdf(string $path, string $title): int
    {
        $text = str_replace(['(', ')', '\\'], '', 'SAMPLE DOCUMENT - '.$title);
        $stream = "BT /F1 18 Tf 72 720 Td ({$text}) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$obj}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }
        $pdf .= 'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
        Storage::disk('local')->put($path, $pdf);

        return strlen($pdf);
    }

    private function at(Carbon $time, callable $create): mixed
    {
        Carbon::setTestNow($time);
        try {
            return $create();
        } finally {
            Carbon::setTestNow();
        }
    }

    /** @param array<string, User> $staff */
    private function quotes(array $staff): void
    {
        // [service, company, from, from town, to, to town, goods, pallets, kg, dims, adr, status, price, days ago, collect in days]
        $rows = [
            [QuoteService::Pallets, 'Tees Valley Polymers (sample)', 'TS18 3TS', 'Stockton-on-Tees', 'B6 7EU', 'Birmingham', 'Bagged polymer granules', 8, 6400, null, false, QuoteStatus::New, null, 0, 1],
            [QuoteService::FullLoad, 'Wearside Flooring Supplies (sample)', 'SR5 3NY', 'Sunderland', 'GL2 5BX', 'Gloucester', 'Vinyl flooring rolls on long pallets', 24, 18500, null, false, QuoteStatus::New, null, 0, 2],
            [QuoteService::Hiab, 'Aycliffe Steel Fabrications (sample)', 'DL5 6XF', 'Newton Aycliffe', 'YO26 6RU', 'York', 'Steel portal frame sections, crane offload', null, 9200, [12.0, 2.4, 1.2], false, QuoteStatus::Pricing, null, 1, 3],
            [QuoteService::PartLoad, 'Durham Garden Retail (sample)', 'DH1 1TW', 'Durham', 'CB4 0WS', 'Cambridge', 'Boxed garden furniture', 10, 3100, null, false, QuoteStatus::New, null, 1, 5],
            [QuoteService::Abnormal, 'Hartlepool Process Engineering (sample)', 'TS25 1PU', 'Hartlepool', 'L20 8DJ', 'Bootle', 'Pressure vessel, 4.1 m wide', null, 28000, [9.5, 4.1, 3.6], false, QuoteStatus::Pricing, null, 2, 9],
            [QuoteService::Pallets, 'Leeds Paint Wholesale (sample)', 'LS10 1RJ', 'Leeds', 'NE29 7UJ', 'North Shields', 'Water-based paints and some solvent coatings', 4, 2200, null, true, QuoteStatus::New, null, 0, 2],
            [QuoteService::FullLoad, 'Humber Food Packaging (sample)', 'HU7 0XD', 'Hull', 'DL1 4PZ', 'Darlington', 'Food-grade trays, palletised', 26, 7800, null, false, QuoteStatus::New, null, 3, 4],
            [QuoteService::PartLoad, 'Cheltenham Office Interiors (sample)', 'GL51 9NJ', 'Cheltenham', 'TS17 6BX', 'Thornaby', 'Flat-packed office desks', 12, 4300, null, false, QuoteStatus::Quoted, 780, 4, 6],
            [QuoteService::FullLoad, 'Teesport Import Services (sample)', 'TS6 6UD', 'Teesport', 'M17 1RW', 'Manchester', 'Destuffed container of retail goods', 22, 14500, null, false, QuoteStatus::Quoted, 1150, 5, 3],
            [QuoteService::Pallets, 'Peterborough Print Finishers (sample)', 'PE2 6TG', 'Peterborough', 'DH6 5PG', 'Durham', 'Printed cartons', 3, 1300, null, false, QuoteStatus::Accepted, 295, 9, -2],
            [QuoteService::Hiab, 'Wirral Precast (sample)', 'CH41 1LE', 'Birkenhead', 'DL3 0PH', 'Darlington', 'Precast concrete kerbs, crane offload', 20, 21000, [3.0, 1.0, 1.0], false, QuoteStatus::Accepted, 860, 12, -5],
            [QuoteService::FullLoad, 'Gateshead Automotive Parts (sample)', 'NE11 0BT', 'Gateshead', 'CV6 4BX', 'Coventry', 'Automotive components in stillages', 22, 11200, null, false, QuoteStatus::NotTaken, 940, 15, -8],
            [QuoteService::Warehousing, 'Yarm Online Homeware (sample)', 'TS15 9BW', 'Yarm', 'DL3 0PH', 'Darlington', 'Homeware stock to store and despatch', 40, 12000, null, false, QuoteStatus::New, null, 2, 7],
            [QuoteService::Pallets, 'Doncaster Building Merchants (sample)', 'DN2 4PG', 'Doncaster', 'NE63 0YA', 'Ashington', 'Bagged plaster and adhesives', 6, 5900, null, false, QuoteStatus::Declined, null, 20, -12],
            [QuoteService::PartLoad, 'Carlisle Agricultural Supplies (sample)', 'CA1 3NQ', 'Carlisle', 'HG3 1QY', 'Harrogate', 'Fencing materials', 8, 4800, null, false, QuoteStatus::Quoted, 520, 6, 8],
            [QuoteService::FullLoad, 'Selby Glass Containers (sample)', 'YO8 8PH', 'Selby', 'TS2 1RH', 'Middlesbrough', 'Glass bottles on pallets', 26, 16800, null, false, QuoteStatus::Accepted, 690, 25, -18],
        ];
        foreach ($rows as $i => [$service, $company, $from, $fromTown, $to, $toTown, $goods, $pallets, $kg, $dims, $adr, $status, $price, $daysAgo, $collectIn]) {
            $created = now()->subDays($daysAgo)->subHours(mt_rand(1, 6))->subMinutes(mt_rand(0, 59));
            $quote = $this->at($created, fn () => Quote::create([
                'service' => $service, 'company' => $company, 'contact_name' => $this->name(),
                'email' => 'transport'.($i + 1).'@example.com', 'phone' => '01'.mt_rand(10, 99).mt_rand(1, 9).' '.mt_rand(100000, 999999),
                'collection_postcode' => $from, 'collection_town' => $fromTown, 'collection_date' => today()->addDays($collectIn),
                'delivery_postcode' => $to, 'delivery_town' => $toTown, 'delivery_date' => $collectIn % 3 === 0 ? today()->addDays($collectIn + 1) : null,
                'goods' => $goods, 'pallets' => $pallets, 'weight_kg' => $kg,
                'length_m' => $dims[0] ?? null, 'width_m' => $dims[1] ?? null, 'height_m' => $dims[2] ?? null,
                'adr' => $adr, 'adr_details' => $adr ? 'UN1263, class 3 (sample)' : null, 'tail_lift' => $i % 5 === 3, 'regular' => $i % 4 === 1,
                'message' => $i % 3 === 0 ? 'Booking-in required at delivery. (sample)' : null,
            ]));
            $quote->log('Received from the website. Routed to '.$quote->depotName().' by collection postcode.');
            $owner = $quote->depot === 'yorkshire' ? $staff['traffic.yorkshire'] : $staff['traffic'];
            if ($status === QuoteStatus::New) {
                continue;
            }
            $quote->forceFill(['assigned_to' => $owner->id])->save();
            if ($status === QuoteStatus::Pricing) {
                $quote->claimStatus(QuoteStatus::Pricing);
                $quote->log('Checking vehicle availability. (sample)', 'note', $owner);

                continue;
            }
            if ($status === QuoteStatus::Declined) {
                $quote->claimStatus(QuoteStatus::Declined);
                $quote->log('Declined: no capacity on the dates asked. (sample)', 'status', $owner);

                continue;
            }
            $quote->claimStatus(QuoteStatus::Quoted, ['quoted_at' => $created->copy()->addHours(3)]);
            $quote->forceFill(['price' => $price, 'valid_until' => today()->addDays(max(7, 14 - $daysAgo)), 'price_notes' => 'Curtainsider, tail lift not included. (sample)'])->save();
            $quote->log('Quote of '.gbp($price).' + VAT emailed to the customer.', 'status', $owner);
            if (in_array($status, [QuoteStatus::Accepted, QuoteStatus::NotTaken], true)) {
                $quote->claimStatus($status, ['responded_at' => $created->copy()->addDay()]);
                $quote->log($status === QuoteStatus::Accepted ? 'Customer accepted the quote online.' : 'Customer declined the quote online.', 'status');
            }
        }
    }

    private function warehousing(User $manager): void
    {
        $rows = [
            ['Sample Online Retail Ltd (sample)', 180, StorageDuration::Ongoing, GoodsType::Retail, 'Boxed homeware, mixed SKUs, about 900 orders a week', ['pick_pack', 'ecommerce', 'distribution'], WarehousingStatus::New, 0],
            ['North East Polymers (sample)', 450, StorageDuration::SixToTwelve, GoodsType::Polymers, '25 kg bags on standard pallets, double stack', ['storage', 'distribution'], WarehousingStatus::New, 2],
            ['Teesside Flooring Distributors (sample)', 90, StorageDuration::ThreeToSix, GoodsType::Flooring, 'Carpet and vinyl rolls up to 4 m long', ['storage'], WarehousingStatus::Contacted, 4],
            ['Import Furniture Co (sample)', 60, StorageDuration::OneToThree, GoodsType::Containers, 'Two 40 ft containers a month of flat-pack furniture', ['destuffing', 'cross_dock', 'distribution'], WarehousingStatus::Proposal, 8],
            ['Durham Food Ingredients (sample)', 220, StorageDuration::Ongoing, GoodsType::Food, 'Ambient dry ingredients, batch and best-before tracking', ['storage', 'pick_pack'], WarehousingStatus::Won, 21],
            ['Automotive Spares North (sample)', 35, StorageDuration::UnderMonth, GoodsType::Automotive, 'Overflow stock during a stock-take', ['storage'], WarehousingStatus::Lost, 30],
        ];
        foreach ($rows as $i => [$company, $spaces, $duration, $goods, $desc, $handling, $status, $daysAgo]) {
            $e = $this->at(now()->subDays($daysAgo)->subHours(mt_rand(2, 30)), fn () => WarehousingEnquiry::create([
                'company' => $company, 'contact_name' => $this->name(), 'email' => 'logistics'.($i + 1).'@example.com', 'phone' => '0191 '.mt_rand(200, 499).' '.mt_rand(1000, 9999),
                'pallet_spaces' => $spaces, 'duration' => $duration, 'start_on' => today()->addDays(mt_rand(7, 40)), 'goods_type' => $goods,
                'goods_description' => $desc.'. (sample)', 'handling' => $handling, 'hazardous' => false, 'depot' => $i % 3 === 0 ? 'darlington' : null,
            ]));
            $e->log('Received from the website.');
            if ($status !== WarehousingStatus::New) {
                $e->forceFill(['assigned_to' => $manager->id])->save();
                $e->claimStatus($status);
                $e->log('Moved to '.$status->label().'. (sample)', 'status', $manager);
            }
        }
    }

    /** @return list<JobApplication> */
    private function careers(User $hr): array
    {
        $jobs = [
            ['C+E driver, nights (sample)', JobRole::CeDriver, 'darlington', EmploymentType::FullTime, 'Hourly rate on application (sample)', 'Nights, Sunday to Thursday', 3, 'Night trunking between the depots and customers across the UK.', "Night trunking runs to the regional depots\nDaily walkaround checks and defect reporting\nLoad security and paperwork", "C+E licence with no more than 6 points\nDriver CPC and digital tacho card in date\nCurtainsider experience"],
            ['C+E driver, days (sample)', JobRole::CeDriver, 'gloucestershire', EmploymentType::FullTime, 'Hourly rate on application (sample)', 'Days, Monday to Friday', 2, 'Day distribution from the Gloucestershire depot.', "Multi-drop and single-drop deliveries\nCustomer sign-offs and PODs on the handheld", "C+E licence\nDriver CPC and digital tacho card in date"],
            ['Hiab driver (sample)', JobRole::HiabDriver, 'darlington', EmploymentType::FullTime, 'Hourly rate on application (sample)', 'Days', 1, 'Crane-offload deliveries of steel, precast and long loads.', "Plan and carry out crane lifts safely\nSecure long and heavy loads", "C or C+E licence\nLorry loader qualification\nDriver CPC and digital tacho card"],
            ['Warehouse operative (sample)', JobRole::Warehouse, 'darlington', EmploymentType::FullTime, null, 'Shifts', 2, 'Goods in, put-away, picking and loading in the Faverdale warehouse.', "Unload and check goods in\nPick and pack orders\nLoad trailers to plan", "Counterbalance or reach truck licence preferred\nCareful with stock and paperwork"],
            ['Transport planner (sample)', JobRole::TransportOffice, 'yorkshire', EmploymentType::FullTime, null, 'Days, Monday to Friday', 1, 'Plan loads and drivers and look after customers from the Yorkshire depot.', "Plan daily work and trunking\nPrice work for the traffic office\nKeep drivers' hours compliant", "Transport office experience\nGood with Mandata or a similar TMS\nCPC (transport manager) an advantage"],
        ];
        $posts = [];
        foreach ($jobs as [$title, $role, $depot, $type, $pay, $hours, $openings, $summary, $duties, $req]) {
            $posts[] = JobPosting::create(['slug' => Str::slug(str_replace(' (sample)', '', $title)), 'title' => $title, 'role' => $role, 'depot' => $depot, 'employment_type' => $type, 'status' => JobStatus::Open,
                'pay' => $pay, 'hours' => $hours, 'openings' => $openings, 'summary' => $summary, 'duties' => $duties, 'requirements' => $req, 'closes_on' => today()->addDays(mt_rand(14, 45)), 'is_demo' => true]);
        }
        JobPosting::create(['slug' => 'class-1-driver-liverpool-closed', 'title' => 'C+E driver, Liverpool (sample, closed)', 'role' => JobRole::CeDriver, 'depot' => 'liverpool', 'employment_type' => EmploymentType::FullTime,
            'status' => JobStatus::Closed, 'openings' => 1, 'summary' => 'Closed sample vacancy.', 'is_demo' => true]);

        // Where each sample applicant has got to.
        $stages = [
            ApplicationStatus::New, ApplicationStatus::New, ApplicationStatus::New, ApplicationStatus::New, ApplicationStatus::Screening, ApplicationStatus::Screening,
            ApplicationStatus::Checks, ApplicationStatus::Checks, ApplicationStatus::Assessment, ApplicationStatus::Assessment, ApplicationStatus::Offer,
            ApplicationStatus::Declined, ApplicationStatus::Withdrawn, ApplicationStatus::New, ApplicationStatus::Screening, ApplicationStatus::Checks, ApplicationStatus::Offer, ApplicationStatus::Assessment,
        ];
        $out = [];
        foreach ($stages as $i => $stage) {
            $job = $i % 6 === 5 ? null : $posts[$i % count($posts)];
            $role = $job->role ?? JobRole::CeDriver;
            $drives = $role->drives();
            [$first, $last] = explode(' ', $this->name());
            $points = $drives ? [0, 0, 3, 0, 6, 0][$i % 6] : null;
            $a = $this->at(now()->subDays(mt_rand(0, 40))->subHours(mt_rand(0, 10)), fn () => JobApplication::create([
                'job_posting_id' => $job?->id, 'role' => $role, 'depot' => $job->depot ?? 'darlington', 'status' => ApplicationStatus::New,
                'first_name' => $first, 'last_name' => $last, 'phone' => $this->mobile(), 'email' => strtolower($first.'.'.$last.$i).'@example.com',
                'postcode' => ['DL1 3AB', 'TS5 6QT', 'DH7 8RT', 'GL4 3JR', 'LS27 0BN', 'NE8 1AX'][$i % 6], 'experience' => ['2', '5', '10', '1', 'none'][$i % 5],
                'licence_categories' => $drives ? ['B', 'C', 'CE'] : ['B'],
                'cpc_expires_on' => $drives ? today()->addDays([700, 40, 1200, 25, 400, -10][$i % 6]) : null,
                'tacho_card_expires_on' => $drives ? today()->addDays([900, 300, 50, 1500, 20, 600][$i % 6]) : null,
                'licence_points' => $points, 'endorsements' => $points ? 'SP30 (sample)' : null,
                'hiab_qualification' => $role === JobRole::HiabDriver ? 'Lorry loader card, valid to 2028 (sample)' : null,
                'forklift' => $role === JobRole::Warehouse ? ['counterbalance', 'reach'] : ($i % 4 === 0 ? ['counterbalance'] : null),
                'right_to_work' => true, 'available_from' => today()->addDays(mt_rand(7, 35)),
                'message' => 'Sample applicant: previous work on general haulage and distribution.', 'consented_at' => now(),
            ]));
            if ($role->needsCv() || $i % 3 === 0) {
                $size = $this->pdf('cvs/demo-'.$a->id.'.pdf', 'CV - '.$a->full_name);
                $a->forceFill(['cv_path' => 'cvs/demo-'.$a->id.'.pdf', 'cv_name' => 'CV.pdf', 'cv_mime' => 'application/pdf', 'cv_size' => $size])->save();
            }
            $a->log('Received from the website.');
            $this->progress($a, $stage, $hr, $i);
            $out[] = $a;
        }

        return $out;
    }

    private function progress(JobApplication $a, ApplicationStatus $stage, User $hr, int $i): void
    {
        if ($stage === ApplicationStatus::New) {
            return;
        }
        $order = [ApplicationStatus::Screening, ApplicationStatus::Checks, ApplicationStatus::Assessment, ApplicationStatus::Offer];
        $reach = in_array($stage, $order, true) ? array_search($stage, $order, true) : 0;
        $a->claimStatus(ApplicationStatus::Screening);
        $a->log('Phone screen done. (sample)', 'status', $hr);
        if ($reach >= 1) {
            $a->claimStatus(ApplicationStatus::Checks);
            $a->forceFill(['documents_requested_at' => now()->subDays(3), 'references_status' => ReferenceStatus::Requested, 'references_updated_at' => now()->subDays(3)])->save();
            $a->log('Emailed a link to upload licence, card and right-to-work copies.', 'status', $hr);
            foreach (ApplicantDocumentKind::forDriving($a->drives()) as $kind) {
                $path = 'applicants/'.$a->id.'/demo-'.$kind->value.'.pdf';
                $size = $this->pdf($path, $kind->label().' - '.$a->full_name);
                $a->documents()->create(['kind' => $kind, 'path' => $path, 'original_name' => $kind->value.'.pdf', 'mime' => 'application/pdf', 'size' => $size]);
            }
            $a->log('Applicant uploaded their documents.', 'file');
        }
        if ($reach >= 2) {
            $a->forceFill(['rtw_checked_at' => now()->subDays(2), 'rtw_checked_by' => $hr->id, 'rtw_method' => 'share_code', 'references_status' => ReferenceStatus::Received, 'references_updated_at' => now()->subDay()])->save();
            $a->claimStatus(ApplicationStatus::Assessment);
            $a->forceFill(['assessment_at' => today()->addDays(1 + $i % 6)->setTime(9 + $i % 4, 0)])->save();
            $a->log('Right to work checked; references received; assessment drive booked.', 'status', $hr);
        }
        if ($reach >= 3) {
            $a->forceFill(['assessment_at' => today()->subDays(2)->setTime(10, 0), 'assessment_result' => AssessmentResult::Passed])->save();
            $a->claimStatus(ApplicationStatus::Offer);
            $a->log('Passed the assessment drive. Offer made. (sample)', 'status', $hr);
        }
        if (in_array($stage, [ApplicationStatus::Declined, ApplicationStatus::Withdrawn], true)) {
            $a->claimStatus($stage);
            $a->log($stage === ApplicationStatus::Declined ? 'Not taken forward: licence categories do not fit the role. (sample)' : 'Applicant accepted a job elsewhere. (sample)', 'status', $hr);
        }
    }

    /** @param list<JobApplication> $applications */
    private function staff(User $hr, array $applications): void
    {
        // Days until CPC / tacho / licence expiry: a mix of expired, due soon and comfortable.
        $offsets = [[-6, 400, 1500], [12, 800, 1200], [33, 45, 900], [58, 1300, 2000], [90, 14, 700], [200, 600, -3], [420, 900, 1600], [700, 1100, 2400],
            [1000, 1500, 3000], [150, 75, 1800], [1500, 1600, 500], [30, 1000, 1200], [600, 200, 59], [1200, 400, 2200]];
        $depots = ['darlington', 'darlington', 'darlington', 'yorkshire', 'liverpool', 'gloucestershire', 'cambridgeshire'];
        foreach ($offsets as $i => [$cpc, $tacho, $licence]) {
            $s = StaffMember::create([
                'name' => $this->name(), 'role' => $i % 7 === 6 ? JobRole::HiabDriver : JobRole::CeDriver, 'depot' => $depots[$i % count($depots)],
                'employee_number' => 'S'.str_pad((string) (1040 + $i * 7), 5, '0', STR_PAD_LEFT), 'phone' => $this->mobile(),
                'licence_categories' => ['B', 'C', 'CE'], 'cpc_expires_on' => today()->addDays($cpc), 'tacho_card_expires_on' => today()->addDays($tacho),
                'licence_expires_on' => today()->addDays($licence), 'cards_checked_on' => today()->subDays(mt_rand(10, 160)), 'active' => true,
            ]);
            $s->log('Added to staff compliance. (sample)', 'system', $hr);
        }
        foreach ([['Warehouse', JobRole::Warehouse], ['Planner', JobRole::TransportOffice]] as $j => [, $role]) {
            StaffMember::create(['name' => $this->name(), 'role' => $role, 'depot' => 'darlington', 'employee_number' => 'S0'.(2100 + $j), 'active' => true]);
        }
        StaffMember::create(['name' => $this->name(), 'role' => JobRole::CeDriver, 'depot' => 'darlington', 'cpc_expires_on' => today()->subDays(40), 'tacho_card_expires_on' => today()->subDays(10), 'active' => false]);

        // One applicant already hired, so the link from application to staff record shows.
        $hire = collect($applications)->first(fn (JobApplication $a) => $a->status === ApplicationStatus::Offer && $a->drives());
        if ($hire && $hire->claimStatus(ApplicationStatus::Hired)) {
            $s = StaffMember::create(['name' => $hire->full_name, 'role' => $hire->role, 'depot' => $hire->depot, 'phone' => $hire->phone, 'email' => $hire->email,
                'licence_categories' => $hire->licence_categories, 'cpc_expires_on' => $hire->cpc_expires_on, 'tacho_card_expires_on' => $hire->tacho_card_expires_on,
                'job_application_id' => $hire->id, 'active' => true]);
            $hire->log('Hired. Compliance tracking started.', 'status', $hr);
            $s->log('Added from application '.$hire->reference.'. Card dates are as the applicant declared them; check them against the cards.', 'system', $hr);
        }
    }

    private function enquiries(): void
    {
        $rows = [
            [EnquiryTopic::Benchmark, 'Sample Manufacturing Ltd (sample)', 'We send about 40 pallets a week from Aycliffe to the Midlands and London. Could you benchmark our current costs?', EnquiryStatus::New],
            [EnquiryTopic::Existing, 'Sample Retail Group (sample)', 'Please send the POD for last Tuesday\'s delivery to our Leeds store.', EnquiryStatus::New],
            [EnquiryTopic::General, null, 'Do you collect from Northern Ireland via Cairnryan?', EnquiryStatus::Answered],
            [EnquiryTopic::Other, 'Sample School PTA (sample)', 'Could one of your lorries visit our summer fair?', EnquiryStatus::Closed],
        ];
        foreach ($rows as $i => [$topic, $org, $message, $status]) {
            $e = $this->at(now()->subDays($i * 3 + 1), fn () => Enquiry::create(['topic' => $topic, 'name' => $this->name(), 'organisation' => $org, 'email' => 'enquiry'.($i + 1).'@example.com', 'phone' => $this->mobile(), 'message' => $message.' (sample)', 'status' => $status]));
            $e->log('Received from the website.');
        }
    }
}
