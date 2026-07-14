<?php

namespace App\Domain\Backup;

use App\Domain\Audit\AuditRecorder;
use App\Models\Backup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;
use ZipArchive;

class BackupService
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function run(Backup $backup): void
    {
        $backup->update(['status' => 'running', 'started_at' => now(), 'error_message' => null]);
        $temporarySql = tempnam(sys_get_temp_dir(), 'library-backup-');
        $temporaryZip = tempnam(sys_get_temp_dir(), 'library-archive-');

        try {
            if (! is_string(config("filesystems.disks.{$backup->disk}.root")) || config("filesystems.disks.{$backup->disk}.root") === '') {
                throw new RuntimeException('Lokasi penyimpanan backup tidak valid.');
            }
            if ($temporarySql === false || $temporaryZip === false) {
                throw new RuntimeException('Direktori sementara tidak dapat digunakan.');
            }

            $this->writeDatabaseDump($temporarySql);
            $zip = new ZipArchive;
            if ($zip->open($temporaryZip, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Arsip backup tidak dapat dibuat.');
            }
            $manifest = [
                'application' => config('app.name'),
                'created_at' => now()->toIso8601String(),
                'type' => $backup->type,
                'database_driver' => DB::connection()->getDriverName(),
                'format_version' => 2,
            ];
            $zip->addFile($temporarySql, 'database.sql');
            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            $zip->close();

            $path = now()->format('Y/m').'/eperpustakaan-'.now()->format('Ymd-His').'-'.$backup->id.'.zip';
            $stream = fopen($temporaryZip, 'rb');
            if ($stream === false || ! Storage::disk($backup->disk)->writeStream($path, $stream)) {
                throw new RuntimeException('Arsip backup gagal disimpan.');
            }
            if (is_resource($stream)) {
                fclose($stream);
            }
            $absolutePath = Storage::disk($backup->disk)->path($path);
            $backup->update([
                'path' => $path,
                'size' => filesize($absolutePath),
                'checksum' => hash_file('sha256', $absolutePath),
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            $this->audit->record('backup.completed', $backup, null, ['type' => $backup->type, 'checksum' => $backup->checksum]);
        } catch (Throwable $exception) {
            $backup->update(['status' => 'failed', 'error_message' => mb_substr($exception->getMessage(), 0, 1000), 'completed_at' => now()]);
            $this->audit->record('backup.failed', $backup, null, ['type' => $backup->type, 'error' => $backup->error_message]);
            throw $exception;
        } finally {
            if (is_string($temporarySql) && file_exists($temporarySql)) {
                unlink($temporarySql);
            }
            if (is_string($temporaryZip) && file_exists($temporaryZip)) {
                unlink($temporaryZip);
            }
        }
    }

    private function writeDatabaseDump(string $path): void
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('File dump sementara tidak dapat dibuat.');
        }
        fwrite($handle, "-- E-Perpustakaan Digital KPU\n-- Generated: ".now()->toIso8601String()."\n\n");
        $tables = $this->creationOrder($this->tables());
        fwrite($handle, DB::connection()->getDriverName() === 'sqlite' ? "PRAGMA foreign_keys=OFF;\n" : "SET FOREIGN_KEY_CHECKS=0;\n");
        foreach (array_reverse($tables) as $table) {
            fwrite($handle, 'DROP TABLE IF EXISTS '.$this->quoteIdentifier($table).";\n");
        }
        foreach ($tables as $table) {
            fwrite($handle, $this->createStatement($table).";\n");
            DB::table($table)->orderBy($this->firstColumn($table))->chunk(500, function ($rows) use ($handle, $table): void {
                foreach ($rows as $row) {
                    $values = array_map(fn ($value) => $value === null ? 'NULL' : DB::connection()->getPdo()->quote((string) $value), array_values((array) $row));
                    $columns = array_map(fn ($column) => $this->quoteIdentifier($column), array_keys((array) $row));
                    fwrite($handle, 'INSERT INTO '.$this->quoteIdentifier($table).' ('.implode(',', $columns).') VALUES ('.implode(',', $values).");\n");
                }
            });
            fwrite($handle, "\n");
        }
        foreach ($this->triggers() as $trigger) {
            fwrite($handle, $trigger.";\n");
        }
        fwrite($handle, DB::connection()->getDriverName() === 'sqlite' ? "PRAGMA foreign_keys=ON;\n" : "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
    }

    /** @return list<string> */
    private function tables(): array
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return array_values(array_map(fn ($row) => $row->name, DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")));
        }

        return array_values(array_map(fn ($row) => array_values((array) $row)[0], DB::select('SHOW TABLES')));
    }

    private function createStatement(string $table): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return (string) (DB::selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$table])->sql ?? '');
        }
        $row = (array) DB::selectOne('SHOW CREATE TABLE '.$this->quoteIdentifier($table));

        return (string) array_values($row)[1];
    }

    /** @param list<string> $tables @return list<string> */
    private function creationOrder(array $tables): array
    {
        $remaining = array_fill_keys($tables, true);
        $ordered = [];
        while ($remaining !== []) {
            $progress = false;
            foreach (array_keys($remaining) as $table) {
                $parents = array_values(array_filter(array_map(
                    fn (array $foreign) => $foreign['foreign_table'] ?? $foreign['foreignTable'] ?? null,
                    DB::getSchemaBuilder()->getForeignKeys($table)
                ), fn ($parent) => $parent && $parent !== $table));
                if (array_intersect($parents, array_keys($remaining)) === []) {
                    $ordered[] = $table;
                    unset($remaining[$table]);
                    $progress = true;
                }
            }
            if (! $progress) {
                $ordered = [...$ordered, ...array_keys($remaining)];
                break;
            }
        }

        return $ordered;
    }

    private function firstColumn(string $table): string
    {
        return DB::getSchemaBuilder()->getColumnListing($table)[0];
    }

    private function quoteIdentifier(string $value): string
    {
        return DB::connection()->getDriverName() === 'sqlite' ? '"'.str_replace('"', '""', $value).'"' : '`'.str_replace('`', '``', $value).'`';
    }

    /** @return list<string> */
    private function triggers(): array
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return array_values(array_filter(array_map(fn ($row) => $row->sql, DB::select("SELECT sql FROM sqlite_master WHERE type='trigger' AND sql IS NOT NULL ORDER BY name"))));
        }

        return array_values(array_filter(array_map(function ($row): ?string {
            $name = (array) $row;
            $trigger = $name['Trigger'] ?? array_values($name)[0] ?? null;
            if (! $trigger) {
                return null;
            }
            $create = (array) DB::selectOne('SHOW CREATE TRIGGER '.$this->quoteIdentifier((string) $trigger));

            return $create['SQL Original Statement'] ?? $create['Create Trigger'] ?? null;
        }, DB::select('SHOW TRIGGERS'))));
    }
}
