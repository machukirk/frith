<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The consent line now sits directly under the email field and explains what
 * happens to the address, so the field's own help text was saying the same
 * thing twice in a row.
 *
 * Applied only where the wording is still the original, so an editor's version
 * would have stood.
 */
return new class extends Migration
{
    private const WAS = 'So we can tell you the moment Frith opens. One email, and you can leave any time.';

    private const NOW = 'Where we will write to you.';

    public function up(): void
    {
        DB::table('form_fields')->where('key', 'email')->where('help', self::WAS)->update(['help' => self::NOW]);
    }

    public function down(): void
    {
        DB::table('form_fields')->where('key', 'email')->where('help', self::NOW)->update(['help' => self::WAS]);
    }
};
