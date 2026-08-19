<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_step_id')->constrained()->cascadeOnDelete();

            // Maps to the request key the controller validates. Not editable:
            // renaming it would detach the field from its own validation.
            $table->string('key');

            $table->string('label');
            $table->text('help')->nullable();
            $table->string('placeholder')->nullable();

            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['form_step_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
