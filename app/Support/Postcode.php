<?php

namespace App\Support;

/**
 * Turning the half-postcode we hold into something a parent recognises.
 *
 * A family's location is the only part of their profile that is deliberately
 * imprecise. We never ask for more than the outcode, and we never show even
 * that if we can show a town instead — "Leigh-on-Sea area" tells another
 * family what they need to know about whether a coffee is realistic, and
 * narrows a household down no further than the outcode already does.
 *
 * The table behind this is incomplete on purpose. See config/frith-postcodes.
 */
class Postcode
{
    /** The post town for an outcode, or null if we are not sure. */
    public static function town(?string $outcode): ?string
    {
        $key = mb_strtoupper(preg_replace('/\s+/', '', trim((string) $outcode)));

        return $key === '' ? null : (config('frith-postcodes.'.$key) ?: null);
    }

    /**
     * What goes on a card: the town if we know it, the outcode if we do not.
     *
     * Always "X area", never a bare place name. The word is load-bearing: it
     * says the location is approximate, which is the truthful thing to say
     * about a town inferred from half a postcode.
     */
    public static function label(?string $outcode): string
    {
        $outcode = trim((string) $outcode);

        if ($outcode === '') {
            return '';
        }

        return (self::town($outcode) ?? mb_strtoupper($outcode)).' area';
    }
}
