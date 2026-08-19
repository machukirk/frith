<?php

namespace App\Filament\Resources\Registrations\Tables;

use App\Models\Registration;
use App\Support\Taxonomy;
use Filament\Actions\Action;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the Founders as a CSV, with slugs resolved back to their labels.
 *
 * A file full of "behaviour-relationships.masking" is not something anybody
 * can read, so the export writes what the family actually ticked.
 */
class ExportRegistrationsAction
{
    public static function make(): Action
    {
        return Action::make('export')
            ->label('Download CSV')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->modalHeading('Download the Founders')
            ->modalDescription('This file contains names, email addresses, rough locations and what families told us about their lives. Keep it somewhere private, and delete it when you are finished with it.')
            ->modalSubmitActionLabel('Download')
            ->action(fn (): StreamedResponse => static::response());
    }

    public static function response(): StreamedResponse
    {
        return response()->streamDownload(
            static::write(...),
            'frith-founders-'.now()->format('Y-m-d').'.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    private static function write(): void
    {
        $out = fopen('php://output', 'w');

        fputcsv($out, [
            'Founder number', 'Name', 'Email', 'Postcode area',
            'Family', 'Children (born)', 'Support areas', 'Detailed experiences',
            'Progress', 'Registered', 'Consent wording', 'Consent version', 'Consented at',
        ]);

        Registration::query()
            ->with(['children', 'experiences'])
            ->orderBy('founder_number')
            ->chunk(200, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->founder_number,
                        $r->first_name,
                        $r->email,
                        $r->postcode_outcode,
                        collect($r->family_structures ?? [])
                            ->map(fn ($s) => Taxonomy::familyStructureLabel($s))->implode('; '),
                        $r->children->map(fn ($c) => $c->bornLabel())->implode('; '),
                        collect($r->orderedSupportAreas())
                            ->map(fn ($s) => Taxonomy::categoryLabel($s))->implode('; '),
                        $r->experiences->map(fn ($e) => $e->categoryLabel().': '.$e->label())->implode(' | '),
                        $r->isComplete() ? 'Complete' : 'Step '.$r->furthest_step.' of 5',
                        $r->created_at?->toDateTimeString(),
                        $r->consent_text,
                        $r->consent_version,
                        $r->consented_at?->toDateTimeString(),
                    ]);
                }
            });

        fclose($out);
    }
}
