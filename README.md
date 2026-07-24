# NALE — Katalog Baju Anak (Laravel 11 + Inertia + Vue 3)

Website company profile + katalog produk pribadi — bukan toko online. Pembeli
melihat katalog di sini, transaksi tetap lewat Shopee/Tokopedia (link per
produk diatur di admin). Detail arsitektur & konteks lengkap ada di
[CLAUDE.md](CLAUDE.md). Stack: Laravel 11, Inertia.js, Vue 3, Tailwind,
database **SQLite** (tanpa setup DB).

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

Domain-driven — lihat [CLAUDE.md](CLAUDE.md) untuk penjelasan lengkap tiap layer.

```
app/
  Domains/Product/Controller/    → ProductController (publik), ProductAdminController (admin)
  Domains/Product/Service/       → ProductService (business logic)
  Domains/Product/Repository/    → ProductRepository (query Eloquent)
  Domains/Auth/Controller/       → AuthController (login/logout admin)
  Domains/Auth/Service/          → AdminAuthService (cek password, session)
  Http/Middleware/               → AdminAuth, HandleInertiaRequests
  Models/Product.php
routes/web.php                    → semua route
database/
  migrations/                     → products
  seeders/ProductSeeder.php       → baca database/data/products.json
  data/products.json              → 5 produk (sumber data awal)
resources/
  js/app.js                       → bootstrap Inertia + Vue
  js/pages/   Home Catalog Product About Admin/Login Admin/Products
  js/components/  AppHeader AppFooter ProductCard AdminLayout
  js/lib/format.js                → helper rp() (format Rupiah)
  css/app.css                     → font + token + recipe
  views/app.blade.php             → root Inertia
public/img/                       → foto produk
tailwind.config.js                → token desain NALE (jangan diubah sembarangan)
```

## Route

| URL | Halaman | Data dari controller |
|---|---|---|
| `/` | Home | `products` |
| `/katalog?type=` | Catalog | `products`, `type` |
| `/produk/{id}` | Product | `product`, `related` — tombol beli langsung ke Shopee/Tokopedia |
| `/tentang` | About | — |
| `/admin/login` | Admin login | password dari `.env` |
| `/admin` | Admin — Produk | CRUD + upload foto varian + link Shopee/Tokopedia |

## Panel Admin

- Buka **http://localhost:8000/admin** → login.
- Password default: **`nale123`** — ganti di `.env` baris `ADMIN_PASSWORD=` (wajib
  diganti sebelum deploy ke server publik).
- **Produk**: tambah / edit / hapus produk, kelola varian + upload foto per warna
  (foto pertama = thumbnail), dan isi link **Shopee / Tokopedia** — inilah yang
  ditampilkan sebagai tombol beli di halaman produk publik. Tidak ada
  cart/checkout di website ini; transaksi selalu terjadi di marketplace.
- Foto yang diunggah admin disimpan ke `public/img/` (URL `/img/...`) — tidak
  perlu `php artisan storage:link`.

> Perubahan produk di admin **langsung tersimpan ke database** dan otomatis
> tampil di etalase.

## Yang masih perlu dikerjakan sebelum deploy ke STB

- **Harga**: angka di `products.json` masih perkiraan — sesuaikan.
- **Foto**: crop dari katalog (res menengah). Ganti dengan foto studio
  resolusi tinggi (nama file sama) bila ada.
- **Password admin**: ganti `ADMIN_PASSWORD` dari default sebelum server bisa
  diakses dari luar.
- **Deploy**: set `APP_ENV=production`, `APP_DEBUG=false`, `npm run build`,
  arahkan web server ke folder `public/`. Database tetap SQLite (target STB
  RAM 2GB) — jangan pindah ke MySQL/Postgres.
