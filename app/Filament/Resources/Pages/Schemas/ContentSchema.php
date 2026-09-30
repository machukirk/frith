<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Illuminate\Support\Str;

/**
 * Builds a page's editing form from the page's own content.
 *
 * One hand-written form per page would mean ten of them, each free to drift
 * from the file it edits — and a form built for one page's shape, opened on
 * another, does not merely look wrong: saving it replaces that page's content
 * with its own empty fields. That is how the first version of this lost the
 * Terms page the moment anybody pressed Save on it.
 *
 * So the form is derived from the content instead. A top-level key becomes a
 * tab, a nested map becomes a section, a list of maps becomes a repeater, and
 * the field type is chosen from the value. A page added to config/content gets
 * an editor for nothing, and the editor cannot describe a shape the page does
 * not have.
 *
 * Structure is still code: an editor can reword anything and add or remove
 * items in a list, but cannot invent a new key, because there is no control
 * that makes one.
 */
class ContentSchema
{
    /** Keys whose value is prose however short it happens to be today. */
    private const PROSE_KEYS = [
        'answer', 'blurb', 'body', 'description', 'detail', 'intro', 'lead',
        'message', 'note', 'paragraph', 'reassurance', 'standfirst', 'summary',
        'empty', 'safeguarding', 'help',
    ];

    /** Beyond this, a single line is no longer comfortable to edit. */
    private const PROSE_LENGTH = 90;

    /** Tabs that belong at the end whatever order the file puts them in. */
    private const TRAILING = ['meta'];

    private const LABELS = [
        'meta' => 'Search & sharing',
        'cta' => 'Call to action',
        'faq' => 'Questions',
        'head' => 'Top of the page',
        'page_head' => 'Top of the page',
    ];

    /**
     * @param  array<string, mixed>  $content
     */
    public static function tabs(array $content): Tabs
    {
        $keys = collect(array_keys($content))
            ->sortBy(fn (string $key) => in_array($key, self::TRAILING, true) ? 1 : 0)
            ->values();

        return Tabs::make()->columnSpanFull()->tabs(
            $keys->map(fn (string $key) => Tabs\Tab::make(self::label($key))
                ->schema(self::fields("content.{$key}", $content[$key])))
                ->all(),
        );
    }

    /**
     * The controls for one value, whatever shape it is.
     *
     * @return array<int, mixed>
     */
    private static function fields(string $path, mixed $value): array
    {
        // A plain value at the top of a tab still needs a control.
        if (! is_array($value)) {
            return [self::control($path, self::leaf($path), $value)];
        }

        if (array_is_list($value)) {
            return [self::repeater($path, self::leaf($path), $value)];
        }

        $simple = [];
        $complex = [];

        foreach ($value as $key => $child) {
            $childPath = "{$path}.{$key}";

            if (! is_array($child)) {
                $simple[] = self::control($childPath, $key, $child);

                continue;
            }

            $complex[] = array_is_list($child)
                ? self::repeater($childPath, $key, $child)
                : Section::make(self::label($key))
                    ->collapsible()
                    ->collapsed()
                    ->schema(self::fields($childPath, $child));
        }

        return array_merge(
            $simple === [] ? [] : [Section::make()->schema($simple)->columns(2)],
            $complex,
        );
    }

    /** A list: of strings, or of maps — which need not all have the same keys. */
    private static function repeater(string $path, string $key, array $value): Repeater
    {
        $first = $value[0] ?? null;

        $repeater = Repeater::make($path)
            ->label(self::label($key))
            ->reorderable()
            ->collapsible()
            ->collapsed();

        // A list of plain strings — one box per row, no inner labels. The
        // inner field takes a bare name, not a path: a simple repeater stores
        // the scalar itself, and a dotted name makes it write to a key that is
        // not there, so every row comes back null.
        if (! is_array($first)) {
            return $repeater
                ->simple(self::control('item', $key, $first)->label(''))
                ->collapsed(false);
        }

        // Every key that appears anywhere in the list, not just in its first
        // item. The policies have sections that carry paragraphs, sections that
        // carry a bullet list, and sections that carry both — reading only the
        // first item gives the later ones no control, and saving then writes an
        // empty array over whatever they held.
        $shape = self::shapeOf($value);

        $label = collect(['title', 'question', 'label', 'term', 'heading', 'what', 'name'])
            ->first(fn (string $candidate) => array_key_exists($candidate, $shape));

        return $repeater
            ->itemLabel(fn (array $state): ?string => $label ? ($state[$label] ?? null) : null)
            ->schema(collect($shape)
                ->map(function (mixed $child, string $childKey) {
                    if (is_array($child)) {
                        return array_is_list($child)
                            ? self::repeater($childKey, $childKey, $child)
                            : Section::make(self::label($childKey))
                                ->collapsible()
                                ->schema(self::fields($childKey, $child));
                    }

                    return self::control($childKey, $childKey, $child);
                })
                ->values()
                ->all());
    }

    /**
     * The union of every key in a list of maps, with a sample value for each
     * so the control can be chosen from it.
     *
     * @param  array<int, mixed>  $items
     * @return array<string, mixed>
     */
    private static function shapeOf(array $items): array
    {
        $shape = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            foreach ($item as $key => $value) {
                // A later, fuller example beats an earlier empty one: a list
                // that is empty in the first item says nothing about its type.
                $known = $shape[$key] ?? null;

                if (! array_key_exists($key, $shape) || ($known === null || $known === []) && $value !== null) {
                    $shape[$key] = $value;
                }
            }
        }

        return $shape;
    }

    /** One editable value: a line, or a box if it is prose. */
    private static function control(string $path, string $key, mixed $value): TextInput|Textarea
    {
        if (self::isProse($key, $value)) {
            return Textarea::make($path)
                ->label(self::label($key))
                ->rows(self::rows($value))
                ->autosize();
        }

        return TextInput::make($path)
            ->label(self::label($key))
            ->maxLength(255);
    }

    private static function isProse(string $key, mixed $value): bool
    {
        return in_array($key, self::PROSE_KEYS, true)
            || Str::length((string) $value) > self::PROSE_LENGTH;
    }

    private static function rows(mixed $value): int
    {
        return match (true) {
            Str::length((string) $value) > 600 => 8,
            Str::length((string) $value) > 260 => 5,
            default => 3,
        };
    }

    /** The last segment of a dot path. */
    private static function leaf(string $path): string
    {
        return Str::afterLast($path, '.');
    }

    private static function label(string $key): string
    {
        return self::LABELS[$key] ?? Str::of($key)->replace('_', ' ')->ucfirst()->toString();
    }
}
