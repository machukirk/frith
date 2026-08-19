<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();

            // Month and year only, never a full date of birth. Enough to keep
            // their age current and match on stage, and no more than that
            // about somebody else's child.
            $table->unsignedTinyInteger('birth_month');
            $table->unsignedSmallInteger('birth_year');

            // Preserves the order they were added in, so the form reads back
            // the way the family entered it.
            $table->unsignedTinyInteger('position')->default(0);

            $table->timestamps();

            $table->index(['registration_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_children');
    }
};
