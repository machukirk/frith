<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Registration;
use App\Support\BrandAsset;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_page_carries_a_canonical_url_and_is_indexable(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('home').'"', false)
            ->assertSee('name="robots" content="index, follow', false);
    }

    #[Test]
    public function the_open_graph_and_twitter_tags_are_complete(): void
    {
        $response = $this->get(route('home'))->assertOk();

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
        $this->get(route('home'))
            ->assertDontSee('twitter:site', false)
            ->assertDontSee('twitter:creator', false);
    }

    #[Test]
    public function the_structured_data_is_a_valid_linked_graph(): void
    {
        $this->seed(PageSeeder::class);

        $html = $this->get(route('home'))->assertOk()->getContent();

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
        $page = Page::query()->where('slug', 'home')->sole();

        $content = $page->content;
        $content['meta']['description'] = 'A description written in the admin panel.';
        $page->update(['content' => $content]);

        $this->get(route('home'))
            ->assertSee('A description written in the admin panel.', false);
    }

    #[Test]
    public function the_registration_screens_are_not_indexed(): void
    {
        // A half-finished form is nothing anybody should land on from a search
        // result, and the screens carry no structured data either.
        $this->get(route('register.step', 'you'))
            ->assertOk()
            ->assertSee('content="noindex, nofollow"', false)
            ->assertDontSee('application/ld+json', false);
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
        $this->assertStringContainsString('<loc>'.route('home').'</loc>', $xml);
        $this->assertStringContainsString('<lastmod>', $xml);
        $this->assertNotFalse(simplexml_load_string($xml), 'sitemap is not well-formed XML');
    }

    #[Test]
    public function robots_txt_points_at_the_sitemap_and_hides_the_signed_urls(): void
    {
        $robots = $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->getContent();

        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $robots);
        $this->assertStringContainsString('Disallow: /admin', $robots);

        // The registration steps stay crawlable on purpose: they carry a
        // noindex tag, and a crawler has to fetch a page to see that.
        $this->assertStringNotContainsString('Disallow: /join', $robots);
    }

    #[Test]
    public function a_copy_of_the_site_tells_every_crawler_to_go_away(): void
    {
        // Staging is stood up by copying production's .env, so its APP_URL
        // still says frith.community while it answers on another domain
        // entirely. The host the request arrived on is what decides, which is
        // what makes this survive that.
        config(['frith.site.canonical_host' => 'frith.community']);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /')
            ->assertDontSee('Allow: /')
            ->assertSee('https://frith.community');

        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('name="robots" content="noindex, nofollow"', false)
            ->assertDontSee('application/ld+json', false);

        // And the things that never went through the layout are covered too.
        $this->get(route('sitemap'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get(route('manifest'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    #[Test]
    public function the_real_site_carries_no_such_header(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeaderMissing('X-Robots-Tag');
    }

    #[Test]
    public function the_indexing_rule_can_be_overridden_when_somebody_means_to(): void
    {
        config([
            'frith.site.canonical_host' => 'frith.community',
            'frith.site.indexable' => 'true',
        ]);

        $this->get('/robots.txt')->assertOk()->assertSee('Allow: /');
        $this->get(route('home'))->assertOk()->assertHeaderMissing('X-Robots-Tag');
    }

    #[Test]
    public function the_web_manifest_is_served_with_the_right_content_type(): void
    {
        // Cloudways' nginx has no mime type for .webmanifest and serves a
        // static one as application/octet-stream, hence the route.
        $response = $this->get(route('manifest'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json');

        $manifest = $response->json();

        $this->assertSame('Frith', $manifest['short_name']);
        $this->assertSame('#F8F4EE', $manifest['theme_color']);

        $purposes = array_column($manifest['icons'], 'purpose');
        $this->assertContains('maskable', $purposes, 'Android crops icons to a shape; a maskable one is needed');

        foreach ($manifest['icons'] as $icon) {
            $path = public_path(ltrim(parse_url($icon['src'], PHP_URL_PATH), '/'));
            $this->assertFileExists($path);
        }
    }

    #[Test]
    public function the_canonical_sitemap_and_structured_data_all_agree_on_the_homepage_url(): void
    {
        // Three different spellings of the same page is how a crawler ends up
        // deciding for itself which one is canonical.
        $home = route('home');
        $html = $this->get($home)->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.$home.'"', $html);
        $this->assertStringContainsString('property="og:url" content="'.$home.'"', $html);
        $this->assertStringContainsString('<loc>'.$home.'</loc>', $this->get('/sitemap.xml')->getContent());

        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $graph = json_decode($m[1], true)['@graph'];
        foreach ($graph as $node) {
            if (in_array($node['@type'], ['WebSite', 'WebPage'], true)) {
                $this->assertSame($home, $node['url']);
            }
        }
    }

    #[Test]
    public function brand_assets_carry_a_version_so_a_new_logo_actually_reaches_people(): void
    {
        // public/brand is served with a year-long cache under filenames that
        // never change, so replacing the logo on the server is not enough — a
        // returning visitor keeps the old one. The URL has to move with it.
        $html = $this->get(route('home'))->assertOk()->getContent();

        preg_match_all('#(/brand/[a-z0-9/_.@-]+\.(?:svg|png))(\?v=\d+)?#i', $html, $matches, PREG_SET_ORDER);

        $this->assertNotEmpty($matches, 'the page should reference some brand assets');

        foreach ($matches as $match) {
            $this->assertArrayHasKey(2, $match, "{$match[1]} is served unversioned");
            $this->assertNotEmpty($match[2], "{$match[1]} is served unversioned");
        }

        // And the version has to be the file's own, not a constant.
        $logo = 'brand/logo/frith-logo-horizontal-fullcolour.svg';
        $this->assertStringContainsString(
            '?v='.filemtime(public_path($logo)),
            BrandAsset::url($logo),
        );
    }

    #[Test]
    public function every_icon_referenced_in_the_head_exists(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        preg_match_all('#<link rel="(?:icon|apple-touch-icon)"[^>]*href="([^"]+)"#', $html, $m);
        $this->assertNotEmpty($m[1]);

        foreach ($m[1] as $href) {
            $path = public_path(ltrim(parse_url($href, PHP_URL_PATH), '/'));
            $this->assertFileExists($path, "{$href} is referenced but missing");
        }
    }

    #[Test]
    public function the_page_has_exactly_one_h1(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'), 'a page should have one h1');
    }
}
