<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\WaitlistSignup;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_page_carries_a_canonical_url_and_is_indexable(): void
    {
        $this->get(route('coming-soon'))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('coming-soon').'"', false)
            ->assertSee('name="robots" content="index, follow', false);
    }

    #[Test]
    public function the_open_graph_and_twitter_tags_are_complete(): void
    {
        $response = $this->get(route('coming-soon'))->assertOk();

        foreach ([
            'og:type', 'og:site_name', 'og:title', 'og:description', 'og:url',
            'og:locale', 'og:image', 'og:image:width', 'og:image:height', 'og:image:alt',
        ] as $property) {
            $response->assertSee('property="'.$property.'"', false);
        }

        foreach (['twitter:card', 'twitter:title', 'twitter:description', 'twitter:image', 'twitter:image:alt'] as $name) {
            $response->assertSee('name="'.$name.'"', false);
        }
    }

    #[Test]
    public function no_social_handles_are_claimed_that_do_not_exist(): void
    {
        // Pointing twitter:site at a handle nobody owns hands the card's
        // attribution to whoever registers it later.
        $this->get(route('coming-soon'))
            ->assertDontSee('twitter:site', false)
            ->assertDontSee('twitter:creator', false);
    }

    #[Test]
    public function the_structured_data_is_a_valid_linked_graph(): void
    {
        $this->seed(PageSeeder::class);

        $html = $this->get(route('coming-soon'))->assertOk()->getContent();

        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $this->assertNotEmpty($m, 'no JSON-LD block found');

        $data = json_decode($m[1], true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'JSON-LD is not valid JSON');
        $this->assertSame('https://schema.org', $data['@context']);

        $types = array_column($data['@graph'], '@type');
        foreach (['Organization', 'WebSite', 'WebPage', 'ImageObject'] as $type) {
            $this->assertContains($type, $types, "graph is missing {$type}");
        }

        // Every @id referenced from a node must resolve to a node in the graph,
        // or the graph is a set of dangling pointers.
        $ids = array_filter(array_column($data['@graph'], '@id'));
        $referenced = [];
        array_walk_recursive($data['@graph'], function ($value, $key) use (&$referenced) {
            if ($key === '@id') {
                $referenced[] = $value;
            }
        });

        foreach (array_diff($referenced, $ids) as $dangling) {
            $this->fail("JSON-LD references {$dangling}, which is not a node in the graph");
        }

        $org = collect($data['@graph'])->firstWhere('@type', 'Organization');
        $this->assertSame(config('frith.company.name'), $org['legalName']);
        $this->assertSame(config('frith.company.contact_email'), $org['email']);
    }

    #[Test]
    public function the_structured_data_follows_the_content_when_it_is_edited(): void
    {
        $this->seed(PageSeeder::class);
        $page = Page::query()->where('slug', 'coming-soon')->sole();

        $content = $page->content;
        $content['meta']['description'] = 'A description written in the admin panel.';
        $page->update(['content' => $content]);

        $this->get(route('coming-soon'))
            ->assertSee('A description written in the admin panel.', false);
    }

    #[Test]
    public function the_pages_reached_from_an_email_are_not_indexed(): void
    {
        // They have nothing to offer a search result, and their URLs carry a
        // signature that should never end up in an index.
        $signup = WaitlistSignup::factory()->create();

        $this->get(URL::temporarySignedRoute('waitlist.confirm', now()->addDays(14), ['signup' => $signup->public_id]))
            ->assertOk()
            ->assertSee('content="noindex, nofollow"', false)
            ->assertDontSee('application/ld+json', false);

        $this->get(URL::signedRoute('waitlist.unsubscribe', ['signup' => $signup->public_id]))
            ->assertOk()
            ->assertSee('content="noindex, nofollow"', false);
    }

    #[Test]
    public function the_sitemap_lists_the_page_with_a_last_modified_date(): void
    {
        $this->seed(PageSeeder::class);

        $response = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = $response->getContent();

        $this->assertStringStartsWith('<?xml', $xml);
        $this->assertStringContainsString('<loc>'.route('coming-soon').'</loc>', $xml);
        $this->assertStringContainsString('<lastmod>', $xml);
        $this->assertNotFalse(simplexml_load_string($xml), 'sitemap is not well-formed XML');
    }

    #[Test]
    public function robots_txt_points_at_the_sitemap_and_hides_the_signed_urls(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Sitemap: https://frith.community/sitemap.xml', $robots);
        $this->assertStringContainsString('Disallow: /admin', $robots);
        $this->assertStringContainsString('Disallow: /waitlist/', $robots);
    }

    #[Test]
    public function the_web_manifest_is_valid_and_its_icons_exist(): void
    {
        $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true);

        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertSame('Frith', $manifest['short_name']);
        $this->assertSame('#F8F4EE', $manifest['theme_color']);

        $purposes = array_column($manifest['icons'], 'purpose');
        $this->assertContains('maskable', $purposes, 'Android crops icons to a shape; a maskable one is needed');

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    #[Test]
    public function every_icon_referenced_in_the_head_exists(): void
    {
        $html = $this->get(route('coming-soon'))->assertOk()->getContent();

        preg_match_all('#<link rel="(?:icon|apple-touch-icon|manifest)"[^>]*href="([^"]+)"#', $html, $m);
        $this->assertNotEmpty($m[1]);

        foreach ($m[1] as $href) {
            $path = public_path(ltrim(parse_url($href, PHP_URL_PATH), '/'));
            $this->assertFileExists($path, "{$href} is referenced but missing");
        }
    }

    #[Test]
    public function the_page_has_exactly_one_h1(): void
    {
        $html = $this->get(route('coming-soon'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'), 'a page should have one h1');
    }
}
