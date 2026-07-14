<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table): void {
                $table->string('role', 20);
                $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
                $table->primary(['role', 'permission_id']);
            });
        }

        $permissionRows = DB::table('permissions')
            ->pluck('id')
            ->map(fn (int $permissionId): array => [
                'role' => 'superadmin',
                'permission_id' => $permissionId,
            ])
            ->all();

        if ($permissionRows !== []) {
            DB::table('role_permissions')->insertOrIgnore($permissionRows);
        }

        if (Schema::hasColumn('users', 'admin_level')) {
            DB::table('users')
                ->where('role', 'admin')
                ->where('admin_level', 'superadmin')
                ->update(['role' => 'superadmin', 'status' => 'active']);

            DB::table('users')
                ->where('role', 'admin')
                ->update(['role' => 'member', 'status' => 'inactive']);
        } else {
            DB::table('users')
                ->where('role', 'admin')
                ->update(['role' => 'member', 'status' => 'inactive']);
        }

        DB::table('users')
            ->where('role', 'visitor')
            ->update(['role' => 'public', 'status' => 'inactive']);

        Schema::dropIfExists('admin_level_permissions');

        if (Schema::hasColumn('users', 'admin_level')) {
            foreach (Schema::getIndexes('users') as $index) {
                if (in_array('admin_level', $index['columns'], true)) {
                    Schema::table('users', function (Blueprint $table) use ($index): void {
                        $table->dropIndex($index['name']);
                    });
                }
            }

            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('admin_level');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'admin_level')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('admin_level', 30)->nullable()->index();
            });
        }

        DB::table('users')
            ->where('role', 'superadmin')
            ->update(['role' => 'admin', 'admin_level' => 'superadmin']);

        DB::table('users')
            ->where('role', 'public')
            ->update(['role' => 'visitor', 'admin_level' => null]);

        if (! Schema::hasTable('admin_level_permissions')) {
            Schema::create('admin_level_permissions', function (Blueprint $table): void {
                $table->string('admin_level', 30);
                $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
                $table->primary(['admin_level', 'permission_id']);
            });
        }

        if (Schema::hasTable('role_permissions')) {
            $permissionRows = DB::table('role_permissions')
                ->where('role', 'superadmin')
                ->pluck('permission_id')
                ->map(fn (int $permissionId): array => [
                    'admin_level' => 'superadmin',
                    'permission_id' => $permissionId,
                ])
                ->all();

            if ($permissionRows !== []) {
                DB::table('admin_level_permissions')->insertOrIgnore($permissionRows);
            }
        }

        Schema::dropIfExists('role_permissions');
    }
};
