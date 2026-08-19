<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();

            // Which list this belongs to: 'support_areas', 'family_structures'.
            $table->string('group');

            // Self-referencing: the eight support areas are parents, and the
            // sixty-three detailed statements hang underneath them.
            $table->foreignId('parent_id')->nullable()->constrained('form_options')->cascadeOnDelete();

            // What the database stores against a family. Never editable once
            // it exists — renaming it detaches everyone who chose it.
            $table->string('slug');

            $table->string('label', 500);
            $table->string('description')->nullable();

            $table->unsignedSmallInteger('position')->default(0);

            // Archived options stop being offered and stop being accepted, but
            // still resolve to their label so existing answers keep meaning
            // something. This is why there is no delete for an option in use.
            $table->timestamp('archived_at')->nullable();

            $table->timestamps();

            $table->unique(['form_id', 'group', 'parent_id', 'slug'], 'form_options_unique_slug');
            $table->index(['form_id', 'group', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_options');
    }
};
