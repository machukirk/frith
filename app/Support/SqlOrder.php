<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * "Order by this list, in this order."
 *
 * A CASE expression rather than MySQL's FIELD(), because the test suite runs
 * on SQLite and does not have it — a difference that has now cost two
 * debugging sessions, so it lives in one place.
 */
class SqlOrder
{
    /**
     * @param  array<int, string>  $values  in the order they should come out
     */
    public static function byList(Builder $query, string $column, array $values): Builder
    {
        if ($values === []) {
            return $query;
        }

        $wrapped = $query->getQuery()->getGrammar()->wrap($column);

        $cases = [];

        foreach (array_keys($values) as $rank) {
            $cases[] = "WHEN ? THEN {$rank}";
        }

        return $query->orderByRaw(
            'CASE '.$wrapped.' '.implode(' ', $cases).' ELSE '.count($values).' END',
            array_values($values),
        );
    }
}
