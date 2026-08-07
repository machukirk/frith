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

                Tabs\Tab::make('Sign-up form')->schema([
                    Callout::make('The line under the form is not editable here')
                        ->description('What people agree to when they sign up is stored, word for word and with a version number, against every signup as the record of consent. Changing it needs a developer to version the change at the same time.')
                        ->icon(Heroicon::OutlinedLockClosed)
                        ->color('warning')
                        ->columnSpanFull(),

                    Section::make()->schema([
                        TextInput::make('content.form.label')->label('Field label')->maxLength(60),
                        TextInput::make('content.form.placeholder')->label('Greyed-out example text')->maxLength(60),
                        TextInput::make('content.form.button')->label('Button')->maxLength(60),
                    ])->columns(3),

                    Section::make('After someone signs up')
                        ->description('Shown on the page once they have submitted the form.')
                        ->schema([
                            TextInput::make('content.success.heading')->label('Heading')->maxLength(80),
                            Textarea::make('content.success.body')->label('Message')->rows(3),
                            Textarea::make('content.success.footnote')->label('If nothing arrives')->rows(2),
                        ]),

                    Section::make('After they confirm their email')
                        ->description('The page they land on from the link in the email.')
                        ->schema([
                            TextInput::make('content.confirmed.heading')->label('Heading')->maxLength(80),
                            Textarea::make('content.confirmed.body')->label('Message')->rows(3),
                        ]),

                    Section::make('If someone unsubscribes')
                        ->collapsed()
                        ->schema([
                            TextInput::make('content.unsubscribed.heading')->label('Heading')->maxLength(80),
                            Textarea::make('content.unsubscribed.body')->label('Message')->rows(3),
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
