# Cerdas Bersama PTA Papua Barat

Aplikasi kuis interaktif berbahasa Indonesia: host membuat kuis, peserta masuk dengan PIN, menjawab bersama, lalu melihat podium dan hasil yang tersimpan.

## Versi dan dependency — baca sebelum instalasi

Implementasi ini diuji dengan **PHP 7.4.33**, **Laravel 7.30.7**, **Composer 2.8.9**, dan **MariaDB 10.4.27** di XAMPP Windows. Target database MySQL 5.7+/8 dengan InnoDB; MariaDB XAMPP kompatibel untuk MVP ini. Gunakan PHP 7.4; kompatibilitas PHP 8 tidak dijanjikan. Extension: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, pdo_mysql, Tokenizer, XML, GD; pdo_sqlite diperlukan untuk test default. `composer.lock` mengunci dependency, termasuk PHPUnit 9.6.38. Bootstrap 5.3.3 dipakai sebagai CSS lokal; frontend adalah Blade dan JavaScript native, tanpa npm install/build.

**Status pemeliharaan:** Laravel 7 berhenti menerima perbaikan keamanan pada 3 Maret 2021 ([kebijakan resmi](https://laravel.com/docs/7.x/releases)). PHP 7.4 juga merupakan runtime lama. `composer audit --format=summary` pada environment pengerjaan melaporkan **16 advisory pada 3 paket**. MVP ini untuk pengembangan/lingkungan terkontrol; jangan menyebutnya siap produksi publik. Rencanakan migrasi framework dan PHP, audit ulang dependency, dan uji regresi sebelum publikasi. APP_DEBUG=false secara default; jangan membuka Ignition/debug ke jaringan publik.

## Menjalankan pada workspace ini

Database `quiz_arena` sudah dibuat dan diisi tiga kuis contoh. Development server berjalan di [http://127.0.0.1:8000](http://127.0.0.1:8000). Bila server berhenti, dari `C:\xampp\htdocs\quiz` jalankan:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Akun contoh lokal: **host@quizarena.test** / **QuizArena123!**. Akun ini dibuat hanya oleh seeder; ubah/hapus sebelum memakai data nyata. Akun host tambahan dibuat melalui perintah `php artisan host:create`. Pendaftaran publik dinonaktifkan. Password demo bukan credential production.

## Instalasi baru

1. Aktifkan MySQL pada XAMPP dan pastikan versi PHP CLI sesuai.
2. Jalankan dari root proyek:

```powershell
php -v
composer install
Copy-Item .env.example .env
php artisan key:generate
```

3. Buat database kosong khusus aplikasi (contoh melalui phpMyAdmin atau MySQL CLI):

```sql
CREATE DATABASE quiz_arena CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

4. Sesuaikan `.env` dengan konfigurasi lokal:

```dotenv
APP_NAME="Cerdas Bersama PTA Papua Barat"
APP_ENV=local
APP_DEBUG=false
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=quiz_arena
DB_USERNAME=root
DB_PASSWORD=
SESSION_DRIVER=file
CACHE_DRIVER=file
SESSION_SECURE_COOKIE=false
```

Root tanpa password hanya contoh XAMPP lokal. Gunakan user database khusus dengan hak terbatas pada environment lain. `.env` dan APP_KEY tidak boleh masuk Git.

5. Jalankan migration dan seeder:

```powershell
composer dump-autoload
php artisan migrate --seed
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000
```

Seeder idempotent untuk akun dan judul contoh, tidak menghapus data yang ada. Jika symlink storage gagal di Windows, aktifkan Developer Mode atau jalankan terminal dengan hak yang memungkinkan pembuatan symlink. Upload disimpan di `storage/app/public`; link `public/storage` wajib dapat diakses.

Untuk Apache, arahkan VirtualHost **DocumentRoot ke folder `public`**, aktifkan mod_rewrite dan AllowOverride All. Jangan jadikan root repository sebagai document root karena mengandung `.env` dan source. Sementara, `http://localhost/quiz/public` dapat digunakan di XAMPP; APP_URL harus disesuaikan. Pilih satu hostname secara konsisten, sebab cookie `localhost` berbeda dengan `127.0.0.1`.

## Mencoba satu host dan beberapa peserta

1. Browser biasa: buka aplikasi, masuk sebagai host, pilih **Mainkan** pada Jelajah Nusantara. Lobby menampilkan PIN 6 digit. Kuis Draft harus dibuka di editor dan disimpan dengan **Siap dimainkan** terlebih dahulu.
2. Browser lain atau profil terpisah: buka `/gabung`, isi PIN dan nama panggilan, misalnya Alya. Gunakan browser/profil lain lagi untuk Bima. Beberapa jendela Incognito pada browser yang sama bisa berbagi cookie; gunakan profil/browser berbeda untuk identitas independen.
3. Host melihat peserta masuk otomatis. Klik **Mulai permainan**; peserta menjawab sekali pada tombol besar berwarna dan berlabel A–D.
4. Setelah semua menjawab atau waktu berakhir, jawaban benar dan peringkat muncul. Host menekan **Pertanyaan berikutnya**. Setelah soal terakhir klik **Tampilkan podium**.
5. Refresh host dan peserta saat lobby/soal berlangsung: identitas dan kontrol tetap tersedia pada browser yang sama. Peserta baru ditolak sesudah start. Uji nama yang sama, PIN salah, atau jawaban terlambat untuk melihat pesan kesalahan.
6. Buka **Riwayat permainan → Lihat hasil** untuk skor dan detail jawaban setiap peserta. **Akhiri permainan** dapat dipakai lebih awal; jawaban yang sudah diterima tetap dihitung.

Untuk smartphone pada Wi-Fi yang sama, jalankan `php artisan serve --host=0.0.0.0 --port=8000`, ubah APP_URL ke IP LAN host misalnya `http://192.168.1.10:8000`, dan buka alamat itu dari semua perangkat. Izinkan port pada firewall hanya jaringan privat. `127.0.0.1` pada ponsel menunjuk ponsel itu sendiri. Ini tetap server pengembangan untuk jaringan terkontrol.

## Arsitektur, database, dan aturan

Lihat [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) untuk ERD, tabel/index/FK, mesin status, transaksi/lock, snapshot, token, asumsi, rumus skor, dan seri. Identitas visual dan pemilik komponen tercatat dalam DESIGN.md serta UX-CONTRACT.md.

Struktur penting:

```text
app/Http/Controllers/     AuthController, QuizController, GameController
app/Http/Requests/        AuthRequest, QuizRequest, JoinRequest, AnswerRequest
app/Http/Middleware/      ArenaThrottle + middleware Laravel
app/Policies/             QuizPolicy, GameSessionPolicy
app/Services/             GameService, ScoreService
app/                     User, Quiz, Question, Choice, GameSession,
                         SessionQuestion, Participant, Answer
database/migrations/      skema aplikasi + constraint
database/seeds/           QuizArenaSeeder, DatabaseSeeder
resources/views/          layout, landing, auth, dashboard, editor,
                         join, game, history, result
public/css/app.css        token dan stylesheet bersama
public/js/                app.js, editor.js, game.js
public/images/            ilustrasi orisinal + favicon
public/fonts/             font lokal
routes/web.php            route dan batas akses
tests/                    unit/feature test
```

Skor benar = `round(1000 - 500 × elapsed_ms / duration_ms)`, dibatasi 500–1000; salah/tidak menjawab 0. Seri: total poin → jumlah benar → waktu kumulatif jawaban benar lebih cepat → urutan bergabung. Server menentukan semua waktu/skor. Kunci jawaban tidak dikirim selama pertanyaan aktif.

## Karakter dan efek suara

Peserta memilih karakter di halaman **Gabung permainan** sebelum mengirim PIN/nama: Nala (rubah), Bimo (robot), Kiko (katak), Luna (kelinci), Oki (gurita), atau Momo (beruang). Karakter tampil di lobby, identitas peserta saat menjawab, leaderboard, podium, dan hasil tersimpan. Pilihan tetap sama setelah refresh. Beberapa peserta boleh memakai karakter sama. Data lama otomatis mendapat Nala melalui migration tambahan.

Untuk checkout yang sudah terpasang, jalankan `php artisan migrate` setelah update, lalu refresh halaman. Katalog karakter ada di `config/characters.php`, aset PNG transparan di `public/images/characters`, dan tampilan bersama di `public/css/characters.css`.

Tekan **Aktifkan suara** di halaman gabung atau lobby. Browser memerlukan interaksi pengguna sebelum mengizinkan audio; setelah refresh, ketuk kontrol itu lagi jika belum aktif. Tombol berubah menjadi **Suara aktif** dan dapat ditekan untuk mematikan efek. Slider mengatur volume, dan preferensi disimpan pada browser yang sama. Efek mencakup pemilihan karakter, peserta masuk (host), soal dimulai, lima detik terakhir, jawaban terkirim, hasil benar/salah, dan penutupan permainan. Semua disintesis lokal dengan Web Audio API tanpa file audio/CDN. Efek tidak berulang; tab tersembunyi tidak memutar efek dan refresh tidak mengulang hasil sebelumnya. Musik latar memiliki kontrol tersendiri, dijelaskan di bagian berikut.

## Musik latar dan identitas aplikasi

Nama tampilan aplikasi adalah **Cerdas Bersama PTA Papua Barat**, termasuk logo header/sidebar, judul tab, footer, dan pesan akun. Logo khusus memadukan buku, dua pelajar, dan cenderawasih beraksen emas. PNG transparan asli berada di `public/images/cerdas-bersama-logo-original-v1.png`, sedangkan versi header yang ringan adalah `public/images/cerdas-bersama-logo-v1.png`. Partial `resources/views/partials/brand.blade.php` menerapkannya pada semua halaman. Ukuran responsif diatur pada `public/css/brand-music.css`.

Untuk menerapkan nama baru pada server yang sudah terpasang, atur `APP_NAME="Cerdas Bersama PTA Papua Barat"` di `.env`, unggah kode terbaru, lalu jalankan `php artisan config:clear` dan `php artisan view:clear`.

Akun host tambahan **Admin PTA Papua Barat** dibuat di database lokal dengan email login **admin@ptapapuabarat.test**. Alamat ini merupakan identitas login contoh. Pada database server terpisah, jalankan `php artisan host:create admin@ptapapuabarat.test`. Perintah membuat kata sandi acak baru dan menampilkannya sekali di terminal. Kata sandi disimpan sebagai hash. Email yang sudah terdaftar ditolak. Hak akun mengikuti aturan host dan kepemilikan kuis yang berlaku.

Musik khusus **Langkah Ceria** adalah komposisi instrumental orisinal 32 detik: melodi plucked keys, akor lembut, bass dan perkusi ringan. File WAV lokal `public/audio/pta-pabar-theme.wav` diputar berulang selama soal aktif. Saat hasil muncul musik dijeda, soal berikutnya melanjutkan posisi sebelumnya, dan selesai permainan menghentikannya. Musik juga berhenti saat tab tersembunyi, meninggalkan halaman, atau waktu soal habis.

Di lobby, tekan **Aktifkan musik** atau kontrol audio sekali untuk memberi izin audio browser. **Musik siap** berarti musik akan dimulai ketika host membuka soal; **Musik aktif** berarti sedang diputar. Musik memiliki mute dan slider sendiri, terpisah dari efek suara. Menonaktifkan efek suara tetap memungkinkan musik bermain dan sebaliknya. Preferensi disimpan pada browser. Jika file musik gagal dimuat, gunakan **Coba musik lagi**; kuis tetap dapat dimainkan. Efek jawaban mengecilkan volume musik sesaat supaya terdengar jelas.

Komposisi dibuat tanpa sampel/rekaman pihak ketiga dan dapat dibangun ulang dengan `npm run music:build` (Node 20+, tanpa paket npm). Kode partitur/sintesis ada di `scripts/generate-music.cjs`; kredit ada di `public/audio/CREDITS.txt`. Tidak ada layanan audio berbayar, streaming, atau proses musik di server saat aplikasi dijalankan.

## Poin sementara setelah setiap soal

Ketika semua peserta sudah menjawab atau waktu habis, host dan peserta otomatis beralih ke layar **Peringkat sementara** untuk soal perantara. Soal dan pilihan jawabannya digantikan daftar peserta, tambahan poin dari soal ini, total poin, dan penanda **Naik/Turun/Posisi tetap**. Jawaban benar tetap tersedia sebagai referensi singkat. Host melanjutkan dengan **Pertanyaan berikutnya**.

Angka total menghitung naik dari skor sebelumnya. Peserta yang berubah posisi bergerak ke slot barunya, disertai efek angka/coin dan suara naik atau turun untuk peserta tersebut. Poin dan peringkat sebelumnya dihitung server dari jawaban tersimpan dengan aturan seri yang sama; jawaban salah/tidak menjawab tetap mendapat 0, bukan pengurangan skor. Peringkat turun jika peserta lain melampaui total/tie-break peserta itu.

Polling dan refresh tidak mengulang animasi/suaranya. Refresh menampilkan hasil akhir soal perantara secara langsung. Preferensi reduced-motion menonaktifkan perpindahan/angka bergerak. Mute/volume efek yang sudah ada juga mengatur suara peringkat.

Setelah **soal terakhir**, semua skor/peringkat ditahan dan peserta menunggu host menekan **Lihat hasil podium**. Tirai panggung terbuka, lalu notifikasi **hitung mundur 5→4→3→2→1** mendahului juara 3. Host menekan **Juara selanjutnya** untuk memulai hitung mundur lima detik sebelum juara 2, lalu sekali lagi sebelum juara 1. Setiap angka mendapat bunyi singkat, kemudian fanfare juara berbunyi saat peserta muncul. Juara pertama mendapat mahkota, halo, konfeti dan fanfare khusus. Peringkat lengkap baru tampil setelah pengumuman juara pertama. Jika hanya dua peserta, urutannya 2→1; satu peserta mendapat hitung mundur sebelum juara 1. Refresh melanjutkan sisa hitung mundur dari waktu server. Tahapan tersimpan dan disinkronkan server. Jalankan `php artisan migrate` untuk kolom podium tambahan (migration sudah diterapkan pada aplikasi lokal).

## Pemeriksaan

```powershell
php vendor/phpunit/phpunit/phpunit
php vendor/phpunit/phpunit/phpunit --configuration phpunit.mysql.xml
php artisan view:cache
php artisan route:list
node --check public/js/app.js
node --check public/js/editor.js
node --check public/js/game.js
npm run check
npm run test:js
composer audit
```

Test default memakai SQLite `:memory:` dan tidak mengubah database aplikasi. Feature test meliputi satu permainan lengkap, isolasi pemilik, snapshot tetap setelah edit/hapus, refresh peserta, duplicate/late answer, stale version, seri, validasi editor, upload, auth, rate limiter, duplikasi kuis, serta pilihan/persistensi karakter. Test JavaScript memakai Node 20+ tanpa dependency npm tambahan: memeriksa larangan autoplay, mute, volume nol, tab tersembunyi, penyimpanan preferensi, dan fallback audio. Test SQLite membuktikan aturan aplikasi dan constraint dasar, bukan perilaku lock InnoDB saat beban paralel.

Untuk suite MySQL, buat database kosong **quiz_arena_test** terlebih dahulu; `phpunit.mysql.xml` secara eksplisit mengarah ke database itu dan migration test akan mengosongkannya. Jangan simpan data penting di database test. Untuk bukti konkurensi proses terpisah, buat database kosong **quiz_arena_race_test**, lalu jalankan `php tests/mysql-race.php`. Skrip ini hanya memakai nama database test tetap tersebut, membuat fixture sementara, dan menghapus fixture setelah selesai. Dua proses bersamaan mengirim jawaban sama, lalu dua proses mengirim next dengan versi sama: tepat satu diterima pada tiap pasangan, satu jawaban tersimpan, dan posisi soal maju tepat sekali.

Browser QA memakai Playwright Chromium karena plugin Browser tidak tersedia. Verifikasi desktop 1440×1000 dan ponsel 390×844: login, daftar kuis, editor, gabung, pertanyaan, hasil, navigasi dan overflow. Rincian hasil aktual dilaporkan dalam handoff, bukan diasumsikan dari build.

## Operasional dan batas MVP

- Polling 1,5 detik; berhenti saat tab tersembunyi/sesi selesai dan retry 5 detik saat gagal. Tidak ada scheduler/queue yang wajib berjalan.
- Batas kuis 50 soal, pilihan 2–4, waktu 5–120 detik, gambar JPG/PNG/WebP 2MB. Draft tetap harus valid. Tidak ada autosave, impor soal, editor kolaboratif, atau socket real-time.
- Snapshot mempertahankan soal, pilihan, jawaban benar dan path gambar. Media yang masih digunakan riwayat tidak dihapus. Sediakan pemeliharaan media yatim untuk penggunaan jangka panjang.
- Session file/caching file cocok untuk satu server. Gunakan Redis/database session/cache bersama untuk scale-out dan rate limiter terdistribusi; load test sebelum menetapkan kapasitas. Polling mengunci satu sesi singkat, sehingga throughput grup besar terbatas.
- Tidak ada pemulihan peserta setelah cookie hilang, email verification/reset password, atau auto-expire lobby. Host dapat melanjutkan/menutup sesi aktif dari dasbor.
- Untuk lingkungan berikutnya: HTTPS + SESSION_SECURE_COOKIE=true, APP_ENV=production, APP_DEBUG=false, user DB khusus, backup teruji, pembatasan ukuran request server, patch/audit dependency dan migrasi runtime. Jalankan `php artisan config:cache` setelah konfigurasi benar. Jangan memakai akun contoh untuk deployment.
- Aplikasi ini belum dipublikasikan ke hosting eksternal. Backend PHP/MySQL dijalankan lokal, bukan Worker/static hosting.

## Aset dan lisensi

Ilustrasi dan logo dibuat khusus untuk Cerdas Bersama PTA Papua Barat. Logo utama merupakan aset PNG transparan orisinal; ikon kecil/favicon dibuat sebagai SVG/Blade. Musik Langkah Ceria disusun dan disintesis khusus aplikasi ini, tanpa rekaman/sampel pihak ketiga. Bootstrap MIT, Outfit dan Plus Jakarta Sans SIL Open Font License; font disajikan sendiri agar halaman tidak bergantung pada CDN. Lihat `public/vendor/LICENSES.txt`.
