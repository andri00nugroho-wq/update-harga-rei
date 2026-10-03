# Gold Price Sync — Raja Emas Indonesia

Sistem web untuk mengotomatisasi sinkronisasi data harga emas dari **Google Sheets ke desain Canva**.

Project ini dibuat sebagai solusi untuk membantu proses pembaruan harga emas harian pada desain publikasi Raja Emas Indonesia agar tidak perlu dilakukan secara manual.

---

## 1. Tentang Project

Tim Marketing Raja Emas Indonesia menggunakan data harga emas yang diperbarui secara berkala untuk kebutuhan publikasi.

Sebelum adanya sistem ini, proses pembaruan harga pada desain Canva dilakukan secara manual berdasarkan data dari Google Sheets.

Project ini mengotomatisasi proses tersebut dengan menghubungkan:

text
Google Sheets
      │
      ▼
   Laravel
      │
      ▼
Dashboard Preview
      │
      ▼
Sinkronisasi
      │
      ▼
   Canva API
      │
      ▼
Updated Canva Design


Sistem membaca data harga terbaru dari Google Sheets, menampilkannya pada dashboard, kemudian menjalankan proses sinkronisasi ke desain Canva.

---

# 2. Fitur Utama

## Google Sheets Integration

Sistem membaca data harga emas dari Google Spreadsheet secara dinamis.

Data yang digunakan pada spreadsheet terdiri dari:

* Karat
* Harga per gram
* Tanggal

## Dashboard

Dashboard menyediakan:

* Preview data harga terbaru
* Informasi status koneksi Canva
* Tombol sinkronisasi
* Status proses sinkronisasi
* Informasi hasil sinkronisasi

## Canva Integration

Sistem menggunakan Canva API untuk menjalankan proses sinkronisasi data ke desain Canva yang telah dikonfigurasi.

Hasil proses berupa desain Canva yang telah diperbarui menggunakan data harga dari Google Sheets.

---

# 3. Tech Stack

Project menggunakan:

* PHP
* Laravel
* Blade
* JavaScript
* CSS
* Google Sheets API
* Canva API
* SQLite
* Vite

---

# 4. Requirements

Environment yang diperlukan:

* PHP
* Composer
* Node.js
* npm
* SQLite
* Google Account
* Google Sheets API
* Canva Developer Account
* Canva API credentials

Project dikembangkan dan diuji menggunakan Laravel pada local environment.

---

# 5. Installation

Clone repository:

bash
git clone <GITHUB_REPOSITORY_URL>


Masuk ke folder project:

bash
cd harga-emas-canva


Install dependency PHP:

bash
composer install


Install dependency frontend:

bash
npm install


---

# 6. Environment Configuration

Copy file `.env.example` menjadi `.env`.

### Windows

cmd
copy .env.example .env


### Linux / macOS

bash
cp .env.example .env


Generate application key:

bash
php artisan key:generate


Kemudian buka file:

text
.env


dan isi konfigurasi Google Sheets serta Canva API.

---

# 7. Database Configuration

Project menggunakan SQLite untuk local development.

Pada `.env`:

env
DB_CONNECTION=sqlite


Pastikan database tersedia pada:

text
database/database.sqlite


Jika file belum tersedia pada Windows:

cmd
type nul > database/database.sqlite


Kemudian jalankan migration:

bash
php artisan migrate


---

# 8. Google Sheets Configuration

Google Spreadsheet digunakan sebagai sumber data harga emas.

Masukkan Spreadsheet ID ke dalam `.env`:

env
GOOGLE_SPREADSHEET_ID=


Masukkan nama tab spreadsheet:

env
GOOGLE_SHEET_NAME=


Contoh:

env
GOOGLE_SHEET_NAME=Harga Emas


## Struktur Data Spreadsheet

Spreadsheet yang digunakan memiliki struktur:

| Karat       |    Harga/gr | Tanggal         |
| ----------- | ----------: | --------------- |
| K24*        | Rp2.136.000 | 03 Oktober 2026 |
| K24 (99.5%) | Rp2.029.000 | 03 Oktober 2026 |
| K23         | Rp1.945.000 | 03 Oktober 2026 |
| K22         | Rp1.849.000 | 03 Oktober 2026 |
| K21         | Rp1.766.000 | 03 Oktober 2026 |
| K20         |         Rp5 | 03 Oktober 2026 |
| K19         | Rp1.597.000 | 03 Oktober 2026 |
| K18         | Rp1.514.000 | 03 Oktober 2026 |
| K17         | Rp1.434.000 | 03 Oktober 2026 |
| K16         |        Rp89 | 03 Oktober 2026 |
| K15         | Rp1.262.000 | 03 Oktober 2026 |
| K14         | Rp1.178.000 | 03 Oktober 2026 |
| K13         | Rp1.093.000 | 03 Oktober 2026 |
| K12         | Rp1.010.000 | 03 Oktober 2026 |
| K11         |   Rp926.000 | 03 Oktober 2026 |
| K10         |   Rp841.000 | 03 Oktober 2026 |
| K9          |   Rp759.000 | 03 Oktober 2026 |
| K8          |   Rp674.000 | 03 Oktober 2026 |
| K7          |   Rp589.000 | 03 Oktober 2026 |
| K6          |   Rp507.000 | 03 Oktober 2026 |
| K5          |   Rp423.000 | 03 Oktober 2026 |

> Data harga pada spreadsheet dapat berubah mengikuti pembaruan harga yang digunakan dalam aplikasi.

## Alur Google Sheets

text
Google Sheets
      │
      │ Google Sheets API
      ▼
   Laravel
      │
      ├──────────────► Dashboard Preview
      │
      ▼
  Sinkronisasi
      │
      ▼
   Canva API


Data tidak ditulis secara hard-code pada dashboard. Aplikasi mengambil data dari Google Sheets sebagai sumber data.

---

# 9. Canva API Configuration

Project menggunakan Canva API untuk menjalankan proses sinkronisasi.

Konfigurasi Canva pada `.env`:

env
CANVA_CLIENT_ID=
CANVA_CLIENT_SECRET=
CANVA_REDIRECT_URI=http://localhost:8000/canva/callback
CANVA_DESIGN_ID=


## Redirect URI

Untuk local development digunakan:

text
http://localhost:8000/canva/callback


Redirect URI harus sesuai dengan konfigurasi aplikasi pada Canva Developer.

## Design ID

ID desain Canva yang digunakan untuk proses sinkronisasi dikonfigurasi melalui:

env
CANVA_DESIGN_ID=


Credential Canva tidak disimpan di repository.

---

# 10. Menjalankan Aplikasi

Jalankan server Laravel:

bash
php artisan serve


Aplikasi dapat diakses melalui:

text
http://localhost:8000


Kemudian jalankan Vite:

bash
npm run dev


Laravel dan Vite dapat dijalankan pada terminal yang berbeda.

---

# 11. Alur Penggunaan

## 1. Buka Google Sheets

Pastikan data harga emas terbaru tersedia pada spreadsheet.

## 2. Buka Dashboard

Akses:

text
http://localhost:8000


## 3. Periksa Data

Dashboard menampilkan data harga yang diambil dari Google Sheets.

## 4. Klik Sinkronisasi

Klik tombol:

text
Sinkronisasi


Sistem akan menjalankan proses sinkronisasi melalui backend Laravel.

## 5. Tunggu Proses

Dashboard menampilkan status proses selama sinkronisasi berlangsung.

## 6. Periksa Hasil

Setelah proses berhasil, hasil sinkronisasi ditampilkan pada dashboard.

Desain Canva kemudian dapat diperiksa untuk memastikan data harga telah diperbarui.

---

# 12. Arsitektur Sistem

text
                ┌──────────────────┐
                │   Google Sheets  │
                │   Data Harga     │
                └────────┬─────────┘
                         │
                         │ Google Sheets API
                         ▼
                ┌──────────────────┐
                │     Laravel      │
                │     Backend      │
                └────────┬─────────┘
                         │
              ┌──────────┴──────────┐
              │                     │
              ▼                     ▼
      ┌──────────────┐      ┌──────────────┐
      │  Dashboard   │      │   Canva API  │
      │    Preview   │      │ Synchronize  │
      └──────────────┘      └──────┬───────┘
                                   │
                                   ▼
                           ┌──────────────┐
                           │ Canva Design │
                           │   Updated    │
                           └──────────────┘


### Frontend

Frontend menangani:

* Tampilan dashboard
* Preview data
* Tombol sinkronisasi
* Loading state
* Notifikasi proses
* Tampilan hasil

### Backend

Laravel menangani:

* Pengambilan data Google Sheets
* Integrasi Canva API
* Proses sinkronisasi
* Status dan response API
* Error handling

---

# 13. Canva API Limitation & Approach

Canva API memiliki batasan terhadap jenis elemen desain tertentu, terutama ketika berhubungan dengan tabel kompleks.

Karena itu, proses sinkronisasi tidak bergantung pada manipulasi tabel Canva secara bebas seperti pada aplikasi spreadsheet.

Pendekatan yang digunakan adalah memetakan data harga ke elemen/variabel yang dapat diproses oleh Canva API.

Dengan pendekatan tersebut, data harga dari Google Sheets dapat digunakan untuk memperbarui bagian harga pada template Canva.

Alur implementasi:

text
Google Sheets
      ↓
Laravel
      ↓
Canva API
      ↓
Updated Canva Design


Pendekatan ini juga membuat sumber data dan desain tetap terpisah sehingga data harga dapat diperbarui melalui spreadsheet tanpa memasukkan angka secara manual satu per satu.

---

# 14. Error Handling

Aplikasi memberikan feedback kepada pengguna selama proses berjalan.

Kondisi yang ditangani meliputi:

* Data Google Sheets tidak tersedia
* Koneksi API gagal
* Canva belum terhubung
* Konfigurasi template tidak sesuai
* Proses sinkronisasi gagal
* Proses sinkronisasi berhasil

Dashboard memberikan informasi status agar pengguna dapat mengetahui kondisi proses sinkronisasi.

---

# 15. Environment Variables

File `.env.example` disediakan sebagai template konfigurasi.

Variable utama:

env
GOOGLE_SPREADSHEET_ID=
GOOGLE_SHEET_NAME=

CANVA_CLIENT_ID=
CANVA_CLIENT_SECRET=
CANVA_REDIRECT_URI=
CANVA_DESIGN_ID=


Credential asli hanya disimpan pada:

text
.env


dan tidak dimasukkan ke repository.

---

# 16. Security

Informasi sensitif tidak disimpan di source code repository.

Credential yang harus dijaga antara lain:

* Canva Client Secret
* Google credentials
* API credentials
* Laravel APP_KEY

File `.env` tidak boleh di-upload ke GitHub/GitLab.

Sebaliknya, repository menyediakan:

text
.env.example


sebagai contoh konfigurasi tanpa nilai rahasia.

---

# 17. Project Structure

Struktur utama project:

text
harga-emas-canva/
│
├── app/
│   └── Http/
│       └── Controllers/
│           ├── CanvaController.php
│           ├── DashboardController.php
│           ├── PriceController.php
│           └── SyncController.php
│
├── database/
│   ├── migrations/
│   └── database.sqlite
│
├── resources/
│   ├── css/
│   │   └── dashboard.css
│   │
│   ├── js/
│   │   └── dashboard.js
│   │
│   └── views/
│
├── routes/
│   └── web.php
│
├── .env.example
├── composer.json
├── package.json
└── README.md


---

# 18. Testing

Skenario pengujian utama:

text
1. Buka Google Sheets
2. Pastikan data harga tersedia
3. Buka aplikasi Laravel
4. Periksa preview harga
5. Klik "Sinkronisasi"
6. Periksa status loading
7. Tunggu proses selesai
8. Periksa notifikasi hasil
9. Buka hasil desain Canva
10. Bandingkan data Canva dengan Google Sheets


### Expected Result

Data harga dari Google Sheets berhasil dibaca oleh aplikasi dan digunakan dalam proses sinkronisasi ke desain Canva.

Data pada desain Canva harus sesuai dengan data yang digunakan pada spreadsheet saat proses sinkronisasi.

---

# 19. Video Demo

Video demo mengikuti requirement ujian dengan durasi maksimal **3–5 menit**.

Alur demo:

text
Google Sheets
      ↓
Menunjukkan data harga
      ↓
Dashboard
      ↓
Preview data
      ↓
Klik Sinkronisasi
      ↓
Loading
      ↓
Success
      ↓
Hasil Canva


Video memperlihatkan proses end-to-end dari sumber data hingga hasil desain.

---

# 20. Deliverables

Sesuai requirement ujian, project menyediakan:

### Source Code

Repository:

text
<GITHUB_REPOSITORY_URL>


### Documentation

text
README.md


Dokumentasi mencakup:

* Installation
* Environment configuration
* Google Sheets configuration
* Canva configuration
* Cara menjalankan aplikasi
* Cara melakukan sinkronisasi
* Arsitektur sistem
* Pendekatan Canva API

### Environment Example

text
.env.example


File tersebut tidak berisi credential rahasia.

### Video Demo

Video demo menunjukkan proses:

text
Google Sheets → Laravel → Sinkronisasi → Canva


---

# 21. Optional: Sync History

Penyimpanan riwayat sinkronisasi menggunakan database merupakan fitur tambahan/value-plus.

Jika diimplementasikan, informasi yang dapat dicatat antara lain:

* Waktu sinkronisasi
* Status berhasil/gagal
* Result link
* Informasi error



---

# 22. Project Objective

Tujuan utama project adalah membuat proses pembaruan harga emas menjadi lebih terstruktur dengan menghubungkan sumber data Google Sheets dengan desain Canva melalui aplikasi web.

Dengan sistem ini, pengguna dapat:

1. Mengambil harga terbaru dari Google Sheets.
2. Melihat data melalui dashboard.
3. Menjalankan sinkronisasi melalui satu tombol.
4. Mengirim data ke Canva melalui API.
5. Memeriksa desain Canva yang telah diperbarui.

---

## License

Project ini dibuat untuk keperluan **Ujian Praktik Fullstack Developer — Raja Emas Indonesia**.

