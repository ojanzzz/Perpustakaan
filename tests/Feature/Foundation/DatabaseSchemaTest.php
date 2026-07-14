<?php

namespace Tests\Feature\Foundation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fresh_database_contains_the_required_application_tables(): void
    {
        $tables = [
            'users', 'institutions', 'permissions', 'role_permissions',
            'user_permissions', 'books', 'book_versions', 'categories',
            'book_category', 'collections', 'book_collection', 'authors',
            'book_author', 'publishers', 'tags', 'book_tag', 'languages',
            'favorites', 'reading_histories', 'bookmarks', 'book_views',
            'book_downloads', 'search_logs', 'feedback', 'announcements',
            'audit_logs', 'backups', 'settings',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table [{$table}].");
        }

        $this->assertFalse(Schema::hasTable('admin_level_permissions'));
    }

    public function test_users_table_has_the_authoritative_role_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'id', 'name', 'email', 'password', 'role', 'status',
            'email_verified_at', 'last_login_at', 'created_at', 'updated_at', 'deleted_at',
        ]));

        $this->assertFalse(Schema::hasColumn('users', 'admin_level'));
    }

    public function test_role_permissions_use_the_authoritative_role_column(): void
    {
        $this->assertTrue(Schema::hasColumns('role_permissions', [
            'role', 'permission_id',
        ]));
    }
}
