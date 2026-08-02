<?php

return [
    // Password panel admin. Ubah di .env: ADMIN_PASSWORD=...
    'admin_password' => env('ADMIN_PASSWORD', 'nale123'),

    // Nomor WhatsApp untuk tombol "Tanya via WhatsApp" (kode negara + nomor, tanpa + atau 0 di depan, mis. 6281234567890).
    // Kosongkan/hapus dari .env untuk sembunyikan tombolnya kalau belum ada nomor.
    'whatsapp' => env('WHATSAPP_NUMBER'),
];
