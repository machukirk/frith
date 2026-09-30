<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Nullable, and expected to stay null for most people. Registration
            // never asks for one — the emailed link is the way in, because a
            // parent registering at 1am on a phone will not remember a password
            // a fortnight later. A password is something you may set later.
            $table->string('password')->nullable()->after('email');

            // The emailed link. Stored hashed, because anybody who can read
            // this column could otherwise log in as anybody in it.
            $table->string('login_token', 64)->nullable()->after('password');
            $table->timestamp('login_token_expires_at')->nullable()->after('login_token');

            $table->rememberToken();

            $table->index('login_token');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['login_token']);
            $table->dropColumn(['password', 'login_token', 'login_token_expires_at', 'remember_token']);
        });
    }
};
