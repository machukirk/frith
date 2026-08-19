<?php

use App\Models\Page;
use App\Support\PageContent;
use Illuminate\Database\Migrations\Migration;

/**
 * Clears the old email sign-up out of the stored page content.
 *
 * The homepage no longer has a form on it, so the field labels and the three
 * screens people used to land on afterwards have nowhere to appear. Left in
 * place they would sit in the admin panel offering to edit copy that renders
 * nowhere, which is worse than useless — somebody would spend time on it.
 *
 * The button and its note move to a `cta` block, since "form" is no longer
 * what they belong to.
 */
return new class extends Migration
{
    public function up(): void
    {
        $page = Page::query()->where('slug', 'coming-soon')->first();

        if ($page === null) {
            return;
        }

        $content = $page->content;

        $content['cta'] = array_filter([
            'button' => $content['form']['button'] ?? null,
            'note' => $content['founders_note'] ?? null,
        ]) + config('frith.coming_soon.cta');

        foreach (['form', 'founders_note', 'success', 'confirmed', 'unsubscribed'] as $key) {
            unset($content[$key]);
        }

        $page->update(['content' => $content]);

        PageContent::forget('coming-soon');
    }

    public function down(): void
    {
        $page = Page::query()->where('slug', 'coming-soon')->first();

        if ($page === null) {
            return;
        }

        $content = $page->content;
        $content['form'] = ['button' => $content['cta']['button'] ?? 'Join Frith'];
        $content['founders_note'] = $content['cta']['note'] ?? null;
        unset($content['cta']);

        $page->update(['content' => $content]);

        PageContent::forget('coming-soon');
    }
};
