<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\EmailType;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;
use DateTimeInterface;
use Illuminate\Validation\ValidationException;

final class ExportOrganizationAsSql implements ShouldQueue
{
    use Queueable;

    private array $tables;

    private ?string $outputFilePath = null;

    private string $driverName;

    public function __construct(
        public ?Organization $organization,
        public User $user,
    ) {}

    /**
     * Export all the organization's account as SQL
     */
    public function handle(): void
    {
        $this->validate();
        $this->prepareTables();
        $this->exportData();
        $this->sendEmailToUser();
    }

    private function validate(): void
    {
        if ($this->user->isPartOfOrganization($this->organization) === false) {
            throw ValidationException::withMessages([
                'organization' => 'User is not part of the organization.',
            ]);
        }
    }

    private function prepareTables(): void
    {
        $excludedTables = [
            'cache',
            'cache_locks',
            'failed_jobs',
            'job_batches',
            'magic_links',
            'password_reset_tokens',
            'personal_access_tokens',
            'sessions',
            'sqlite_sequence',
        ];

        // get the tables in the database (driver-specific)
        $tables = $this->listAllTableNames();

        // filter out the excluded tables
        $this->tables = array_diff($tables, $excludedTables);
    }

    /**
     * Export the data to SQL. Excludes non-organization tables.
     */
    private function exportData(): void
    {
        if (!$this->organization instanceof Organization) {
            return;
        }

        DB::connection();

        // Prepare output file (streaming, append as we go)
        $dir = storage_path('app/exports');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $timestamp = Carbon::now()->format('Ymd-His');
        $orgSlug = (string) ($this->organization->slug ?? $this->organization->id);
        $base = sprintf('org-%s-%s.sql', Str::slug($orgSlug), $timestamp);
        $this->outputFilePath = $dir . DIRECTORY_SEPARATOR . $base;

        $handle = fopen($this->outputFilePath, 'ab');
        if ($handle === false) {
            return;
        }

        // Header and FK off for portability
        $this->writeLine($handle, '-- Organization data export');
        $this->writeLine($handle, '-- Organization ID: ' . $this->organization->id);
        $this->writeLine($handle, '-- Generated at: ' . Carbon::now()->toDateTimeString());
        if ($this->driverName === 'sqlite') {
            $this->writeLine($handle, 'PRAGMA foreign_keys=OFF;');
        } elseif (in_array($this->driverName, ['mysql', 'mariadb'])) {
            $this->writeLine($handle, 'SET FOREIGN_KEY_CHECKS=0;');
        }
        $this->writeLine($handle, '');

        $chunkSize = 500;

        foreach ($this->tables as $table) {
            try {
                // Only export tables that have an organization_id column
                if (! Schema::hasColumn($table, 'organization_id')) {
                    continue;
                }

                // Get ordered list of column names for consistent inserts
                $columns = Schema::getColumnListing($table);
                if (empty($columns)) {
                    continue;
                }

                $this->writeLine($handle, sprintf('-- Table %s', $table));

                $query = DB::table($table)
                    ->where('organization_id', $this->organization->id);

                // Prefer chunkById when an auto-increment id exists
                if (Schema::hasColumn($table, 'id')) {
                    $query->orderBy('id')->chunkById($chunkSize, function ($rows) use ($handle, $table, $columns): void {
                        foreach ($rows as $row) {
                            $this->writeInsert($handle, $table, $columns, (array) $row);
                        }
                    }, 'id');
                } else {
                    // Fallback to offset-based chunking
                    $firstColumn = $columns[0];
                    $query->orderBy($firstColumn)->chunk($chunkSize, function ($rows) use ($handle, $table, $columns): void {
                        foreach ($rows as $row) {
                            $this->writeInsert($handle, $table, $columns, (array) $row);
                        }
                    });
                }

                $this->writeLine($handle, '');
            } catch (Throwable $e) {
                $this->writeLine($handle, sprintf('-- Skipped table %s due to error: %s', $table, $e->getMessage()));
            }
        }

        // Re-enable FKs
        if ($this->driverName === 'sqlite') {
            $this->writeLine($handle, 'PRAGMA foreign_keys=ON;');
        } elseif (in_array($this->driverName, ['mysql', 'mariadb'])) {
            $this->writeLine($handle, 'SET FOREIGN_KEY_CHECKS=1;');
        }

        fclose($handle);
    }

    private function sendEmailToUser(): void
    {
        SendEmail::dispatch(
            emailType: EmailType::EXPORT_ORGANIZATION,
            user: $this->user,
            parameters: [
                'organizationName' => $this->organization->name,
                'link' => 'bla',
            ],
        )->onQueue('high');
    }

    /**
     * Return all table names for the current connection.
     *
     * @return list<string>
     */
    private function listAllTableNames(): array
    {
        $connection = DB::connection();
        $this->driverName = $connection->getDriverName();

        return match ($this->driverName) {
            'sqlite' => collect($connection->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"))
                ->map(fn($row): array => (array) $row)
                ->pluck('name')
                ->all(),
            'mysql', 'mariadb' => collect($connection->select('SHOW TABLES'))
                ->map(fn($row): array => (array) $row)
                ->map(fn(array $row) => reset($row))
                ->filter()
                ->values()
                ->all(),
            'pgsql' => collect($connection->select("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'"))
                ->map(fn($row): array => (array) $row)
                ->pluck('tablename')
                ->all(),
            default => [],
        };
    }

    private function writeInsert($handle, string $table, array $columns, array $row): void
    {
        $qi = fn(string $id): string => match ($this->driverName) {
            'pgsql' => '"' . str_replace('"', '""', $id) . '"',
            default => '`' . str_replace('`', '``', $id) . '`', // mysql, sqlite accept backticks
        };
        // Rebuild with driver-aware quoting
        $colList = implode(', ', array_map(fn(string $c): string => $qi($c), $columns));

        $values = [];
        foreach ($columns as $c) {
            $values[] = $this->toSqlValue($row[$c] ?? null);
        }

        $valueList = implode(', ', $values);
        $this->writeLine($handle, sprintf('INSERT INTO %s (%s) VALUES (%s);', $qi($table), $colList, $valueList));
    }

    private function toSqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        // Normalize DateTimeInterface to string
        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        }

        // Ensure scalar string
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $string = (string) $value;
        // Escape single quotes by doubling them per SQL standard
        $string = str_replace("'", "''", $string);

        return "'{$string}'";
    }

    private function writeLine($handle, string $line): void
    {
        fwrite($handle, $line . "\n");
    }
}
