<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();

            // Slugs from config/frith-taxonomy.php. Stored rather than the
            // labels, so wording can be improved without rewriting history or
            // detaching anybody from what they actually selected.
            $table->string('category');
            $table->string('item');

            $table->timestamps();

            $table->unique(['registration_id', 'category', 'item']);
            // The query matching is built on: everyone who selected this.
            $table->index(['category', 'item']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_experiences');
    }
};
