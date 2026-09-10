<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--keep=14 : Jumlah backup terakhir yang disimpan}';

    protected $description = 'Backup database.sqlite ke storage/app/backups, hapus backup lama';

    public function handle(): int
    {
        $source = database_path('database.sqlite');
        $dir = storage_path('app/backups');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $dest = $dir.'/backup-'.now()->format('Y-m-d_His').'.sqlite';

        // ".backup" adalah hot-backup command bawaan SQLite CLI — aman dipanggil walau ada
        // write yang lagi jalan, beda dengan copy() file mentah yang bisa korup di tengah transaksi.
        $result = Process::run(['sqlite3', $source, ".backup {$dest}"]);

        if ($result->failed()) {
            $this->error('Backup gagal: '.$result->errorOutput());

            return self::FAILURE;
        }

        $this->cleanupOld($dir, (int) $this->option('keep'));
        $this->info("Backup tersimpan: {$dest}");

        return self::SUCCESS;
    }

    private function cleanupOld(string $dir, int $keep): void
    {
        $files = collect(glob($dir.'/backup-*.sqlite'))->sort()->values();

        foreach ($files->slice(0, max(0, $files->count() - $keep)) as $old) {
            unlink($old);
        }
    }
}
