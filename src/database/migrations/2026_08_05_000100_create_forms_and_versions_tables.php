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
            $table->ulid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('status', 16)->default('draft');
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->unsignedBigInteger('published_version_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'status', 'updated_at']);
            $table->index(['tenant_id', 'created_by', 'updated_at']);
        });

        Schema::create('form_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('schema_json');
            $table->char('schema_checksum', 64)->index();
            $table->string('change_summary')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['form_id', 'version_number']);
            $table->index(['form_id', 'created_at']);
        });

        Schema::table('forms', function (Blueprint $table) {
            $table->foreign('current_version_id')->references('id')->on('form_versions')->restrictOnDelete();
            $table->foreign('published_version_id')->references('id')->on('form_versions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
            $table->dropForeign(['published_version_id']);
        });
        Schema::dropIfExists('form_versions');
        Schema::dropIfExists('forms');
    }
};
