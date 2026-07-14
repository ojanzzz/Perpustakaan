<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::unprepared("CREATE TRIGGER audit_logs_immutable_update BEFORE UPDATE ON audit_logs BEGIN SELECT RAISE(ABORT, 'audit_logs are immutable'); END");
            DB::unprepared("CREATE TRIGGER audit_logs_immutable_delete BEFORE DELETE ON audit_logs BEGIN SELECT RAISE(ABORT, 'audit_logs are immutable'); END");
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared("CREATE TRIGGER audit_logs_immutable_update BEFORE UPDATE ON audit_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs are immutable'");
            DB::unprepared("CREATE TRIGGER audit_logs_immutable_delete BEFORE DELETE ON audit_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs are immutable'");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_immutable_update');
            DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_immutable_delete');
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_immutable_update');
            DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_immutable_delete');
        }
    }
};
