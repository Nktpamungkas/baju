# NALE — Toko Baju Anak (Laravel 11 + Inertia + Vue 3)

Project **siap jalan**. Prototipe visual: `NALE.dc.html`. Stack: Laravel 11,
Inertia.js, Vue 3, Pinia, Tailwind, database **SQLite** (tanpa setup DB).

## Jalankan lokal (5 langkah)

```bash
# 1. Dependency PHP + JS
composer install
npm install

# 2. Environment + app key
cp .env.example .env      # (sudah ada .env juga)
php artisan key:generate

# 3. Database: buat file sqlite + migrate + isi 5 produk
#    (file database/database.sqlite sudah disertakan, kosong)
php artisan migrate --seed

# 4. Build / dev asset
npm run dev               # mode dev (hot reload)   ── biarkan jalan di 1 terminal
#   atau: npm run build   # build produksi sekali

# 5. Jalankan server (terminal lain)
php artisan serve
```

Buka **http://localhost:8000**.

> Windows/macOS: kalau `database/database.sqlite` belum ada, buat manual:
> `type NUL > database\database.sqlite` (Windows) / `touch database/database.sqlite` (mac/linux).

## Struktur

```
app/
  Http/Controllers/ProductController.php   → home, catalog, show, about
  Http/Controllers/CartController.php      → keranjang + POST /checkout (simpan order)
  Http/Middleware/HandleInertiaRequests.php
  Models/Product.php  Order.php  OrderItem.php
routes/web.php                              → semua route
database/
  migrations/                               → products, orders, order_items
  seeders/ProductSeeder.php                 → baca database/data/products.json
  data/products.json                        → 5 produk (sumber data awal)
resources/
  js/app.js                                 → bootstrap Inertia + Vue + Pinia
  js/pages/   Home Catalog Product Cart About
  js/components/  AppHeader AppFooter ProductCard
  js/stores/cart.js                         → Pinia (localStorage)
  css/app.css                               → font + token + recipe
  views/app.blade.php                       → root Inertia
public/img/                                 → 20 foto produk
tailwind.config.js                          → token desain NALE (jangan diubah sembarangan)
```

## Route

| URL | Halaman | Data dari controller |
|---|---|---|
| `/` | Home | `products` |
| `/katalog?type=` | Catalog | `products`, `type` |
| `/produk/{id}` | Product | `product`, `related` |
| `/tentang` | About | — |
| `/keranjang` | Cart | — (keranjang di localStorage) |
| `POST /checkout` | — | simpan `orders` + `order_items` |
| `/admin/login` | Admin login | password dari `.env` |
| `/admin` | Admin — Produk | CRUD + upload foto varian |
| `/admin/pesanan` | Admin — Pesanan | daftar order dari checkout |

## Panel Admin

Sudah **jadi bagian dari project** ini (bukan file terpisah lagi).

- Buka **http://localhost:8000/admin** → login.
- Password default: **`nale123`** — ganti di `.env` baris `ADMIN_PASSWORD=`.
- **Produk**: tambah / edit / hapus produk, kelola varian + upload foto per warna
  (foto pertama = thumbnail), dan isi link **Shopee / Tokopedia**. Order tetap
  diproses di marketplace; admin hanya kelola katalog.
- **Pesanan**: menampilkan order yang masuk lewat checkout website (tabel
  `orders`). Read-only untuk saat ini.
- Foto yang diunggah admin disimpan ke `public/img/` (URL `/img/...`) — tidak
  perlu `php artisan storage:link`.

> Perubahan produk di admin **langsung tersimpan ke database** dan otomatis
> tampil di etalase — beda dengan prototipe `NALE Admin.dc.html` yang cuma
> menyimpan di browser (localStorage).

## Yang masih perlu dikerjakan sebelum produksi

- **Pembayaran**: `Cart.vue` masih konfirmasi dummy (localStorage). Ganti
  tombol "Lanjut ke Pembayaran" → `router.post('/checkout', {...})` (endpoint
  sudah ada, sudah menyimpan order), lalu integrasi **Midtrans/Xendit**.
- **Harga**: angka di `products.json` masih perkiraan — sesuaikan.
- **Foto**: crop dari katalog (res menengah). Ganti dengan foto studio
  resolusi tinggi (nama file sama) bila ada.
- **Deploy**: set `APP_ENV=production`, `APP_DEBUG=false`, `npm run build`,
  arahkan web server ke folder `public/`. Untuk MySQL, ubah blok `DB_*` di `.env`.
