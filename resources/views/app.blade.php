<!DOCTYPE html>
<html lang="id" class="antialiased">
<head>
    @php
        $metaTitle = $meta['title'] ?? config('app.name', 'NALE');
        $metaDescription = $meta['description'] ?? 'Katalog baju anak NALE — pakaian yang ikut tumbuh bersama mereka.';
        $metaImage = !empty($meta['image']) ? url($meta['image']) : asset('img/mascot-small.png');
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name', 'NALE') }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:image" content="{{ $metaImage }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon-180.png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
