<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_version_id')->constrained()->restrictOnDelete();
            $table->json('data_json');
            $table->text('search_text');
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->index(['form_id', 'submitted_at']);
            $table->index(['form_id', 'id']);
            $table->index(['form_version_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
