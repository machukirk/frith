<?php

namespace App\Support;

/**
 * Small numbers written out, and lists joined the way a person writes them.
 *
 * The designs say "two children (aged 6, 9)" and "born March 2019 and
 * September 2016" — words for the count, digits for the ages. That is not an
 * inconsistency: the count is prose and the ages are data, and a parent
 * scanning a card reads the ages and reads past the count.
 *
 * Ten and up stay as digits, because "seventeen families are in this journey"
 * is harder to scan than "17".
 */
class Words
{
    private const SMALL = [
        1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
        6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine',
    ];

    public static function count(int $number): string
    {
        return self::SMALL[$number] ?? (string) $number;
    }

    /**
     * "A", "A and B", "A, B and C". No Oxford comma: this is British English.
     *
     * @param  array<int, string>  $items
     */
    public static function list(array $items, string $conjunction = 'and'): string
    {
        $items = array_values(array_filter($items, fn ($item) => filled($item)));

        if (count($items) <= 1) {
            return $items[0] ?? '';
        }

        $last = array_pop($items);

        return implode(', ', $items).' '.$conjunction.' '.$last;
    }
}
