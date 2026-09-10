<?php

namespace App\Domains\Notification\Service;

use Illuminate\Support\Facades\Http;

class WhatsappService
{
    // Sementara pakai Fonnte (cloud, berbayar) sampai WAHA (self-hosted, gratis) selesai
    // disetup user — lihat config/services.php & CLAUDE.md bagian "Profil pelanggan tersimpan".
    public function send(string $phone, string $message): bool
    {
        $res = Http::withHeaders(['Authorization' => config('services.fonnte.token')])
            ->asForm()
            ->post('https://api.fonnte.com/send', [
                'target'  => preg_replace('/\D/', '', $phone),
                'message' => $message,
            ]);

        return (bool) $res->json('status', false);
    }
}
