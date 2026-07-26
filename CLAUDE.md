# NALE — Katalog Baju Pribadi

## Apa ini dan untuk apa

NALE **bukan** toko online / e-commerce. Ini adalah **website company profile + katalog produk pribadi** milik pemilik (jualan baju anak sampingan). Fungsinya cuma pelengkap: pemilik kirim link website ini ke calon pembeli supaya mereka bisa lihat katalog produk dengan rapi, lalu transaksi beli tetap terjadi **di luar website** — lewat Shopee atau Tokopedia.

Jangan perlakukan project ini seperti produk e-commerce generik yang butuh cart, checkout, payment gateway, akun pelanggan, dll. Kalau ada permintaan fitur yang mengarah ke situ, konfirmasi dulu ke user — kemungkinan besar itu di luar tujuan project ini.

## Aturan penting (jangan dilanggar tanpa diminta eksplisit)

- **Tidak ada keranjang/checkout.** Sistem cart + Order/OrderItem pernah ada di versi awal project ini dan sudah **sengaja dihapus** (lihat git history). Setiap produk hanya punya dua tombol: "Beli di Shopee" dan "Beli di Tokopedia", mengarah ke URL yang diisi admin lewat kolom `products.shopee` / `products.toko`. Jangan tambahkan cart lagi kecuali diminta eksplisit.
- **Database wajib SQLite.** Target deploy adalah STB (set-top box) pribadi milik user dengan RAM cuma **2GB**. Jangan tambahkan MySQL/Postgres/Redis/queue worker atau dependency berat lain — semua harus tetap ringan agar jalan nyaman di RAM sekecil itu.
- **Password admin default (`nale123`, di `config/nale.php` fallback `env('ADMIN_PASSWORD')`) harus diganti sebelum deploy ke server yang bisa diakses publik.** Saat ini masih default karena project baru jalan di local — ingatkan user kalau mereka mulai proses deploy ke STB.
- **`composer.json` punya `config.policy.advisories.block: false`** — ini sengaja dimatikan (dikonfirmasi dengan user) karena Laravel 11.x kena 3 security advisory yang tidak akan pernah dipatch di versi 11.x manapun (perbaikan baru ada di Laravel 12.60+/13.10+). Project ini tidak pakai `Mail::` atau signed URL, jadi celah tsb secara praktis tidak tereksploitasi saat ini — tapi kalau nanti nambah fitur yang pakai salah satu dari itu, evaluasi ulang apakah perlu upgrade Laravel major version dulu.

## Arsitektur: domain-driven

Business logic diorganisir per domain di `app/Domains/<Domain>/`, bukan flat di `app/Http/Controllers`:

```
app/Domains/
  Product/
    Controller/ProductController.php       # publik: home, catalog, show, about
    Controller/ProductAdminController.php   # admin: CRUD produk + upload foto varian
    Service/ProductService.php              # business logic: generate slug id, default field, upload foto
    Repository/ProductRepository.php        # satu-satunya tempat query Eloquent ke Product
  Auth/
    Controller/AuthController.php           # login/logout admin
    Service/AdminAuthService.php            # cek password, kelola session is_admin
```

Pembagian tanggung jawab:
- **Controller** — hanya urus HTTP in/out (request → panggil Service → response/Inertia::render). Tidak boleh ada query Eloquent atau logic bisnis langsung di sini.
- **Service** — semua business logic (validasi turunan, generate ID, default value, aturan bisnis lain).
- **Repository** — satu-satunya lapisan yang boleh menyentuh Eloquent/`App\Models\*` untuk domain itu.

Catatan:
- **Tidak semua domain butuh Repository.** Domain `Auth` sengaja tidak punya Repository karena tidak ada tabel database untuk admin — password admin cuma satu nilai di `config/nale.php` / env, bukan data yang perlu di-query. Jangan buat Repository kosong hanya demi konsistensi pola.
- **Repository/Service di sini adalah class konkret biasa, TIDAK pakai interface + binding di service container.** Cuma ada satu implementasi (SQLite) dan tidak ada rencana ganti-ganti implementasi, jadi interface di sini cuma seremoni tanpa manfaat — jangan ditambahkan.
- `app/Models/Product.php` tetap di lokasi standar Laravel (`app/Models`), bukan dipindah ke folder domain — cukup Repository yang membungkusnya.
- `app/Http/Middleware/AdminAuth.php` tetap di lokasi standar Laravel (dirujuk lewat FQCN penuh di `bootstrap/app.php`), tapi logic-nya didelegasikan ke `AdminAuthService::check()`.
- Ketika menambah domain baru, ikuti pola yang sama: `app/Domains/<NamaDomain>/{Controller,Service,Repository}` (skip layer yang memang tidak relevan, seperti contoh `Auth` di atas).

## Stack

- Laravel 11 + Inertia.js + Vue 3 + Tailwind CSS
- Database: **SQLite saja** (`database/database.sqlite`)
- Tidak ada Pinia (sudah dihapus bersamaan dengan cart) — state di Vue cukup pakai `ref`/`computed` biasa
- Ponytail plugin aktif (mode `lite`) — tulis kode seminim mungkin, jangan bikin abstraksi yang belum perlu

## Data produk

Kolom penting di tabel `products` (lihat `database/migrations/2024_01_01_000000_create_products_table.php`):
- `id` — slug string, primary key, bukan auto-increment
- `shopee`, `toko` — URL langsung ke listing produk di Shopee/Tokopedia, diisi admin lewat panel admin (`/admin`), ditampilkan sebagai tombol beli di halaman detail produk publik
- `variants` (json array of `{name, img}`), `sizeCols`, `sizes` (json) — untuk galeri varian & tabel ukuran

## Repo

Repo publik personal di `github.com/Nktpamungkas/baju` (akun pribadi, bukan akun/org perusahaan tempat user bekerja).

## Git & Deploy

- **Jangan `git push` atas inisiatif sendiri.** User yang akan `git commit`/`push` sendiri (lewat terminal atau `./deploy.sh`). Kalau ada perubahan kode yang perlu di-deploy, cukup beri tahu user apa yang berubah dan biarkan mereka yang push — jangan push duluan tanpa diminta eksplisit.
- Deploy ke STB (`/opt/baju`, dijalankan via FrankenPHP + Cloudflare Tunnel) sudah otomatis lewat GitHub Actions (`.github/workflows/deploy.yml`) begitu ada push ke `main` — jadi `git push` biasa (lewat terminal atau GUI VSCode) sudah cukup, tidak perlu SSH manual lagi.
- `deploy.sh` di root project adalah shortcut manual (build + commit + push + SSH deploy sekaligus) kalau user mau trigger dari satu command tanpa nunggu CI — tapi ini juga harus dijalankan oleh user sendiri, bukan oleh Claude.
