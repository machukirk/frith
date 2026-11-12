<?php

namespace App\Filament\Resources\Journeys;

use App\Filament\Resources\Journeys\Pages\EditJourney;
use App\Filament\Resources\Journeys\Pages\ListJourneys;
use App\Models\Journey;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The nine Journeys, as an editor can change them.
 *
 * These words are read twice: on the home page, where they are the first
 * thing a visitor sees about what Frith is for, and inside, where a family
 * decides whether a Journey is theirs. Until now they could only be changed
 * by editing config/frith-journeys.php and deploying.
 *
 * Two things are deliberately not on offer here.
 *
 * A slug cannot be changed. It is in the URL a family may have bookmarked and
 * in the membership table, so moving one silently detaches everybody in it.
 *
 * A Journey cannot be deleted, only archived. Deleting takes the Journey out
 * from under the families who joined it, and takes with it the reason two
 * people were ever introduced. Archiving stops anybody new joining and leaves
 * everything that already happened intact.
 */
class JourneyResource extends Resource
{
    protected static ?string $model = Journey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Journeys';

    protected static ?string $modelLabel = 'Journey';

    protected static ?int $navigationSort = 2;

    /** Never. See the class comment. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Callout::make('These words appear twice')
                ->description('On the home page, where they are the first thing a visitor reads about what Frith is for, and inside, where a family decides whether a Journey is theirs.')
                ->icon(Heroicon::OutlinedMap)
                ->color('info')
                ->columnSpanFull(),

            TextInput::make('title')
                ->label('Name')
                ->required()
                ->maxLength(120)
                ->helperText('What a family sees on the card.')
                ->columnSpanFull(),

            Textarea::make('blurb')
                ->label('What it is for')
                ->rows(4)
                ->maxLength(600)
                ->helperText('A sentence or two. Somebody deciding whether this is them is reading it.')
                ->columnSpanFull(),

            TextInput::make('slug')
                ->label('Address')
                ->disabled()
                ->dehydrated(false)
                ->helperText('Fixed. It is in the link families may have saved and in the record of who joined, so moving it would detach everybody in this Journey.')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Journey')
                    ->searchable()
                    ->description(fn (Journey $record): ?string => $record->isArchived()
                        ? 'Archived '.$record->archived_at->format('j M Y').'. Nobody new can join.'
                        : null),

                TextColumn::make('members_count')
                    ->label('Families')
                    ->counts('members')
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Address')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->recordActions([
                EditAction::make(),

                // Archive rather than delete, and owners only: it changes what
                // families can do, not just what they read.
                Action::make('archive')
                    ->label(fn (Journey $record): string => $record->isArchived() ? 'Put back' : 'Archive')
                    ->icon(fn (Journey $record): Heroicon => $record->isArchived()
                        ? Heroicon::OutlinedArrowUturnLeft
                        : Heroicon::OutlinedArchiveBox)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Journey $record): string => $record->isArchived()
                        ? 'Put '.$record->title.' back?'
                        : 'Archive '.$record->title.'?')
                    ->modalDescription(fn (Journey $record): string => $record->isArchived()
                        ? 'It goes back on the home page and in the directory, and families can join it again.'
                        : 'It comes off the home page and the directory, so nobody new can join. The families already in it keep their membership, and every connection made through it stays.')
                    ->visible(fn (): bool => Filament::auth()->user()?->isOwner() ?? false)
                    ->action(fn (Journey $record) => $record
                        ->forceFill(['archived_at' => $record->isArchived() ? null : now()])
                        ->save()),
            ])
            ->toolbarActions([]);
    }

    /** Archived ones are still listed here, which is the point of archiving. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderBy('position');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJourneys::route('/'),
            'edit' => EditJourney::route('/{record}/edit'),
        ];
    }
}
