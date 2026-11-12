<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records which family closed a connection, when nobody ever asked.
 *
 * "Not for us" on a profile writes a closed row so that family is not
 * suggested again. With only requester_id, addressee_id and a status of
 * declined, that row is byte-identical to "you asked them and they said no",
 * for two families where no ask was ever made. The withdraw() method a few
 * lines above deletes its row rather than keep one, on exactly this ground:
 * a row that would read as somebody's no is worse than no row.
 *
 * So the row stays, because something has to remember the two are closed, and
 * it says who closed it. Null means what it has always meant: the addressee
 * answered an ask that really happened.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connections', function (Blueprint $table) {
            $table->foreignId('closed_by')
                ->nullable()
                ->after('status')
                ->constrained('registrations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('connections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closed_by');
        });
    }
};
