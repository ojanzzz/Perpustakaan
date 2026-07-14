<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        DB::table('settings')->upsert([
            ['group' => 'general', 'key' => 'site_name', 'value' => 'E-Perpustakaan Digital KPU', 'type' => 'string', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'auth', 'key' => 'member_registration_enabled', 'value' => 'false', 'type' => 'boolean', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'upload', 'key' => 'pdf_max_size_mb', 'value' => '100', 'type' => 'integer', 'is_public' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'security', 'key' => 'admin_2fa_required', 'value' => 'false', 'type' => 'boolean', 'is_public' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'embed', 'key' => 'embed_allowed_domains', 'value' => '', 'type' => 'string', 'is_public' => false, 'created_at' => $now, 'updated_at' => $now],
        ], ['key'], ['group', 'value', 'type', 'is_public', 'updated_at']);
    }
}
