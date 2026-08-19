<?php

namespace App\Filament\Resources\Registrations\Schemas;

use App\Models\Registration;
use App\Support\Taxonomy;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * One family, read-only.
 *
 * Note the synthetic keys on the summaries. Naming an entry after a relation
 * or an array column makes Filament map the formatter over every element, so
 * `children` renders the whole list once per child. Building the string in
 * ->state() instead keeps it to one.
 */
class RegistrationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make()->schema([
                TextEntry::make('founder_number')
                    ->label('Founder')
                    ->formatStateUsing(fn ($state) => $state ? '#'.$state : '—')
                    ->badge()
                    ->color('warning'),
                TextEntry::make('name')
                    ->label('Name')
                    ->state(fn (Registration $r) => $r->fullName()),
                TextEntry::make('email')->copyable(),
                TextEntry::make('postcode_outcode')->label('Postcode area')->placeholder('—'),
            ])->columns(4),

            Section::make('Their family')->schema([
                TextEntry::make('family_structures')
                    ->label('Who is part of the family')
                    ->placeholder('Not answered')
                    ->badge()
                    ->color('gray')
                    // One slug at a time: the column is an array, so Filament
                    // calls this once per element.
                    ->formatStateUsing(fn (string $state) => Taxonomy::familyStructureLabel($state)),

                TextEntry::make('children_summary')
                    ->label('Children')
                    ->placeholder('Not answered')
                    ->state(fn (Registration $r) => $r->children->isEmpty() ? null : $r->children
                        ->map(fn ($c) => $c->bornLabel().' · '.$c->age().' years old')
                        ->implode("\n"))
                    ->listWithLineBreaks(),
            ]),

            Section::make('What they would like support with')
                ->description('Private. Used to match families, never shown on a profile.')
                ->schema([
                    TextEntry::make('support_areas')
                        ->label('Areas')
                        ->placeholder('Not answered')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => Taxonomy::categoryLabel($state)),

                    TextEntry::make('experiences_summary')
                        ->label('In more detail')
                        ->placeholder('Skipped — which was fine, it is optional')
                        ->state(fn (Registration $r) => $r->experiences->isEmpty() ? null : $r->experiencesByCategory()
                            ->map(fn ($group, $cat) => Taxonomy::categoryLabel($cat).' — '
                                .$group->map(fn ($e) => $e->label())->implode('; '))
                            ->values()
                            ->all())
                        ->listWithLineBreaks()
                        ->bulleted(),
                ]),

            Section::make('Progress')->schema([
                TextEntry::make('progress')
                    ->label('How far they got')
                    ->badge()
                    ->state(fn (Registration $r) => $r->isComplete() ? 'Finished section one' : 'Step '.$r->furthest_step.' of 5')
                    ->color(fn (string $state) => $state === 'Finished section one' ? 'success' : 'warning'),
                TextEntry::make('created_at')->label('Registered')->dateTime('j M Y, H:i'),
                TextEntry::make('completed_at')->label('Finished')->dateTime('j M Y, H:i')->placeholder('—'),
            ])->columns(3),

            Section::make('Record of consent')
                ->description('Captured when they registered, and never rewritten afterwards.')
                ->collapsed()
                ->schema([
                    TextEntry::make('consent_text')->label('What they agreed to')->columnSpanFull(),
                    TextEntry::make('consent_version')->label('Wording version'),
                    TextEntry::make('consented_at')->label('Agreed at')->dateTime('j M Y, H:i'),
                    TextEntry::make('consent_ip')->label('From IP')->placeholder('—'),
                    TextEntry::make('email_verified_at')->label('Email confirmed')->dateTime('j M Y, H:i')->placeholder('Not yet'),
                ])->columns(2),
        ]);
    }
}
