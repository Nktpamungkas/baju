<?php

// Project ini SQLite-only (lihat CLAUDE.md) — koneksi lain (MySQL/Postgres/
// Redis) sengaja tidak didefinisikan di sini. Config default bawaan Laravel
// selalu mendefinisikan koneksi mysql/mariadb yang mereferensikan konstanta
// PDO::MYSQL_ATTR_SSL_CA (deprecated sejak PHP 8.5) meski tidak dipakai —
// config custom ini menghindari peringatan itu sekaligus mencerminkan
// arsitektur project yang sebenarnya.

return [

    'default' => env('DB_CONNECTION', 'sqlite'),

    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],
    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

];
