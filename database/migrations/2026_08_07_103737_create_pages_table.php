<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();

            // The route or screen this content drives. One row per page today;
            // the same table serves app screens when there are app screens.
            $table->string('slug')->unique();
            $table->string('name');

            // Structured content rather than a blob of HTML, so the same record
            // can render a Blade view now and feed a JSON API to the apps later
            // without anyone re-authoring it.
            $table->json('content');

            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
