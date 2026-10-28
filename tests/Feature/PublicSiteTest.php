<?php

namespace Tests\Feature;

use App\Models\JobPosting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function pages(): array
    {
        return [
            'home' => ['/'], 'services' => ['/services'], 'fleet' => ['/fleet-and-warehousing'], 'depots' => ['/depots'], 'about' => ['/about'],
            'contact' => ['/contact'], 'quote' => ['/quote'], 'warehousing' => ['/warehousing-enquiry'], 'careers' => ['/careers'],
            'apply' => ['/careers/apply'], 'privacy' => ['/privacy'], 'credits' => ['/photo-credits'], 'sitemap' => ['/sitemap'], 'login' => ['/login'],
        ];
    }

    #[DataProvider('pages')]
    public function test_public_pages_render(string $url): void
    {
        $this->get($url)->assertOk()->assertSee('Allinson', false);
    }

    public function test_every_inner_page_opens_with_the_shared_header(): void
    {
        foreach (self::pages() as [$url]) {
            if (in_array($url, ['/', '/login'], true)) {
                continue;
            }
            $this->get($url)->assertSee('tacho relative isolate', false)->assertSee('class="consignment', false)->assertSee('class="chevrons"', false);
        }
    }

    public function test_published_figures_appear_exactly_and_are_cited(): void
    {
        $home = $this->get('/')->assertOk();
        $home->assertSee('120')->assertSee('250')->assertSee('250,000')->assertSee('1955');
        $home->assertSee(ga('sources.home.url'), false);
        $this->get('/fleet-and-warehousing')->assertSee('500 TEU')->assertSee('45,000')->assertSee(ga('sources.containers.url'), false);
        $this->get('/about')->assertSee('Family run and owned since 1955')->assertSee(ga('sources.companies_house.url'), false);
    }

    public function test_unconfirmed_claims_and_names_stay_off_the_public_pages(): void
    {
        foreach (array_column(self::pages(), 0) as $url) {
            $html = strtolower(strip_tags((string) $this->get($url)->getContent()));
            foreach (['iso 9001', 'fors', 'haulier of the year', 'safety award', 'rha member', 'ukwa', 'barnfather', 'michael allinson', 'david allinson', 'george william', 'testimonial'] as $word) {
                $this->assertDoesNotMatchRegularExpression('/\\b'.preg_quote($word, '/').'\\b/', $html, "{$url} mentions {$word}");
            }
        }
    }

    public function test_only_the_head_office_address_is_shown_for_depots(): void
    {
        $html = (string) $this->get('/depots')->assertOk()->getContent();
        $this->assertStringContainsString('DL3 0PH', $html);
        foreach (['yorkshire', 'liverpool', 'cambridgeshire', 'gloucestershire'] as $depot) {
            $this->assertNull(ga("depots.{$depot}.address"), "{$depot} address must be confirmed before it is published");
            $this->assertStringContainsString(ga("depots.{$depot}.name"), $html);
        }
    }

    public function test_every_photo_is_credited_and_exists(): void
    {
        $credits = config('photo-credits');
        foreach ((array) config('allinson.photos') as $key => $meta) {
            $this->assertArrayHasKey($key, $credits, "{$key} has no credit");
            foreach ($meta['widths'] as $w) {
                $this->assertFileExists(public_path("img/{$key}-{$w}.webp"));
            }
        }
        $this->get('/photo-credits')->assertSee('Wikimedia Commons')->assertSee('rights to be confirmed');
    }

    public function test_every_public_page_has_a_photo_and_stock_photos_are_labelled(): void
    {
        foreach (self::pages() as [$url]) {
            if ($url === '/login') {
                continue;
            }
            $html = (string) $this->get($url)->getContent();
            $this->assertMatchesRegularExpression('/<img [^>]*src="[^"]+\/img\//', $html, "{$url} has no photograph");
        }
        $this->get('/services')->assertSee('Illustrative photo');
        $this->get('/careers')->assertSee('Illustrative photo');
        $this->get('/no-such-page')->assertNotFound()->assertSee('Illustrative photo');
        // The home and depot headers show the firm's own lorry and depot: no label.
        $this->assertStringNotContainsString('Illustrative photo', (string) $this->get('/')->getContent());
        $this->assertStringNotContainsString('Illustrative photo', (string) $this->get('/depots')->getContent());
    }

    public function test_preview_banner_and_noindex_until_launch(): void
    {
        config(['allinson.preview' => true]);
        $this->get('/')->assertSee('Concept preview prepared for George Allinson (Transport) Ltd')->assertSee('noindex, nofollow', false);
        config(['allinson.preview' => false]);
        $this->get('/')->assertDontSee('Concept preview')->assertDontSee('<meta name="robots"', false);
    }

    public function test_uk_formats(): void
    {
        $this->get('/contact')->assertSee('01325 461241')->assertSee('tel:+441325461241', false)->assertSee('lang="en-GB"', false);
        $this->assertSame('£1,250', gbp(1250));
        $this->assertSame('DL3 0PH', uk_postcode('dl30ph'));
        $this->assertSame('020 7946 0018', format_phone('+44 20 7946 0018'));
    }

    public function test_submit_buttons_read_correctly_without_javascript(): void
    {
        $html = (string) $this->get('/quote')->getContent();
        $this->assertMatchesRegularExpression('/<span hidden[^>]*>Sending…<\/span>/u', $html);
    }

    public function test_old_site_addresses_redirect(): void
    {
        $this->get('/page.php?id=26')->assertStatus(301)->assertRedirect(route('careers.index'));
        $this->get('/page.php?id=4')->assertStatus(301)->assertRedirect(route('fleet'));
        $this->get('/page.php?id=999')->assertStatus(301)->assertRedirect(route('home'));
        $this->get('/page.php?id[]=1')->assertStatus(301);
        $this->get('/index.php')->assertStatus(301);
    }

    public function test_open_jobs_list_and_closed_jobs_404(): void
    {
        $open = JobPosting::factory()->create(['title' => 'Night trunker', 'depot' => 'yorkshire']);
        $closed = JobPosting::factory()->create(['title' => 'Old role', 'closes_on' => today()->subDay()]);
        $this->get('/careers')->assertSee('Night trunker')->assertDontSee('Old role');
        $this->get('/careers?depot=yorkshire')->assertSee('Night trunker');
        $this->get('/careers?depot=liverpool')->assertDontSee('Night trunker');
        $this->get('/careers?depot[]=x')->assertOk();
        $this->get('/careers/'.$open->slug)->assertOk()->assertSee('JobPosting', false);
        $this->get('/careers/'.$closed->slug)->assertNotFound();
        $this->get('/careers/apply?job='.$open->slug)->assertOk()->assertSee('Night trunker');
    }

    public function test_sitemap_lists_pages_and_jobs(): void
    {
        $job = JobPosting::factory()->create();
        $xml = (string) $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->getContent();
        foreach (['services', 'fleet', 'depots', 'quotes.create', 'warehousing.create'] as $r) {
            $this->assertStringContainsString(route($r), $xml);
        }
        $this->assertStringContainsString(route('careers.show', $job), $xml);
    }

    public function test_security_headers(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
