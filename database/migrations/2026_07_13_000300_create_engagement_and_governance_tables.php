<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'book_id']);
        });

        Schema::create('reading_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('last_page')->default(1);
            $table->unsignedBigInteger('duration_seconds')->default(0);
            $table->timestamp('last_read_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['user_id', 'book_id']);
        });

        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('page');
            $table->string('label')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'book_id', 'page']);
        });

        Schema::create('book_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_hash', 64)->nullable()->index();
            $table->string('session_hash', 64)->nullable()->index();
            $table->string('device_type', 30)->nullable()->index();
            $table->string('browser', 80)->nullable();
            $table->string('referrer', 2048)->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedInteger('last_page')->nullable();
            $table->timestamp('viewed_at')->index();
            $table->timestamps();
            $table->index(['book_id', 'viewed_at']);
        });

        Schema::create('book_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_hash', 64)->nullable()->index();
            $table->string('device_type', 30)->nullable();
            $table->timestamp('downloaded_at')->index();
            $table->timestamps();
            $table->index(['book_id', 'downloaded_at']);
        });

        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('query')->index();
            $table->string('normalized_query')->index();
            $table->unsignedInteger('result_count')->default(0)->index();
            $table->json('filters')->nullable();
            $table->string('session_hash', 64)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('book_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30)->index();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->string('status', 20)->default('new')->index();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('image')->nullable();
            $table->string('link')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->nullableMorphs('target');
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['action', 'created_at']);
        });

        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30)->index();
            $table->string('disk');
            $table->string('path')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general')->index();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->boolean('is_public')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('backups');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('feedback');
        Schema::dropIfExists('search_logs');
        Schema::dropIfExists('book_downloads');
        Schema::dropIfExists('book_views');
        Schema::dropIfExists('bookmarks');
        Schema::dropIfExists('reading_histories');
        Schema::dropIfExists('favorites');
    }
};
