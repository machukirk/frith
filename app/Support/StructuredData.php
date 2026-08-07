<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Builds the JSON-LD for the site.
 *
 * One @graph with @id-linked nodes rather than several loose script blocks, so
 * search engines see one organisation referenced from the page and the logo,
 * instead of three unrelated fragments they have to reconcile.
 *
 * Everything here is a claim about Frith that has to stay true. Structured data
 * asserting things the page cannot back up is worse than none — it is what
 * manual spam actions are for.
 */
class StructuredData
{
    public static function comingSoon(): array
    {
        $base = rtrim(config('app.url'), '/');
        $copy = PageContent::for('coming-soon');

        $organisationId = $base.'/#organisation';
        $websiteId = $base.'/#website';
        $logoId = $base.'/#logo';

        return [
            '@context' => 'https://schema.org',
            '@graph' => [

                [
                    '@type' => 'Organization',
                    '@id' => $organisationId,
                    'name' => 'Frith',
                    'legalName' => config('frith.company.name'),
                    'url' => $base.'/',
                    'email' => config('frith.company.contact_email'),
                    'logo' => ['@id' => $logoId],
                    'image' => ['@id' => $logoId],
                    'slogan' => 'Find Your Village.',
                    'description' => 'Frith connects parents and carers of children with SEND to other families nearby, matching them on shared experience rather than diagnosis.',
                    'foundingDate' => '2026',
                    'areaServed' => [
                        '@type' => 'Country',
                        'name' => 'United Kingdom',
                    ],
                    'knowsAbout' => [
                        'Special educational needs and disabilities',
                        'Education, Health and Care Plans',
                        'Parent carer support',
                    ],
                    'contactPoint' => [
                        '@type' => 'ContactPoint',
                        'contactType' => 'customer support',
                        'email' => config('frith.company.contact_email'),
                        'areaServed' => 'GB',
                        'availableLanguage' => ['English'],
                    ],
                ],

                [
                    '@type' => 'ImageObject',
                    '@id' => $logoId,
                    'url' => asset('brand/logo/png/frith-logo-horizontal-fullcolour@2x.png'),
                    'contentUrl' => asset('brand/logo/png/frith-logo-horizontal-fullcolour@2x.png'),
                    'width' => 980,
                    'height' => 310,
                    'caption' => 'Frith',
                ],

                [
                    '@type' => 'WebSite',
                    '@id' => $websiteId,
                    'url' => $base.'/',
                    'name' => 'Frith',
                    'description' => $copy['meta']['description'] ?? null,
                    'publisher' => ['@id' => $organisationId],
                    'inLanguage' => 'en-GB',
                ],

                [
                    '@type' => 'WebPage',
                    '@id' => $base.'/#webpage',
                    'url' => $base.'/',
                    'name' => $copy['meta']['title'] ?? 'Frith',
                    'description' => $copy['meta']['description'] ?? null,
                    'isPartOf' => ['@id' => $websiteId],
                    'about' => ['@id' => $organisationId],
                    'primaryImageOfPage' => ['@id' => $base.'/#hero'],
                    'inLanguage' => 'en-GB',
                    // The page is a single sign-up form, so the only meaningful
                    // action on it is joining the waiting list.
                    'potentialAction' => [
                        '@type' => 'SubscribeAction',
                        'name' => 'Join the Frith waiting list',
                        'target' => [
                            '@type' => 'EntryPoint',
                            'urlTemplate' => route('waitlist.store'),
                            'httpMethod' => 'POST',
                            'contentType' => 'application/x-www-form-urlencoded',
                        ],
                    ],
                ],

                [
                    '@type' => 'ImageObject',
                    '@id' => $base.'/#hero',
                    'url' => self::heroUrl($copy),
                    'contentUrl' => self::heroUrl($copy),
                    'caption' => $copy['hero_image_alt'] ?? null,
                ],
            ],
        ];
    }

    private static function heroUrl(array $copy): string
    {
        return ! empty($copy['hero_image'])
            ? Storage::url($copy['hero_image'])
            : asset('brand/img/frith-hero-hillside.png');
    }
}
