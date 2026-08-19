<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();

            // Matched to a controller in code. The structure of a form is
            // behaviour — saving on every step, the child repeater, section two
            // appearing only for chosen areas — so it lives in PHP. What lives
            // here is everything an editor should be able to change.
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('description')->nullable();

            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
