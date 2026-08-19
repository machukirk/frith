<?php

namespace App\Filament\Resources\Forms\Pages;

use App\Filament\Resources\Forms\FormResource;
use App\Models\FormOption;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * The choices people pick from.
 *
 * One rule governs this screen. What a family selected is stored as a slug, so
 * deleting an option somebody chose leaves them with an answer that resolves to
 * nothing and they quietly stop matching on it. Archiving takes it off the form
 * while every answer already given keeps its meaning — so Archive is what is
 * offered, and Delete only appears for an option nobody has ever picked.
 */
class ManageFormOptions extends ManageRelatedRecords
{
    protected static string $resource = FormResource::class;

    protected static string $relationship = 'options';

    protected static ?string $title = 'Options';

    public function getBreadcrumb(): string
    {
        return 'Options';
    }

    public function getHeading(): string
    {
        return $this->getOwnerRecord()->name;
    }

    public function getSubheading(): ?string
    {
        return 'The choices people pick from. Reword them freely; archive rather than delete anything somebody has chosen.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Callout::make('The wording is yours to change, the slug is not')
                ->description('Reword an option whenever you like — the database stores a short slug underneath, so nobody who already chose it is affected. The slug itself is fixed once the option exists.')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('warning')
                ->columnSpanFull(),

            Select::make('group')
                ->label('Which list')
                ->options(FormOption::GROUPS)
                ->default('support_areas')
                ->required()
                ->live()
                ->afterStateUpdated(fn (callable $set) => $set('parent_id', null))
                ->disabledOn('edit'),

            // Only the areas of family life have anything underneath them. Left
            // on show for the other lists, this offers to file an interest as a
            // detailed statement about school, which is not a choice anybody
            // means to make.
            Select::make('parent_id')
                ->label('Sits under')
                ->placeholder('Nothing — this is a top-level choice')
                ->options(fn () => $this->getOwnerRecord()
                    ->options()
                    ->whereNull('parent_id')
                    ->where('group', 'support_areas')
                    ->orderBy('position')
                    ->pluck('label', 'id'))
                ->helperText('Detailed statements sit under an area of family life. Leave this empty for an area itself.')
                ->visible(fn (callable $get, string $operation) => $operation === 'edit'
                    ? true
                    : $get('group') === 'support_areas')
                ->disabledOn('edit'),

            TextInput::make('label')
                ->label('What people read')
                ->required()
                ->maxLength(500)
                ->columnSpanFull()
                ->live(onBlur: true)
                // Derived once from the first label, then left alone. Editing
                // the wording later must never move the slug.
                ->afterStateUpdated(function ($state, callable $set, callable $get, string $operation) {
                    if ($operation === 'create' && blank($get('slug'))) {
                        $set('slug', Str::slug(Str::limit((string) $state, 40, '')));
                    }
                }),

            Textarea::make('description')
                ->label('Smaller line underneath')
                ->rows(2)
                ->maxLength(255)
                ->columnSpanFull(),

            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(120)
                ->disabledOn('edit')
                ->rules(['regex:/^[a-z0-9-]+$/'])
                ->helperText('What gets recorded against a family. Fixed once saved.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('parent'))
            ->defaultSort('position')
            ->columns([
                TextColumn::make('label')
                    ->label('Option')
                    ->wrap()
                    ->searchable()
                    ->description(fn (FormOption $record) => $record->parent?->label)
                    ->weight(fn (FormOption $record) => $record->parent_id === null ? 'medium' : null),

                TextColumn::make('group')
                    ->label('List')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state) => FormOption::GROUP_BADGES[$state] ?? $state),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->color('gray')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('usage')
                    ->label('Chosen by')
                    ->state(fn (FormOption $record) => $record->usageCount())
                    ->formatStateUsing(fn (int $state) => $state === 0
                        ? '—'
                        : $state.' famil'.($state === 1 ? 'y' : 'ies'))
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'gray'),

                IconColumn::make('archived_at')
                    ->label('On the form')
                    ->boolean()
                    ->getStateUsing(fn (FormOption $record) => ! $record->isArchived())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-archive-box')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->label('List')
                    ->options(FormOption::GROUPS),

                TernaryFilter::make('archived')
                    ->label('Shown on the form')
                    ->placeholder('All options')
                    ->trueLabel('On the form')
                    ->falseLabel('Archived only')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('archived_at'),
                        false: fn (Builder $query) => $query->whereNotNull('archived_at'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add an option')
                    ->mutateDataUsing(function (array $data): array {
                        $data['position'] = (int) $this->getOwnerRecord()
                            ->options()
                            ->where('group', $data['group'])
                            ->where('parent_id', $data['parent_id'] ?? null)
                            ->max('position') + 1;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()->label('Edit wording'),

                Action::make('archive')
                    ->label(fn (FormOption $record) => $record->isArchived() ? 'Put back' : 'Archive')
                    ->icon(fn (FormOption $record) => $record->isArchived() ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-archive-box')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading(fn (FormOption $record) => $record->isArchived()
                        ? 'Put this back on the form?'
                        : 'Take this off the form?')
                    ->modalDescription(fn (FormOption $record) => $record->isArchived()
                        ? 'It will start being offered again. Anybody who chose it before keeps their answer either way.'
                        : self::archiveWarning($record))
                    ->action(function (FormOption $record) {
                        $wasArchived = $record->isArchived();

                        $record->forceFill(['archived_at' => $wasArchived ? null : now()])->save();

                        Notification::make()
                            ->title($wasArchived ? 'Back on the form' : 'Taken off the form')
                            ->body('Answers already given are untouched.')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()
                    ->visible(fn (FormOption $record) => $record->usageCount() === 0 && $record->children()->count() === 0)
                    ->modalHeading('Delete this option?')
                    ->modalDescription('Nobody has chosen this one, so deleting it is safe. If anybody had, you would be archiving it instead.'),
            ])
            ->reorderable('position')
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading('No options yet');
    }

    private static function archiveWarning(FormOption $option): string
    {
        $used = $option->usageCount();

        $base = 'It stops being offered to new people straight away.';

        return $used === 0
            ? $base.' Nobody has chosen it, so nothing else changes.'
            : $base." {$used} famil".($used === 1 ? 'y has' : 'ies have')
                .' already chosen it, and their answer is kept exactly as it is.';
    }
}
