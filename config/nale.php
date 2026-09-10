<?php

return [
    // Password panel admin. Ubah di .env: ADMIN_PASSWORD=...
    'admin_password' => env('ADMIN_PASSWORD', 'nale123'),

    // Nomor WhatsApp untuk tombol "Tanya via WhatsApp" (kode negara + nomor, tanpa + atau 0 di depan, mis. 6281234567890).
    // Kosongkan/hapus dari .env untuk sembunyikan tombolnya kalau belum ada nomor.
    'whatsapp' => env('WHATSAPP_NUMBER'),

    // Link Instagram toko, ditampilkan sebagai ikon di footer. Kosongkan untuk sembunyikan.
    'instagram' => env('INSTAGRAM_URL'),

    // Rekening tujuan transfer manual, ditampilkan di halaman checkout & tracking pesanan.
    'bank' => [
        'name'           => env('BANK_NAME'),
        'account_number' => env('BANK_ACCOUNT_NUMBER'),
        'account_name'   => env('BANK_ACCOUNT_NAME'),
    ],
];
