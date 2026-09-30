<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * The editing form for a page.
 *
 * One tab per section, in the order they appear going down the page, so finding
 * the words you want to change is a matter of scrolling the page and picking
 * the tab with the same name. Helper text says where a field shows up rather
 * than restating its label.
 *
 * Structure is not editable — which sections a page has and in what order is
 * code, because a section is a designed thing rather than a free-form block.
 * What is editable is every word inside them.
 */
class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Callout::make('Everything here is live')
                ->description('Visitors see the new wording as soon as you save. The section each tab refers to is named after what you see on the page itself.')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('info')
                ->columnSpanFull(),

            Tabs::make()->columnSpanFull()->tabs([

                Tabs\Tab::make('Hero')->icon(Heroicon::OutlinedSparkles)->schema([
                    Section::make()->schema([
                        TextInput::make('content.hero.eyebrow')
                            ->label('Small line above the heading')
                            ->helperText('Shown in capitals, in coral. A few words.')
                            ->maxLength(80),

                        Repeater::make('content.hero.headline')
                            ->label('Main heading')
                            ->helperText('One line per row. The coral full stop at the end is added for you.')
                            ->simple(TextInput::make('line')->required()->maxLength(60))
                            ->reorderable()
                            ->maxItems(3),

                        Textarea::make('content.hero.standfirst')
                            ->label('Paragraph underneath')
                            ->rows(3)
                            ->maxLength(320),

                        TextInput::make('content.hero.primary_cta')
                            ->label('Button')
                            ->maxLength(30),

                        TextInput::make('content.hero.secondary_cta')
                            ->label('Link beside the button')
                            ->maxLength(40),

                        Textarea::make('content.hero.note')
                            ->label('Small print under the button')
                            ->rows(2)
                            ->maxLength(200),

                        Textarea::make('content.hero.image_alt')
                            ->label('Description of the photograph')
                            ->helperText('Read aloud to anybody who cannot see it. Describe what is happening, not that it is a photo.')
                            ->rows(2)
                            ->maxLength(200)
                            ->columnSpanFull(),
                    ])->columns(2),
                ]),

                Tabs\Tab::make('The three reasons')->icon(Heroicon::OutlinedSquares2x2)->schema([
                    Repeater::make('content.pillars')
                        ->label('')
                        ->helperText('The green band under the hero.')
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                        ->collapsible()
                        ->collapsed()
                        ->reorderable()
                        ->schema([
                            TextInput::make('title')->label('Heading')->required()->maxLength(60),
                            Textarea::make('body')->label('Paragraph')->rows(3)->maxLength(240),
                        ]),
                ]),

                Tabs\Tab::make('How it works')->icon(Heroicon::OutlinedListBullet)->schema([
                    Section::make()->schema([
                        TextInput::make('content.how_it_works.eyebrow')->label('Small line above')->maxLength(60),
                        TextInput::make('content.how_it_works.title')->label('Heading')->maxLength(120),
                        Textarea::make('content.how_it_works.standfirst')->label('Paragraph underneath')->rows(2)->maxLength(300)->columnSpanFull(),
                    ])->columns(2),

                    Repeater::make('content.how_it_works.steps')
                        ->label('The three steps')
                        ->helperText('Numbered automatically, in this order.')
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                        ->collapsible()
                        ->collapsed()
                        ->reorderable()
                        ->schema([
                            TextInput::make('title')->label('Heading')->required()->maxLength(80),
                            Textarea::make('body')->label('Paragraph')->rows(3)->maxLength(260),
                        ]),

                    TextInput::make('content.how_it_works.note')
                        ->label('Line underneath the three')
                        ->maxLength(140),
                ]),

                Tabs\Tab::make('Journeys')->icon(Heroicon::OutlinedMap)->schema([
                    Section::make()->schema([
                        TextInput::make('content.journeys.eyebrow')->label('Small line above')->maxLength(60),
                        TextInput::make('content.journeys.title')->label('Heading')->maxLength(120),
                        Textarea::make('content.journeys.standfirst')->label('Paragraph underneath')->rows(2)->maxLength(300)->columnSpanFull(),
                    ])->columns(2),

                    Repeater::make('content.journeys.items')
                        ->label('The Journeys shown')
                        ->helperText('The count is what visitors see on the card. It is illustrative until Journeys are live.')
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                        ->collapsible()
                        ->collapsed()
                        ->reorderable()
                        ->schema([
                            TextInput::make('title')->label('Journey')->required()->maxLength(80),
                            TextInput::make('families')->label('Families')->numeric()->minValue(0)->maxValue(9999),
                        ])->columns(2),

                    Section::make()->schema([
                        TextInput::make('content.journeys.note')->label('Line underneath')->maxLength(160),
                        TextInput::make('content.journeys.link')->label('Link at the end')->maxLength(60),
                    ])->columns(2),
                ]),

                Tabs\Tab::make('What others see')->icon(Heroicon::OutlinedEye)->schema([
                    Section::make()->schema([
                        TextInput::make('content.visibility.eyebrow')->label('Small line above')->maxLength(60),
                        TextInput::make('content.visibility.title')->label('Heading')->maxLength(140),
                        Textarea::make('content.visibility.standfirst')->label('Paragraph underneath')->rows(2)->maxLength(300)->columnSpanFull(),
                    ])->columns(2),

                    ...collect([
                        'shown' => 'The left panel — what other families see',
                        'hidden' => 'The right panel — what is never shown',
                    ])->map(fn (string $heading, string $key) => Section::make($heading)
                        ->collapsible()
                        ->schema([
                            TextInput::make("content.visibility.{$key}.title")->label('Panel heading')->maxLength(60),
                            TextInput::make("content.visibility.{$key}.standfirst")->label('Line underneath')->maxLength(120),
                            Repeater::make("content.visibility.{$key}.items")
                                ->label('Rows')
                                ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                                ->collapsible()
                                ->collapsed()
                                ->reorderable()
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('label')->label('Row')->required()->maxLength(80),
                                    TextInput::make('detail')->label('Smaller line underneath')->maxLength(140),
                                ]),
                        ])->columns(2))->values()->all(),
                ]),

                Tabs\Tab::make('Call to action')->icon(Heroicon::OutlinedMegaphone)->schema([
                    Section::make()->schema([
                        TextInput::make('content.cta.title')->label('Heading')->maxLength(100),
                        TextInput::make('content.cta.button')->label('Button')->maxLength(30),
                        Textarea::make('content.cta.body')->label('Paragraph')->rows(3)->maxLength(280)->columnSpanFull(),
                    ])->columns(2),
                ]),

                Tabs\Tab::make('Questions')->icon(Heroicon::OutlinedQuestionMarkCircle)->schema([
                    Section::make()->schema([
                        TextInput::make('content.faq.eyebrow')->label('Small line above')->maxLength(60),
                        TextInput::make('content.faq.title')->label('Heading')->maxLength(120),
                    ])->columns(2),

                    Repeater::make('content.faq.items')
                        ->label('Questions and answers')
                        ->helperText('The first one is open when the page loads.')
                        ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                        ->collapsible()
                        ->collapsed()
                        ->reorderable()
                        ->schema([
                            TextInput::make('question')->label('Question')->required()->maxLength(140),
                            Textarea::make('answer')->label('Answer')->rows(4)->maxLength(900),
                        ]),

                    TextInput::make('content.faq.footer')
                        ->label('Line at the end')
                        ->helperText('Write :email where the address should go and it is turned into a link.')
                        ->maxLength(200),
                ]),

                Tabs\Tab::make('Search & sharing')->icon(Heroicon::OutlinedGlobeAlt)->schema([
                    Callout::make('What a search result and a shared link look like')
                        ->description('Used by Google and by anything that unfurls a link — WhatsApp, Slack, a text message. Not shown on the page itself.')
                        ->icon(Heroicon::OutlinedInformationCircle)
                        ->columnSpanFull(),

                    Section::make()->schema([
                        TextInput::make('content.meta.title')
                            ->label('Title')
                            ->helperText('Around 60 characters before Google trims it.')
                            ->maxLength(70),

                        Textarea::make('content.meta.description')
                            ->label('Description')
                            ->helperText('Around 155 characters before it is trimmed.')
                            ->rows(3)
                            ->maxLength(180),
                    ]),
                ]),
            ]),
        ]);
    }
}
