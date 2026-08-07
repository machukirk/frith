<?php

namespace App\Filament\Resources\WaitlistSignups\Schemas;

use App\Models\WaitlistSignup;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * What we hold about one person, including the record of what they agreed to.
 *
 * If somebody ever asks "why are you emailing me?", or the ICO does, this
 * screen is the answer — so it shows the wording that was on screen at the
 * time, not the wording that is on screen now.
 */
class WaitlistSignupInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextEntry::make('email')->label('Email')->copyable(),

                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (WaitlistSignup $r) => match (true) {
                        $r->hasUnsubscribed() => 'Unsubscribed',
                        $r->isConfirmed() => 'Confirmed',
                        default => 'Waiting to confirm',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Confirmed' => 'success',
                        'Unsubscribed' => 'gray',
                        default => 'warning',
                    }),

                TextEntry::make('source')->label('Came from'),
            ])->columns(3),

            Section::make('Dates')->schema([
                TextEntry::make('created_at')->label('Signed up')->dateTime('j M Y, H:i'),
                TextEntry::make('confirmation_sent_at')->label('Confirmation sent')->dateTime('j M Y, H:i')->placeholder('—'),
                TextEntry::make('confirmed_at')->label('Confirmed')->dateTime('j M Y, H:i')->placeholder('—'),
                TextEntry::make('unsubscribed_at')->label('Unsubscribed')->dateTime('j M Y, H:i')->placeholder('—'),
            ])->columns(2),

            Section::make('Record of consent')
                ->description('Captured at the moment they signed up, and never rewritten afterwards.')
                ->schema([
                    TextEntry::make('consent_text')
                        ->label('What they agreed to')
                        ->columnSpanFull(),
                    TextEntry::make('consent_version')->label('Wording version'),
                    TextEntry::make('consented_at')->label('Agreed at')->dateTime('j M Y, H:i'),
                    TextEntry::make('consent_ip')->label('From IP')->placeholder('—'),
                    TextEntry::make('consent_user_agent')
                        ->label('Browser')
                        ->placeholder('—')
                        ->columnSpanFull()
                        ->limit(120),
                ])
                ->columns(2)
                ->collapsed(),
        ]);
    }
}
