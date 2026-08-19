<?php

namespace App\Filament\Resources\Forms\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * One screen per section, in the order a visitor meets them.
 *
 * The key of each screen and field is shown but not editable — it is what ties
 * the wording to the code that validates and saves it.
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
        ]);
    }
}
