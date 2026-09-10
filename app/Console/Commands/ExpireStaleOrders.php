<?php

namespace App\Console\Commands;

use App\Domains\Order\Service\OrderService;
use Illuminate\Console\Command;

class ExpireStaleOrders extends Command
{
    protected $signature = 'orders:expire-stale {--hours=24}';

    protected $description = 'Expire pesanan yang belum lunas lebih dari N jam dan kembalikan stoknya';

    public function handle(OrderService $orders): int
    {
        $count = $orders->expireStaleOrders((int) $this->option('hours'));

        $this->info("{$count} pesanan di-expire.");

        return self::SUCCESS;
    }
}
