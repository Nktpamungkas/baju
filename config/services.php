<?php

return [
    'midtrans' => [
        'server_key'    => env('MIDTRANS_SERVER_KEY'),
        'client_key'    => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    ],

    'biteship' => [
        'api_key'             => env('BITESHIP_API_KEY'),
        'webhook_token'       => env('BITESHIP_WEBHOOK_TOKEN'),
        'couriers'            => env('BITESHIP_COURIERS', 'jne,jnt,sicepat,anteraja,tiki,ninja,lion,idexpress,pos,wahana,sap,gojek,grab'),
        'origin_area_id'      => env('BITESHIP_ORIGIN_AREA_ID'),
        'origin_lat'          => env('BITESHIP_ORIGIN_LAT'),
        'origin_lng'          => env('BITESHIP_ORIGIN_LNG'),
        'origin_address'      => env('BITESHIP_ORIGIN_ADDRESS'),
        'origin_contact_name' => env('BITESHIP_ORIGIN_CONTACT_NAME'),
        'origin_contact_phone' => env('BITESHIP_ORIGIN_CONTACT_PHONE'),
    ],

    // Dormant — belum dipakai (WhatsappService pakai Fonnte sementara). Aktifkan lagi
    // begitu WAHA sudah jalan (lihat CLAUDE.md bagian "Profil pelanggan tersimpan").
    'waha' => [
        'base_url' => env('WAHA_BASE_URL', 'http://localhost:3000'),
        'api_key'  => env('WAHA_API_KEY'),
        'session'  => env('WAHA_SESSION', 'default'),
    ],

    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
    ],
];
