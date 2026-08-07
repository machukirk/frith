<?php

namespace App\Filament\Resources\WaitlistSignups\Tables;

use App\Models\WaitlistSignup;
use Filament\Actions\Action;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the waiting list as a CSV.
 *
 * Filament ships a queued exporter, which needs its own tables and a running
 * worker. A waiting list is small and someone clicking "download" wants the
 * file now, so this streams straight out of the request instead.
 */
class ExportWaitlistAction
{
    public static function make(): Action
    {
        return Action::make('export')
            ->label('Download CSV')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->modalHeading('Download the waiting list')
            ->modalDescription('This file contains people’s email addresses. Keep it somewhere private, and delete it when you are finished with it.')
            ->modalSubmitActionLabel('Download')
            ->action(fn (): StreamedResponse => static::response());
    }

    /** Separate from the action so it can be exercised without a Livewire component. */
    public static function response(): StreamedResponse
    {
        return response()->streamDownload(
            static::write(...),
            'frith-waiting-list-'.now()->format('Y-m-d').'.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    private static function write(): void
    {
        $out = fopen('php://output', 'w');

        fputcsv($out, [
            'Email',
            'Status',
            'Signed up',
            'Confirmed',
            'Unsubscribed',
            'Source',
            'Consent wording',
            'Consent version',
            'Consented at',
        ]);

        WaitlistSignup::query()
            ->orderBy('created_at')
            ->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->email,
                        match (true) {
                            $r->hasUnsubscribed() => 'Unsubscribed',
                            $r->isConfirmed() => 'Confirmed',
                            default => 'Waiting to confirm',
                        },
                        $r->created_at?->toDateTimeString(),
                        $r->confirmed_at?->toDateTimeString(),
                        $r->unsubscribed_at?->toDateTimeString(),
                        $r->source,
                        // Carried into the export so the file stands as evidence
                        // on its own, not a list of addresses with no provenance.
                        $r->consent_text,
                        $r->consent_version,
                        $r->consented_at?->toDateTimeString(),
                    ]);
                }
            });

        fclose($out);
    }
}
