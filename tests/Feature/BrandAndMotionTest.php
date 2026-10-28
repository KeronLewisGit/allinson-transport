<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The brand chrome and the motion layer are progressive: the server sends finished content, and
 * JavaScript only animates it. These tests check the HTML a visitor without JavaScript (or a
 * search engine) receives.
 */
class BrandAndMotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_follows_the_company_layout_name_bar_then_gold_navigation(): void
    {
        $html = $this->get('/services')->assertOk()->getContent();

        $this->assertStringContainsString('data-brandbar', $html);
        $this->assertStringContainsString('class="goldbar', $html);
        $this->assertStringContainsString('George Allinson', $html);
        $this->assertStringContainsString('(Transport) Ltd', $html);
        $this->assertMatchesRegularExpression('/t:\s*<a href="tel:\+441325461241"/', $html);
        $this->assertStringContainsString('aria-label="Main"', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        // Brandbar comes before the gold bar, as on the company's site.
        $this->assertLessThan(strpos($html, 'class="goldbar'), strpos($html, 'data-brandbar'));
    }

    public function test_home_content_is_complete_without_javascript(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // The headline is plain text; JavaScript splits it into words only when it runs.
        $this->assertMatchesRegularExpression('/<h1[^>]*data-words[^>]*>\s*We care for your cargo\s*<\/h1>/', $html);
        // Count-up figures carry their final published values in the HTML.
        foreach (['120', '250', '250,000'] as $figure) {
            $this->assertStringContainsString('data-count>'.$figure.'</span>', $html);
        }
        // Nothing is hidden by inline styles; hidden "before" states only exist under html.js in CSS.
        $this->assertDoesNotMatchRegularExpression('/style="[^"]*(opacity:\s*0|visibility:\s*hidden)/', $html);
        $this->assertStringContainsString('class="badge badge-draw', $html);
    }

    public function test_marquee_is_accessible_and_pausable(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('class="marquee"', $html);
        $this->assertSame(1, substr_count($html, '<div class="marquee-group" aria-hidden="true">'));
        $this->assertStringContainsString('Pause the moving band', $html);
        $this->assertStringContainsString('aria-pressed="false"', $html);
    }

    public function test_depot_map_labels_regions_and_claims_no_unconfirmed_sites(): void
    {
        foreach (['/', '/depots'] as $url) {
            $html = $this->get($url)->getContent();
            $this->assertStringContainsString('class="depot-map', $html);
            $this->assertStringContainsString('role="img"', $html);
            $this->assertSame(4, substr_count($html, 'class="route"'));
            foreach (['Darlington', 'Yorkshire', 'Liverpool', 'Cambridgeshire', 'Gloucestershire'] as $name) {
                $this->assertStringContainsString('>'.$name.'</text>', $html);
            }
        }
        $this->get('/depots')->assertSee('not its site', false)->assertSee('Natural Earth', false);
    }

    public function test_quote_route_strip_has_server_rendered_defaults(): void
    {
        $html = $this->get('/quote')->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'class="route-strip'));
        $this->assertStringContainsString('>Your postcode</b>', $html);
        $this->assertStringContainsString('>Any UK postcode</b>', $html);
        $this->assertStringContainsString('x-ref="form"', $html);
    }

    public function test_careers_story_lists_every_step_in_the_html(): void
    {
        $response = $this->get('/careers')->assertOk();

        $response->assertSee('data-story', false)
            ->assertSee('Employment-Application-Form-2012-Website.doc')
            ->assertSeeInOrder(['The old way', 'Apply on your phone', 'HR calls you back', 'Upload your licence and cards', 'Assessment drive', 'Welcome aboard']);
    }

    public function test_thanks_page_has_the_received_stamp(): void
    {
        $this->withSession(['last_submission' => ['reference' => 'EN-26-00001', 'kind' => 'Enquiry']])
            ->get('/thanks')
            ->assertOk()->assertSee('class="stamp"', false)->assertSee('Received');
    }
}
