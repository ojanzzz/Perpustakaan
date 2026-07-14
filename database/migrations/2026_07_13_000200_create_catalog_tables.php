<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publishers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('biography')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('visibility', 30)->default('public')->index();
            $table->string('password_hash')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->longText('description')->nullable();
            $table->string('editor')->nullable();
            $table->foreignId('publisher_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('language_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('publication_year')->nullable()->index();
            $table->date('publication_date')->nullable();
            $table->string('isbn', 32)->nullable()->index();
            $table->string('document_number')->nullable()->index();
            $table->string('publication_type')->nullable()->index();
            $table->unsignedInteger('page_count')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('original_file')->nullable();
            $table->string('optimized_file')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('file_hash', 64)->nullable()->index();
            $table->string('processing_status', 20)->default('pending')->index();
            $table->text('processing_error')->nullable();
            $table->boolean('download_enabled')->default(false);
            $table->boolean('print_enabled')->default(false);
            $table->string('status', 30)->default('draft')->index();
            $table->string('visibility', 30)->default('public')->index();
            $table->string('password_hash')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'visibility', 'published_at']);
            $table->index(['title', 'publication_year']);
        });

        Schema::create('book_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('original_file');
            $table->string('optimized_file')->nullable();
            $table->string('file_hash', 64);
            $table->unsignedBigInteger('file_size');
            $table->unsignedInteger('page_count')->nullable();
            $table->text('change_notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['book_id', 'version_number']);
        });

        $this->createPivot('book_category', 'category_id', 'categories', true);
        $this->createPivot('book_collection', 'collection_id', 'collections', true);
        $this->createPivot('book_author', 'author_id', 'authors');
        $this->createPivot('book_tag', 'tag_id', 'tags');
    }

    private function createPivot(string $tableName, string $relatedKey, string $relatedTable, bool $sortable = false): void
    {
        Schema::create($tableName, function (Blueprint $table) use ($relatedKey, $relatedTable, $sortable) {
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId($relatedKey)->constrained($relatedTable)->cascadeOnDelete();
            if ($sortable) {
                $table->unsignedInteger('sort_order')->default(0)->index();
            }
            $table->primary(['book_id', $relatedKey]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_tag');
        Schema::dropIfExists('book_author');
        Schema::dropIfExists('book_collection');
        Schema::dropIfExists('book_category');
        Schema::dropIfExists('book_versions');
        Schema::dropIfExists('books');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('authors');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('publishers');
    }
};
