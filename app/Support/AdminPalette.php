<?php

namespace App\Support;

/**
 * Colour ramps for the admin panel.
 *
 * Filament picks shade 600 for a primary button and generates the rest of a
 * ramp from whatever hex you hand it. Giving it `#274B44` produced a bright
 * teal button, because a colour that dark lands near the bottom of a generated
 * ramp rather than in the middle. So the shades are supplied outright, lined up
 * so that 600 is the brand step and 700 is its hover.
 *
 * Only the values marked below come from Brand Guidelines §03. The steps
 * between them are interpolated for this panel and are not brand tokens — the
 * public site uses the real ramps in resources/scss/abstracts/_tokens.scss.
 */
class AdminPalette
{
    /** Eucalyptus. 600 is the brand green #274B44. */
    public const PRIMARY = [
        50 => '#F3F8F7',   // guidelines
        100 => '#D6DFDD',  // guidelines
        200 => '#B8C7C4',  // guidelines
        300 => '#9BAFAA',  // guidelines
        400 => '#627E78',  // guidelines (euc 500)
        500 => '#46665F',  // guidelines (euc 600)
        600 => '#274B44',  // guidelines — brand
        700 => '#153731',  // guidelines
        800 => '#03221D',  // guidelines
        900 => '#03221D',
        950 => '#03221D',
    ];

    /** Coral. 600 is Coral 700 #992F17 — errors never use Coral 400 (§03). */
    public const DANGER = [
        50 => '#FFF4F1',
        100 => '#FFD4C9',
        200 => '#FFB19E',
        300 => '#F58F78',
        400 => '#CD573C',
        500 => '#B3432A',
        600 => '#992F17',  // guidelines
        700 => '#7E1A00',  // guidelines
        800 => '#5F0F00',  // guidelines
        900 => '#5F0F00',
        950 => '#5F0F00',
    ];

    /** Teal. 600 is Teal 700 #006964, the text-safe step. */
    public const SUCCESS = [
        50 => '#F0F9F8',
        100 => '#DFF1EF',  // guidelines
        200 => '#B8E0DC',
        300 => '#7FC9C3',
        400 => '#4FAFA8',
        500 => '#1E8A84',  // guidelines
        600 => '#006964',  // guidelines
        700 => '#005550',
        800 => '#00413D',
        900 => '#002E2B',
        950 => '#002E2B',
    ];

    /** Mustard. 600 is Mustard 700 #785300, the only step safe for text. */
    public const WARNING = [
        50 => '#FEF8EE',
        100 => '#FCEACF',  // guidelines
        200 => '#F7D9A8',
        300 => '#EFC170',
        400 => '#D8A038',  // guidelines
        500 => '#A87A1C',
        600 => '#785300',  // guidelines
        700 => '#614300',
        800 => '#4A3300',
        900 => '#332300',
        950 => '#332300',
    ];

    /** Violet. 600 is Violet 700 #5D4C97. */
    public const INFO = [
        50 => '#F6F4FF',
        100 => '#EDEAFF',  // guidelines
        200 => '#DAD4FA',
        300 => '#B9AEE8',
        400 => '#8573C7',  // guidelines
        500 => '#6F5CB4',
        600 => '#5D4C97',  // guidelines
        700 => '#4A3C79',
        800 => '#372C5B',
        900 => '#241D3D',
        950 => '#241D3D',
    ];
}
