<?php

namespace App\Filament\Resources\Forms;

use App\Filament\Resources\Forms\Pages\EditForm;
use App\Filament\Resources\Forms\Pages\ListForms;
use App\Filament\Resources\Forms\Pages\ManageFormOptions;
use App\Filament\Resources\Forms\Schemas\FormContentSchema;
use App\Models\Form as FormModel;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The words and choices on each form.
 *
 * Structure is not here. Which screens exist, in what order, and how they
 * behave is code, because this form has real behaviour a generic builder
 * cannot express — saving on every step, the child repeater, section two
 * appearing only for the areas somebody picked.
 */
class FormResource extends Resource
{
    protected static ?string $model = FormModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Forms';

    protected static ?string $modelLabel = 'form';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        // A new form needs a controller and a route to go with it, so they
        // arrive in code and are seeded, the same way pages do.
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return FormContentSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Form')->weight('medium'),
                TextColumn::make('description')->label('')->color('gray')->wrap(),
                TextColumn::make('steps_count')->counts('steps')->label('Screens')->alignCenter(),
                TextColumn::make('updated_at')->label('Last changed')->since()->sortable(),
                TextColumn::make('updatedBy.name')->label('By')->placeholder('—'),
            ])
            ->recordActions([EditAction::make()->label('Edit wording')])
            ->paginated(false);
    }

    /**
     * The tabs down the side of a form.
     *
     * Without this the Options page has a route and no way to reach it — it
     * was only ever openable by typing the URL, which nobody was going to do.
     *
     * @return array<NavigationItem>
     */
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            EditForm::class,
            ManageFormOptions::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForms::route('/'),
            'edit' => EditForm::route('/{record}/edit'),
            // Its own page rather than a panel under the wording: there are
            // seventy-odd options, and they need search, filters and room.
            'options' => ManageFormOptions::route('/{record}/options'),
        ];
    }
}
