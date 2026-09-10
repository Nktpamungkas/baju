<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pesanan yang gak dibayar-bayar lebih dari 24 jam di-expire otomatis + stok balik
// (lihat OrderService::expireStaleOrders). Butuh satu cron di STB:
// * * * * * php /path/ke/project/artisan schedule:run >> /dev/null 2>&1
Schedule::command('orders:expire-stale')->hourly();

// Backup database.sqlite harian, simpan 14 hari terakhir (lihat Console/Commands/BackupDatabase).
// Pakai cron schedule:run yang sama di atas — tidak butuh cron terpisah.
Schedule::command('db:backup')->daily();
