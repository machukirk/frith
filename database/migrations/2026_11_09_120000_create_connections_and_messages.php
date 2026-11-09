<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Connecting, talking, and the two ways out of both.
 *
 * Nothing here happens to a family without their say-so. A connection is a
 * request until the other family answers it, a message cannot exist without
 * an accepted connection behind it, and a block is one-sided and silent by
 * design: the guidelines are explicit that nobody is told they were blocked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('registrations')->cascadeOnDelete();
            $table->foreignId('addressee_id')->constrained('registrations')->cascadeOnDelete();

            // pending until it is answered, then accepted or declined. A
            // decline is kept rather than deleted so the same family is not
            // offered again and again.
            $table->string('status')->default('pending');

            // Where they found each other. The designs say "via Waiting for
            // an autism assessment", and it is the most useful thing on the
            // request.
            $table->foreignId('journey_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            // One connection between any two families, whoever asked first.
            $table->unique(['requester_id', 'addressee_id']);
            $table->index(['addressee_id', 'status']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('registrations')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['connection_id', 'id']);
        });

        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blocker_id')->constrained('registrations')->cascadeOnDelete();
            $table->foreignId('blocked_id')->constrained('registrations')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['blocker_id', 'blocked_id']);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('registrations')->cascadeOnDelete();

            // Kept even if the reported family deletes their account: a
            // report is a safeguarding record, and it has to outlive them.
            $table->foreignId('reported_id')->nullable()->constrained('registrations')->nullOnDelete();
            $table->string('reported_name')->nullable();

            $table->string('reason');
            $table->text('note')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index('handled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('blocks');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('connections');
    }
};
