# NALE — Katalog & Checkout Baju Pribadi

## Apa ini dan untuk apa

NALE adalah **website company profile + katalog produk pribadi** milik pemilik (jualan baju anak sampingan). Awalnya murni katalog (checkout selalu lewat Shopee/Tokopedia), tapi sejak checkout in-house ditambahkan, pembeli **bisa langsung belanja & bayar di website ini** — tujuannya supaya pemilik tidak selalu kena potongan ~9% Shopee. Link Shopee/Tokopedia tetap ada di halaman produk sebagai opsi kedua, tidak dihapus.

Ini tetap bukan toko online generik yang butuh akun pelanggan/login pembeli — checkout selalu **guest** (tanpa akun), buyer melacak pesanannya lewat link unik (`/pesanan/{tracking_token}`) yang dikirim setelah checkout, bukan lewat dashboard "akun saya".

## Aturan penting (jangan dilanggar tanpa diminta eksplisit)

- **Harga & berat TIDAK PERNAH dipercaya dari client saat checkout.** `OrderService::resolveItems()` selalu re-lookup harga/berat dari `Product` berdasarkan `product_id` yang dikirim client — field harga/nama yang dikirim dari frontend diabaikan. Ini mencegah manipulasi harga lewat request palsu. Ongkir (`shipping_cost`), sebaliknya, DIPERCAYA dari client karena itu hasil quote live dari Biteship beberapa detik sebelumnya.
- **Order/OrderItem tidak pernah menyimpan referensi FK ke Product.** `order_items.product_id` cuma string biasa (tanpa FK constraint) dan semua field lain (`name`/`price`/`weight`/`variant`/`size`) adalah snapshot saat order dibuat. Produk boleh dihapus/diubah kapan saja tanpa merusak histori order.
- **Webhook Biteship wajib diverifikasi, tidak boleh dipercaya mentah.** Biteship tidak sign payload-nya sama sekali, jadi diamankan lewat token acak di path URL (`BITESHIP_WEBHOOK_TOKEN`, dicek `hash_equals` di `WebhookController::biteship`). Rute ini satu-satunya (bareng `webhooks/midtrans`, dormant — lihat bagian Checkout) yang dikecualikan dari CSRF (`bootstrap/app.php`), karena dipanggil server pihak ketiga.
- **Database wajib SQLite.** Target deploy adalah STB (set-top box) pribadi milik user dengan RAM cuma **2GB**. Jangan tambahkan MySQL/Postgres/Redis/queue worker atau dependency berat lain. Status pengiriman diproses **sinkron di webhook** (bukan lewat queue/scheduler) — sengaja begitu karena `QUEUE_CONNECTION=sync` dan tidak ada worker; volume transaksi toko pribadi ini kecil jadi ini aman.
- **Password admin default (`nale123`, di `config/nale.php` fallback `env('ADMIN_PASSWORD')`) harus diganti sebelum deploy ke server yang bisa diakses publik.** Sekarang uang beneran mengalir lewat panel ini (konfirmasi pembayaran manual, kode diskon, retry pickup), jadi ini makin kritis dibanding waktu situs masih katalog doang.
- **Kredensial Biteship cuma sandbox/test di local — jangan pernah commit key produksi ke `.env`/git.** Sebelum go-live: Biteship butuh aktivasi "Order API" terpisah untuk key `biteship_live.`. (Midtrans juga begitu kalau nanti diaktifkan lagi — lihat bagian Checkout.)
- **Simpan-profil pelanggan (nama/email/alamat by nomor WhatsApp) WAJIB lewat verifikasi OTP dulu**, jangan pernah dibuat langsung save tanpa verifikasi — itu bakal bikin siapapun bisa lihat data orang lain cuma modal tebak nomor HP. Lihat bagian "Profil pelanggan tersimpan" di bawah.
- **Endpoint yang rawan disalahgunakan (brute-force / spam biaya) WAJIB di-throttle.** `admin/login` (5/menit — brute-force password), `checkout/kirim-otp` (3/menit — tiap panggil beneran nembak biaya Fonnte), `checkout/verifikasi-otp` & `checkout/diskon` (10/menit — brute-force kode). Pakai middleware bawaan Laravel `throttle:N,M` di `routes/web.php`, jangan dihapus/dilonggarin tanpa alasan kuat.
- **Stok produk (`products.stock`) dikurangi saat CHECKOUT** (`OrderService::checkout()`, pakai `ProductRepository::decrementStock()` yang atomik lewat WHERE guard — bukan check-then-update manual) **dan dikembalikan saat pesanan DIBATALKAN** (`cancelOrder()`) **atau AUTO-EXPIRE** (`expireStaleOrders()`, lihat bullet di bawah). `stock` bernilai `null` berarti tidak dilacak/tak terbatas (perilaku lama, default produk baru). Ini level SEDERHANA (per produk, bukan per varian/ukuran) — cukup buat cegah oversell kasar, kalau butuh presisi per varian/ukuran nanti perlu tabel baru.
- **Pesanan yang belum dibayar >24 jam di-expire otomatis** (`OrderService::expireStaleOrders()`, command `orders:expire-stale`, dijadwalkan `hourly()` di `routes/console.php`) — `payment_status` jadi `expire` (reuse vocab Midtrans lama, sudah ada labelnya di `Order/Track.vue`) dan stok item-nya dikembalikan, biar stok gak "ketahan" selamanya sama keranjang yang ditinggal orang. Ini pakai **scheduler** Laravel, BUKAN queue worker — beda dari larangan "queue worker" di bullet SQLite di atas: scheduler cuma butuh **satu cron `* * * * * php artisan schedule:run`** di STB (proses pendek, langsung exit, aman buat RAM 2GB), bukan proses panjang yang nunggu terus seperti queue worker. **Cron ini WAJIB ditambahkan manual di STB (aksi user sendiri, bukan Claude) — tanpa cron ini, order tidak akan pernah ke-expire otomatis** meskipun kode & jadwalnya sudah benar.
- **`database/database.sqlite` di-backup harian** (`db:backup`, dijadwalkan `daily()` di `routes/console.php`, pakai cron `schedule:run` yang sama dengan auto-expire di atas) ke `storage/app/backups/backup-{timestamp}.sqlite`, simpan 14 hari terakhir (`--keep=14`, lama otomatis dihapus). Pakai `sqlite3 <db> ".backup <dest>"` (hot backup bawaan SQLite CLI, aman dipanggil meski ada write yang lagi jalan) — **BUKAN** `copy()` file mentah, yang bisa menghasilkan salinan korup kalau ke-copy di tengah transaksi. **Butuh binary `sqlite3` CLI terpasang di STB** (bukan cuma ekstensi PHP `pdo_sqlite`/`sqlite3` yang sudah pasti ada) — kalau belum ada, `apt install sqlite3` (aksi user sendiri di STB). Folder `storage/app/*` sudah di-gitignore by default jadi file backup tidak pernah kecommit.
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
  Order/
    Controller/CheckoutController.php       # publik: show/rates/sendOtp/verifyOtp/discount/store/repay/track/uploadProof
    Controller/OrderAdminController.php     # admin: list order, konfirmasi bayar, retry pickup, tracking, CRUD diskon, upload QRIS
    Controller/WebhookController.php        # terima webhook Midtrans (dormant) & Biteship
    Service/OrderService.php                # satu-satunya tempat business logic checkout/order/OTP/profil pelanggan
    Repository/OrderRepository.php          # satu-satunya tempat query Eloquent ke Order/OrderItem
    Repository/DiscountRepository.php       # satu-satunya tempat query Eloquent ke Discount
    Repository/CustomerRepository.php       # satu-satunya tempat query Eloquent ke Customer (profil tersimpan)
    Repository/OtpRepository.php            # satu-satunya tempat query Eloquent ke OtpVerification
  Payment/
    Service/PaymentService.php              # buat Snap transaction, verifikasi+parse webhook Midtrans (DORMANT, lihat bagian Checkout)
  Shipping/
    Service/ShippingService.php             # cek ongkir (by lat/lng, dengan cache), booking pickup, tracking, parse webhook Biteship
    Repository/ShippingRateRepository.php   # satu-satunya tempat query Eloquent ke ShippingRateQuote (cache ongkir)
  Notification/
    Service/WhatsappService.php             # kirim pesan WhatsApp lewat WAHA (dipakai buat OTP verifikasi nomor)
  Review/
    Controller/ReviewController.php         # publik: submit ulasan (dari halaman tracking); admin: index/approve/toggleFeatured/destroy
    Service/ReviewService.php               # guard: cuma order fulfillment_status=delivered & belum pernah direview yang boleh submit
    Repository/ReviewRepository.php         # satu-satunya tempat query Eloquent ke Review
```
Domain `Shipping` sengaja tidak punya Controller — titik lokasi tujuan sekarang diambil dari peta (Leaflet + Nominatim, langsung dari browser di `Checkout.vue`), bukan dari endpoint proxy pencarian area Biteship yang dulu ada di sini. Domain ini SEKARANG punya Repository (dulu tidak, karena belum ada tabel sendiri) — sejak ada cache ongkir di `shipping_rate_quotes`.

Pembagian tanggung jawab:
- **Controller** — hanya urus HTTP in/out (request → panggil Service → response/Inertia::render). Tidak boleh ada query Eloquent atau logic bisnis langsung di sini.
- **Service** — semua business logic (validasi turunan, generate ID, default value, aturan bisnis lain).
- **Repository** — satu-satunya lapisan yang boleh menyentuh Eloquent/`App\Models\*` untuk domain itu.

Catatan:
- **Tidak semua domain butuh Repository.** Domain `Auth` sengaja tidak punya Repository karena tidak ada tabel database untuk admin — password admin cuma satu nilai di `config/nale.php` / env, bukan data yang perlu di-query. Domain `Payment` juga sengaja tidak punya Repository — tidak punya tabel sendiri, cuma memanggil API Midtrans dan mengembalikan array; yang boleh menulis ke tabel `orders` cuma `OrderRepository` (lewat `OrderService`), termasuk untuk kedua webhook (`WebhookController` ada di domain `Order`, bukan di `Payment`/`Shipping`, justru karena alasan ini). Jangan buat Repository kosong hanya demi konsistensi pola.
- **Repository/Service di sini adalah class konkret biasa, TIDAK pakai interface + binding di service container.** Cuma ada satu implementasi (SQLite) dan tidak ada rencana ganti-ganti implementasi, jadi interface di sini cuma seremoni tanpa manfaat — jangan ditambahkan.
- `app/Models/Product.php` tetap di lokasi standar Laravel (`app/Models`), bukan dipindah ke folder domain — cukup Repository yang membungkusnya.
- `app/Http/Middleware/AdminAuth.php` tetap di lokasi standar Laravel (dirujuk lewat FQCN penuh di `bootstrap/app.php`), tapi logic-nya didelegasikan ke `AdminAuthService::check()`.
- Ketika menambah domain baru, ikuti pola yang sama: `app/Domains/<NamaDomain>/{Controller,Service,Repository}` (skip layer yang memang tidak relevan, seperti contoh `Auth` di atas).

## Stack

- Laravel 11 + Inertia.js + Vue 3 + Tailwind CSS
- Database: **SQLite saja** (`database/database.sqlite`)
- **Tidak ada Pinia**, meskipun sekarang ada keranjang belanja lagi — keranjang sengaja dibuat pakai composable biasa (`resources/js/lib/cart.js`, `reactive()` module-scope singleton + `localStorage`), BUKAN Pinia. Pinia sudah pernah dihapus dulu; jangan ditambahkan lagi kecuali state-nya benar-benar butuh lebih dari satu singleton reactive.
- **`leaflet`** (npm) dipakai buat peta pemilihan titik lokasi di halaman Checkout — satu-satunya dependency JS baru yang ditambahkan sejak fitur checkout, geocoding-nya pakai Nominatim (gratis, tanpa API key) bukan Google Maps.
- Ponytail plugin aktif (mode `full`) — tulis kode seminim mungkin, jangan bikin abstraksi yang belum perlu

## Data produk

Kolom penting di tabel `products` (lihat `database/migrations/2024_01_01_000000_create_products_table.php` + `2024_01_02_000000_add_weight_to_products_table.php`):
- `id` — slug string, primary key, bukan auto-increment
- `shopee`, `toko` — URL langsung ke listing produk di Shopee/Tokopedia, diisi admin lewat panel admin (`/admin`), ditampilkan sebagai tombol beli sekunder di halaman detail produk publik
- `variants` (json array of `{name, img}`), `sizeCols`, `sizes` (json) — untuk galeri varian & tabel ukuran
- `weight` (integer, gram, default 200) — dasar hitung ongkir Biteship, diisi admin lewat form produk
- `stock` (integer, nullable) — `null` = tak terbatas/tidak dilacak (default). Diisi admin kalau mau stoknya beneran dijaga; otomatis berkurang tiap checkout, balik lagi kalau pesanan dibatalkan (lihat "Aturan penting").

## Checkout: keranjang, pembayaran & pengiriman

**Pembayaran aktif SAAT INI: manual** (transfer bank BCA atau QRIS statis), bukan payment gateway otomatis. Alasannya: menghindari proses KYC/verifikasi bisnis Midtrans yang belum bisa dipenuhi pemilik (belum usaha terdaftar). Alur lengkap: keranjang murni client (`resources/js/lib/cart.js`, tanpa call backend) → `/checkout` kumpulkan alamat (teks + titik lokasi dari peta Leaflet/OpenStreetMap — lihat catatan peta di bawah) + cek ongkir live (Biteship, pakai lat/lng) + kode diskon opsional + pilih metode bayar (`bank_transfer`/`qris`) → submit ke `OrderService::checkout()` (buat `Order`+`OrderItem`, re-harga semua item dari `Product`, **tidak** memanggil payment gateway) → buyer diarahkan ke `/pesanan/{tracking_token}` yang menampilkan info rekening/gambar QRIS + total yang harus ditransfer → buyer upload bukti bayar (`CheckoutController::uploadProof`, status jadi `menunggu_konfirmasi`) → **admin cek manual lalu klik "Tandai Lunas"** di `/admin/pesanan` (`OrderService::confirmPayment`, cuma update `payment_status`) → **admin klik terpisah "Atur Pengiriman Sekarang"** (`OrderService::arrangeShipment`, dulu namanya `retryPickup`) begitu barang sudah siap dikirim — **booking pickup ke Biteship SENGAJA TIDAK otomatis lagi begitu lunas** (beda dari versi awal fitur ini), supaya admin yang kontrol kapan kurir (termasuk kurir instan kayak Gojek/Grab yang langsung jalan begitu dibooking) benar-benar dipanggil → webhook Biteship (`/webhooks/biteship/{token}`) update status pengiriman → buyer pantau semua ini di halaman tracking yang sama.

**Titik lokasi tujuan pakai peta (Leaflet + OpenStreetMap tile, `npm install leaflet`), bukan pencarian area Biteship.** Di `Checkout.vue`: ketik "Alamat Lengkap" → di-geocode via Nominatim (`nominatim.openstreetmap.org/search`, gratis tanpa API key) → peta otomatis geser ke situ; buyer juga bisa klik/geser pin manual buat presisi (lalu direverse-geocode via Nominatim `/reverse` buat dapat label alamat). Hasil akhirnya `{lat, lng, label}` dikirim ke `/checkout/ongkir` dan disimpan di `orders.shipping_address`. Nominatim dipanggil langsung dari browser (bukan lewat backend) — cukup buat volume toko pribadi, tapi ingat kebijakan pemakaian Nominatim (jangan dipakai brutal/bulk) kalau nanti trafik naik jauh.

**PENTING — dua endpoint Biteship beda format koordinat, jangan disamakan:** endpoint cek ongkir (`/rates/couriers`) pakai field rata `destination_latitude`/`destination_longitude`, tapi endpoint booking pickup (`/orders`) pakai object bersarang `destination_coordinate: {latitude, longitude}` (juga `origin_coordinate` kalau origin pakai koordinat). Ini pernah bikin `bookPickup()` gagal terus dengan error "Destination needs to have either postal code, location id or coordinate" — sudah diperbaiki di `ShippingService::bookPickup()`, jangan diubah balik ke field rata.

**PENTING — origin WAJIB pakai koordinat (`BITESHIP_ORIGIN_LAT`/`BITESHIP_ORIGIN_LNG`), bukan cuma `origin_area_id`.** Kurir on-demand (Gojek/Grab Instant & Same Day) sama sekali tidak muncul di hasil cek ongkir kalau origin cuma dikirim sebagai `origin_area_id` — Biteship butuh titik koordinat presisi buat kurir jenis ini (motor/mobil hitung rute nyata, bukan area administratif). `BITESHIP_COURIERS` di `.env` sekarang termasuk kurir reguler tambahan (TIKI, Ninja, Lion, IDexpress, Pos Indonesia, Wahana, SAP) plus Gojek & Grab. Kalau nanti ganti/hapus `BITESHIP_ORIGIN_LAT`/`LNG`, kosongkan dulu tabel `shipping_rate_quotes` (cache lama gak otomatis ke-invalidate cuma karena config berubah).

Gambar QRIS diupload admin lewat `/admin/pesanan` (`OrderAdminController::uploadQris`), disimpan selalu sebagai `public/img/qris.png` (nama file tetap, ditimpa tiap upload baru) — sengaja tanpa kolom/tabel baru untuk ini. Rekening bank (`BANK_NAME`/`BANK_ACCOUNT_NUMBER`/`BANK_ACCOUNT_NAME` di `.env`, lewat `config/nale.php`) di-share ke semua halaman Inertia sebagai prop `bank`.

**Integrasi Midtrans (payment gateway otomatis) sudah dibangun lengkap dan teruji, TAPI SAAT INI DORMANT** — `PaymentService`, route `checkout.repay`, dan `webhooks.midtrans` masih ada dan berfungsi, cuma tidak dipanggil dari `OrderService::checkout()` lagi. Kalau nanti pemilik sudah punya usaha terdaftar dan mau balik ke pembayaran otomatis: sambungkan lagi `PaymentService::createTransaction()` di `OrderService::checkout()` (lihat komentar di method itu), tambahkan pilihan "Midtrans" di selector metode bayar `Checkout.vue`, dan pasang lagi Snap.js loader (sudah pernah ada, cek git history kalau perlu referensi).

**Biteship** (cek ongkir + booking pickup otomatis + tracking webhook — satu API buat tiga hal ini, makanya dipilih dibanding RajaOngkir) TETAP aktif dan otomatis penuh, tidak terpengaruh oleh perubahan metode bayar di atas. Kredensial di `.env` (`BITESHIP_*`) dan `config/services.php`. Sandbox/test key cukup untuk development penuh; key produksi baru dibutuhkan saat go-live.

**Cek ongkir Biteship di-cache di tabel `shipping_rate_quotes`** (`ShippingRateRepository`) — setiap panggilan API Biteship berbayar (potong saldo/poin), jadi `ShippingService::rates()` cari dulu quote yang sudah ada dalam radius ±1km dari titik lokasi yang diminta DAN berat barang yang sama (dibulatkan per 500g), umur maksimal 7 hari (`ShippingRateRepository::RADIUS_METERS`/`TTL_HOURS`) — kalau ketemu, langsung dipakai tanpa manggil Biteship lagi. Cache ini otomatis kepakai lintas buyer (bukan cuma per sesi/browser). Di frontend, `Checkout.vue` juga sengaja TIDAK auto-fetch ongkir tiap geser peta/ketik alamat (itu masih gratis, cuma manggil Nominatim) — cek ongkir baru jalan pas buyer klik tombol "Cek Ongkir", biar tidak boros baik di sisi cache maupun beban API.

Tabel: `orders` (`payment_status`: `pending` → `menunggu_konfirmasi` (setelah upload bukti) → `settlement` (setelah admin konfirmasi) — nilai `settlement` sengaja dipertahankan dari era Midtrans supaya semua pengecekan "sudah lunas?" di kode tetap konsisten; `payment_proof` = path bukti transfer/QRIS yang diupload buyer; `fulfillment_status` bebas dari Biteship; `discount_code`/`discount_amount` adalah snapshot bukan FK ke `discounts`), `order_items` (semua kolom snapshot harga/berat/nama saat order dibuat), `discounts` (kode diskon manual yang admin bikin ad hoc lewat halaman `/admin/pesanan`, bukan sistem kupon marketing — tidak ada limit pemakaian/kuota, cuma aktif/nonaktif + kedaluwarsa opsional).

Kalau nanti nambah fitur checkout baru (metode pembayaran lain, opsi pengiriman lain, dll), ikuti pola yang sama: harga/berat selalu di-generate ulang server-side dari `Product`, jangan pernah percaya angka uang yang dikirim client.

## Profil pelanggan tersimpan (verifikasi WhatsApp/OTP)

Situs ini **tanpa akun/login pembeli** (guest checkout), tapi ada fitur opsional "simpan data buat order berikutnya" supaya pembeli lama gak perlu isi ulang nama/alamat — kuncinya nomor WhatsApp, bukan email/password. Karena tidak ada password, verifikasi dilakukan lewat **kode OTP 6 digit dikirim ke WhatsApp** (biar orang lain gak bisa asal ketik nomor orang lain buat lihat data tersimpannya — ini keputusan sadar, dikonfirmasi user, karena situs awalnya didesain 100% tanpa friksi verifikasi apapun).

Alur di `Checkout.vue`: field **No. WhatsApp jadi input pertama** (bukan Nama Penerima) → tombol "Verifikasi" opsional (checkout biasa tanpa verifikasi tetap bisa jalan penuh, verifikasi cuma buka fitur simpan/auto-isi) → `OrderService::sendOtp()` generate kode & kirim via `WhatsappService` → buyer masukin kode → `OrderService::verifyOtp()` cek cocok, kalau nomor itu punya profil tersimpan (`Customer` di tabel `customers`) langsung dikembalikan dan auto-isi nama/email/alamat+peta di form → checkbox "Simpan data saya untuk order berikutnya" muncul (default aktif) → kalau dicentang, `OrderService::checkout()` panggil `saveProfileIfVerified()` yang **re-cek ke database** bahwa nomor itu memang baru diverifikasi (`OtpRepository::isRecentlyVerified`, window 30 menit) sebelum betulan nyimpen — jangan percaya flag `save_profile` dari client begitu saja.

**`OrderService::verifyOtp()` juga balikin `has_delivered_order` (boolean) + `orders` (semua pesanan milik nomor itu)** — dipakai bareng oleh dua tempat: (1) `Checkout.vue` nampilin banner "Selamat Datang Kembali!" kalau `has_delivered_order` true, (2) halaman publik **`/pesanan-saya`** (`Order/Lookup.vue`, link ada di nav header desktop & footer) — tempat buyer yang **kehilangan link tracking-nya** bisa cari lagi: masukin No. WhatsApp + OTP (reuse endpoint `checkout/kirim-otp`/`checkout/verifikasi-otp` yang sama), lalu semua pesanan lama nomor itu muncul dengan link ke masing-masing `/pesanan/{tracking_token}`. Ini sengaja dibuat SEBAGAI PENGGANTI login/akun (dikonfirmasi user) — situs tetap 100% guest checkout, tapi buyer tetap punya cara pulih akses ke riwayat pesanannya tanpa perlu daftar/inget password.

**Provider WhatsApp OTP SAAT INI: Fonnte (cloud, berbayar) — SEMENTARA**, bukan pilihan akhir. Rencana aslinya WAHA (WhatsApp HTTP API, self-hosted, gratis), tapi user belum sempat setup WAHA-nya, jadi `WhatsappService::send()` untuk sementara manggil Fonnte (`POST https://api.fonnte.com/send`, header `Authorization: <token>`, body form `target`+`message`, kredensial `FONNTE_TOKEN` di `.env`/`config('services.fonnte')`). **Begitu WAHA sudah jalan, sambungkan lagi `WhatsappService::send()` ke WAHA** — config-nya (`services.waha`, `WAHA_*` di `.env`) sengaja dibiarkan dormant, jangan dihapus.

Kalau/waktu balik ke WAHA: pakai engine `NOWEB` (`WHATSAPP_DEFAULT_ENGINE=NOWEB` di env container Docker-nya) — WAHA default pakai Chromium yang berat, bentrok sama batasan RAM STB 2GB; NOWEB jauh lebih ringan karena connect ke WhatsApp langsung lewat websocket (lib Baileys), tanpa browser sama sekali. Untuk ARM64 (STB Armbian), pakai image `devlikeapro/waha:arm`, bukan tag default. Endpoint yang dipakai nanti: `POST {WAHA_BASE_URL}/api/sendText` dengan header `X-Api-Key`, body `{session, chatId, text}` — `chatId` formatnya `<nomor internasional tanpa +>@c.us` (nomor lokal `0...` perlu diubah ke `62...`).

Tabel: `customers` (`phone` unique jadi kunci pencarian, `address` json sama bentuknya dengan `orders.shipping_address`), `otp_verifications` (satu baris aktif per nomor — kode baru menimpa yang lama, `expires_at` 5 menit, `verified_at` jadi bukti verifikasi yang dicek ulang di server saat mau simpan profil).

## Halaman admin pesanan (`/admin/pesanan`)

Ini satu-satunya halaman kontrol pengiriman/pesanan — tidak dipisah jadi halaman "Shipping" sendiri. Per pesanan (klik buat expand): item + harga, alamat, metode bayar, nomor resi (kalau sudah ada), link bukti pembayaran, tombol "Tandai Lunas" (cuma update status bayar), tombol **"Atur Pengiriman Sekarang"** (`OrderAdminController::arrangeShipment` → `PATCH /admin/pesanan/{order}/atur-pengiriman`, muncul begitu lunas & belum dibooking — ini juga yang dipakai kalau attempt sebelumnya gagal dan admin mau coba lagi, tidak ada tombol "retry" terpisah), dan tombol **"Cek Update Kurir"** yang lazy-load riwayat tracking dari Biteship (`OrderAdminController::tracking()` → `GET /admin/pesanan/{order}/tracking`) — sengaja tidak di-eager-load buat semua pesanan sekaligus di `index()`, biar tidak buka halaman = langsung nembak API Biteship berkali-kali. Halaman publik (Home/Katalog/Product/Cart/Checkout/Track) sengaja dijaga simpel murni buat pembeli — semua kontrol/pengelolaan taruh di `/admin/*`.

## Ulasan pembeli & banner promo beranda

Dua fitur "tampilan lebih hidup" yang ditambahkan supaya beranda tidak cuma katalog polos — keduanya dimoderasi/dikontrol admin, tidak ada yang publik-tanpa-approval:

- **Ulasan** (`reviews` table) — cuma bisa disubmit dari halaman tracking (`/pesanan/{token}`) kalau `fulfillment_status === 'delivered'`, dan cuma sekali per pesanan (`order_id` unique + guard di `ReviewService::submit()`). Ulasan baru default `approved=false` — **wajib di-approve admin dulu** (`/admin/ulasan`) sebelum bisa tampil di mana pun; admin juga pilih mana yang `featured` buat ditampilkan di section "Apa Kata Pembeli" beranda (`ProductController::home()` ambil lewat `ReviewService::featuredForHome()`, limit 6). Foto opsional ikut disimpan lewat `ProductService::storeUploadedPhoto()` (reuse cross-domain, sama kayak upload foto varian produk).
- **Banner diskon di beranda** — kolom `discounts.featured` (checkbox di form diskon `/admin/pesanan`, terpisah dari `active`). Beranda cuma nampilin SATU diskon yang `active=true` DAN `featured=true` DAN belum kedaluwarsa (`DiscountRepository::activeFeatured()`) — sengaja begitu karena kode diskon di sini kebanyakan ad hoc/personal (lihat bagian Checkout), jadi default-nya TIDAK publik; admin harus sadar centang `featured` kalau memang mau kode itu diiklankan ke semua pengunjung.

Trust section (ikon "Bahan Aman"/"Checkout Aman"/"Kirim ke Seluruh Indonesia"/"Bisa Dilacak") di beranda & footer, serta link Instagram di footer, sengaja **TIDAK dibuat bisa di-setting lewat halaman admin** — copy-nya konten brand yang jarang berubah, dan link Instagram cukup lewat `.env` (`INSTAGRAM_URL` → `config('nale.instagram')`), pola yang sama persis dengan info rekening bank/nomor WhatsApp yang juga cuma `.env`, bukan form admin. Kosongkan `INSTAGRAM_URL` untuk sembunyikan ikonnya dari footer.

## Testing

`php artisan test` (PHPUnit, `tests/Feature/*`) — cakupan sengaja dibatasi ke logic yang menyentuh **uang/keamanan** saja (bukan coverage penuh): harga item selalu di-generate ulang server-side dari `Product` (`CheckoutPriceIntegrityTest`), validasi kode diskon — expired/nonaktif/di bawah `min_subtotal` ditolak (`DiscountValidationTest`), stok berkurang atomik saat checkout & checkout ditolak kalau stok kurang (`StockDecrementTest`), token webhook Biteship yang salah ditolak `403` dan tidak mengubah order (`BiteshipWebhookTest`), pesanan basi di-expire + stok balik tanpa dobel-refund (`ExpireStaleOrdersTest`), ulasan cuma bisa disubmit untuk pesanan `delivered` & maksimal sekali per pesanan, dan ulasan yang belum di-approve tidak pernah nongol di beranda (`ReviewSubmissionTest`), banner diskon beranda cuma nongol kalau `active` DAN `featured` DAN belum kedaluwarsa (`FeaturedDiscountBannerTest`). Jalan pakai in-memory SQLite (`phpunit.xml`, `DB_DATABASE=:memory:`) — tidak pernah menyentuh `database/database.sqlite` asli. Kalau nambah logic baru yang menyentuh uang/keamanan (metode bayar lain, aturan diskon baru, dst), tambah satu test di sini juga.

## Repo

Repo publik personal di `github.com/Nktpamungkas/baju` (akun pribadi, bukan akun/org perusahaan tempat user bekerja).

## Git & Deploy

- **Jangan `git push` atas inisiatif sendiri.** User yang akan `git commit`/`push` sendiri (lewat terminal atau `./deploy.sh`). Kalau ada perubahan kode yang perlu di-deploy, cukup beri tahu user apa yang berubah dan biarkan mereka yang push — jangan push duluan tanpa diminta eksplisit.
- Deploy ke STB (`/opt/baju`, dijalankan via FrankenPHP + Cloudflare Tunnel) sudah otomatis lewat GitHub Actions (`.github/workflows/deploy.yml`) begitu ada push ke `main` — jadi `git push` biasa (lewat terminal atau GUI VSCode) sudah cukup, tidak perlu SSH manual lagi.
- `deploy.sh` di root project adalah shortcut manual (build + commit + push + SSH deploy sekaligus) kalau user mau trigger dari satu command tanpa nunggu CI — tapi ini juga harus dijalankan oleh user sendiri, bukan oleh Claude.
