<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();

            // The key the controller knows this screen by. Not editable.
            $table->string('key');

            $table->string('heading');
            $table->text('standfirst')->nullable();

            // Shows the violet "only used for matching" note. Editable, because
            // whether a screen is private is a content decision as much as a
            // technical one.
            $table->boolean('is_private')->default(false);

            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['form_id', 'key']);
            $table->index(['form_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_steps');
    }
};
