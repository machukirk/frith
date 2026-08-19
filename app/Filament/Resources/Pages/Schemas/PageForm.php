<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * The editing form for the coming soon page.
 *
 * The tabs follow the order things appear on the page, so finding the words you
 * want to change is a matter of scrolling the page and picking the matching tab.
 * Helper text says where each field shows up rather than restating the label.
 */
class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->columnSpanFull()->tabs([

                Tabs\Tab::make('Top of the page')->schema([
                    Section::make()->schema([
                        TextInput::make('content.status_badge')
                            ->label('Launch badge')
                            ->helperText('The small pill in the top right corner. Leave empty to hide it.')
                            ->maxLength(60),

                        TextInput::make('content.eyebrow')
                            ->label('Small heading above the title')
                            ->helperText('Shown in capitals. Keep it to a few words.')
                            ->maxLength(80),

                        Repeater::make('content.headline')
                            ->label('Main heading')
                            ->helperText('One line per row. The full stop at the end is added automatically.')
                            ->simple(TextInput::make('line')->required()->maxLength(60))
                            ->minItems(1)
                            ->maxItems(3)
                            ->defaultItems(2),

                        Textarea::make('content.standfirst')
                            ->label('Paragraph under the heading')
                            ->rows(3)
                            ->maxLength(300),
                    ]),

                    Section::make('Photograph')->schema([
                        FileUpload::make('content.hero_image')
                            ->label('Hero photograph')
                            ->image()
                            ->imageEditor()
                            ->directory('hero')
                            ->disk('public')
                            ->maxSize(4096)
                            ->helperText('Landscape works best, around 1600 pixels wide. Leave empty to use the original artwork.'),

                        Textarea::make('content.hero_image_alt')
                            ->label('Description of the photograph')
                            ->rows(2)
                            ->required()
                            ->maxLength(300)
                            ->helperText('Read aloud to people using a screen reader, and shown if the image fails to load. Describe what is happening, not that it is a photo.'),
                    ]),
                ]),

                Tabs\Tab::make('Call to action')->schema([
                    Callout::make('This button opens the registration')
                        ->description('It takes people to the five-screen Founders form. The wording on that form is edited under Forms, not here.')
                        ->icon(Heroicon::OutlinedCursorArrowRays)
                        ->color('info')
                        ->columnSpanFull(),

                    Section::make()->schema([
                        TextInput::make('content.cta.button')
                            ->label('Button')
                            ->maxLength(40)
                            ->helperText('Keep it short — it should not wrap on a phone.'),

                        Textarea::make('content.cta.note')
                            ->label('Line underneath the button')
                            ->rows(2)
                            ->maxLength(300)
                            ->helperText('Where the offer goes: what a Founder gets, and how long it takes.'),
                    ]),
                ]),

                Tabs\Tab::make('Three cards')->schema([
                    Repeater::make('content.cards')
                        ->label('Cards')
                        ->helperText('The row of white cards under the heading. Three fits the layout best.')
                        ->schema([
                            Select::make('icon')
                                ->label('Icon')
                                ->options([
                                    'people' => 'Two people',
                                    'leaf' => 'Leaf',
                                    'heart' => 'Heart',
                                ])
                                ->required(),
                            TextInput::make('heading')->label('Heading')->required()->maxLength(60),
                            Textarea::make('body')->label('Text')->rows(3)->required()->maxLength(300),
                        ])
                        ->columns(1)
                        ->itemLabel(fn (array $state): ?string => $state['heading'] ?? null)
                        ->collapsible()
                        ->reorderable()
                        ->maxItems(4),
                ]),

                Tabs\Tab::make('Green panel')->schema([
                    Section::make()->schema([
                        TextInput::make('content.status.heading')->label('Heading')->maxLength(60),
                        Textarea::make('content.status.standfirst')->label('Line under the heading')->rows(2),
                        TextInput::make('content.status.tagline')
                            ->label('Tagline under the logo')
                            ->maxLength(40)
                            ->helperText('Always title case, always with the full stop.'),
                    ]),

                    Repeater::make('content.status.points')
                        ->label('Bullet points')
                        ->schema([
                            TextInput::make('lead')
                                ->label('Bold part')
                                ->required()
                                ->maxLength(120),
                            Textarea::make('rest')
                                ->label('Rest of the sentence')
                                ->rows(2)
                                ->maxLength(300),
                        ])
                        ->itemLabel(fn (array $state): ?string => $state['lead'] ?? null)
                        ->collapsible()
                        ->reorderable()
                        ->maxItems(6),
                ]),

                Tabs\Tab::make('Purple note')->schema([
                    Textarea::make('content.note')
                        ->label('Note at the bottom of the page')
                        ->rows(4)
                        ->maxLength(600),
                ]),

                Tabs\Tab::make('Search & sharing')->schema([
                    Callout::make('Not visible on the page itself')
                        ->description('What Google shows in its results, and what appears when someone shares a link to the page on WhatsApp or Facebook.')
                        ->icon(Heroicon::OutlinedMagnifyingGlass)
                        ->color('info')
                        ->columnSpanFull(),

                    Section::make()->schema([
                        TextInput::make('content.meta.title')
                            ->label('Page title')
                            ->maxLength(60)
                            ->helperText('Around 60 characters. Longer gets cut off.'),
                        Textarea::make('content.meta.description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(160)
                            ->helperText('Around 155 characters.'),
                    ]),
                ]),

            ]),
        ]);
    }
}
