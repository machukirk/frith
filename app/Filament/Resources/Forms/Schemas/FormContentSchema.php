<?php

namespace App\Filament\Resources\Forms\Schemas;

use App\Models\Form as FormModel;
use App\Models\FormOption;
use App\Support\RegistrationFlow;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * One screen per section, in the order a visitor meets them.
 *
 * The key of each screen and field is shown but not editable — it is what ties
 * the wording to the code that validates and saves it.
 *
 * The follow-up screens are listed too, even though they are not rows in this
 * table. There is one per area of family life somebody picks, so they cannot be
 * numbered steps — but an editor who never sees them here has no way of knowing
 * the screens exist, let alone where to change their wording.
 */
class FormContentSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Callout::make('Changing these words changes the live form')
                ->description('Every visitor sees the new wording as soon as you save. The choices people pick from are on the Options tab.')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('info')
                ->columnSpanFull(),

            Repeater::make('steps')
                ->relationship()
                ->label('Screens')
                ->columnSpanFull()
                ->orderColumn('position')
                ->reorderable(false)
                ->addable(false)
                ->deletable(false)
                ->itemLabel(fn (array $state): ?string => $state['heading'] ?? null)
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextInput::make('key')
                        ->label('Screen')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Set in code — it is what links this wording to the screen that uses it.'),

                    TextInput::make('heading')
                        ->label('Heading')
                        ->required()
                        ->maxLength(160)
                        ->helperText('The question at the top of the screen.'),

                    Textarea::make('standfirst')
                        ->label('Line underneath')
                        ->rows(2)
                        ->maxLength(400),

                    Toggle::make('is_private')
                        ->label('Show the "only used for matching" note')
                        ->helperText('Use this on any screen that asks for something we never show on a profile.'),

                    Section::make('Fields on this screen')
                        ->collapsed()
                        ->schema([
                            Repeater::make('fields')
                                ->relationship()
                                ->label('')
                                ->orderColumn('position')
                                ->reorderable(false)
                                ->addable(false)
                                ->deletable(false)
                                ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                                ->collapsible()
                                ->collapsed()
                                ->schema([
                                    TextInput::make('key')->label('Field')->disabled()->dehydrated(false),
                                    TextInput::make('label')->label('Label')->required()->maxLength(120),
                                    Textarea::make('help')->label('Help text underneath')->rows(2)->maxLength(400),
                                    TextInput::make('placeholder')->label('Greyed-out example')->maxLength(80),
                                ]),
                        ]),
                ]),

            Section::make('The follow-up screens')
                ->columnSpanFull()
                ->description('The screens between step '
                    .RegistrationFlow::number(RegistrationFlow::DETAIL_STEP).' and step '
                    .RegistrationFlow::number('interests').'.')
                ->collapsed()
                ->schema([
                    Callout::make('One screen per area somebody picks')
                        ->description('After step '.RegistrationFlow::number(RegistrationFlow::DETAIL_STEP)
                            .', everyone is asked for more detail about the areas of family life they chose — one screen'
                            .' each, so somebody who picks three areas sees three of them. That is why they are not in'
                            .' the list above: there is no fixed number of them. The counter stays on step '
                            .RegistrationFlow::number(RegistrationFlow::DETAIL_STEP)
                            .' throughout, so the form never looks longer for the people who tell us most.')
                        ->icon(Heroicon::OutlinedQuestionMarkCircle)
                        ->color('info')
                        ->columnSpanFull(),

                    Grid::make(2)->schema(fn (FormModel $record) => static::followUpScreens($record)),

                    Callout::make('Their wording lives on the Options tab')
                        ->description('Each screen’s heading is the area’s own name, and the line underneath is its'
                            .' description — the same wording people read on step '
                            .RegistrationFlow::number(RegistrationFlow::DETAIL_STEP)
                            .'. Change it once under Options and both update together, with nothing anybody has already'
                            .' chosen lost along the way.')
                        ->icon(Heroicon::OutlinedListBullet)
                        ->color('warning')
                        ->columnSpanFull(),
                ]),

        ]);
    }

    /**
     * One read-only entry per follow-up screen.
     *
     * Read-only on purpose. These are the same rows the choice cards on step
     * five are built from, and the Options tab is where the safeguards live —
     * how many families chose a thing, and archive rather than delete. A second
     * way in without those is how an option thirty families picked disappears.
     *
     * @return array<int, Placeholder>
     */
    private static function followUpScreens(FormModel $form): array
    {
        $areas = $form->optionsIn('support_areas')->with('children')->orderBy('position')->get();

        if ($areas->isEmpty()) {
            return [
                Placeholder::make('no_follow_up_screens')
                    ->label('')
                    ->content('No areas of family life yet, so nobody sees a follow-up screen.')
                    ->columnSpanFull(),
            ];
        }

        return $areas
            ->map(fn (FormOption $area) => Placeholder::make('follow_up_'.$area->slug)
                ->label($area->label)
                ->content(static::followUpSummary($area)))
            ->all();
    }

    private static function followUpSummary(FormOption $area): string
    {
        $live = $area->children->reject(fn (FormOption $item) => $item->isArchived())->count();

        $parts = array_filter([
            $area->description,
            $live.' '.str('statement')->plural($live),
            $area->isArchived() ? 'Archived — nobody is offered this' : null,
        ]);

        return implode(' · ', $parts);
    }
}
