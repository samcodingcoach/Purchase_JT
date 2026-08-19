# PRD + Vibe Coding Specification
# Aplikasi Purchasing Pabrik / Bengkel Las & Bubut Kapal
# Scope V1: Request Order (RO)

## 1. TUJUAN

Bangun aplikasi web internal untuk proses purchasing perusahaan pabrik/bengkel las dan bubut kapal.

Untuk V1, implementasi DIBATASI sampai modul **Request Order (RO)**.

Jangan membuat modul PO, Receiving, Invoice, Retur, Pembayaran, atau Accounting secara penuh pada tahap ini. Namun struktur kode/database harus disiapkan agar modul tersebut mudah ditambahkan pada fase berikutnya.

Workflow V1:

Mekanik
→ Request Order
→ Logistik
→ Ready for PO

RO yang sudah Ready for PO nantinya akan menjadi sumber data untuk Purchase Order.

---

# 2. TEKNOLOGI WAJIB

Backend:

- PHP 8.x
- mysqli
- MariaDB
- PHP native, tanpa Laravel
- Jangan gunakan PDO
- Jangan gunakan ORM

Frontend:

- HTML5
- Bootstrap 5
- JavaScript vanilla
- CSS custom di folder styles/
- Gunakan Fetch API untuk komunikasi ke API

Authentication:

- PHP Session
- password_hash()
- password_verify()
- API menggunakan session + Bearer Token
- Jangan menyimpan password plaintext
- Jangan menggunakan JWT untuk V1 kecuali benar-benar diperlukan; gunakan token session/API yang disimpan server-side.

Database:

- MariaDB
- Database: jaya_teknis
- Host: 127.0.0.1
- User: matos
- Password: 1234

---

# 3. STRUKTUR FOLDER

Gunakan struktur:

root/
│
├── admin/
│   ├── index.php
│   ├── login.php
│   ├── dashboard.php
│   │
│   ├── css/
│   ├── js/
│   ├── pages/
│   │   ├── request_order/
│   │   │   ├── index.php
│   │   │   ├── create.php
│   │   │   └── detail.php
│   │   ├── barang/
│   │   ├── vendor/
│   │   ├── site/
│   │   └── user/
│   │
│   ├── components/
│   │   ├── header.php
│   │   ├── sidebar.php
│   │   ├── navbar.php
│   │   ├── footer.php
│   │   └── alert.php
│   │
│   └── uploads/
│
├── api/
│   ├── auth/
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── me.php
│   │
│   ├── request_order/
│   │   ├── list.php
│   │   ├── get.php
│   │   ├── create.php
│   │   ├── update.php
│   │   ├── submit.php
│   │   ├── process.php
│   │   └── cancel.php
│   │
│   ├── master/
│   │   ├── barang.php
│   │   ├── vendor.php
│   │   ├── site.php
│   │   └── karyawan.php
│   │
│   └── middleware/
│       └── auth.php
│
├── config/
│   ├── koneksi.php
│   ├── session.php
│   └── config.php
│
├── images/
│
├── styles/
│   ├── app.css
│   └── responsive.css
│
└── database/
    └── schema_ro.sql

Boleh menambahkan folder/helper jika memang diperlukan, tetapi jangan mengubah struktur utama tanpa alasan.

---

# 4. DATABASE CONNECTION

File:

config/koneksi.php

Gunakan mysqli.

Konfigurasi:

Host:
127.0.0.1

Database:
jaya_teknis

Username:
matos

Password:
1234

Gunakan:

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

Pastikan charset:

utf8mb4

Jangan menggunakan PDO.

Jangan membuat koneksi database berulang-ulang di setiap file. Semua endpoint menggunakan koneksi dari config/koneksi.php.

---

# 5. SECURITY

## Password

Password harus dibuat dengan:

password_hash($password, PASSWORD_DEFAULT)

Verifikasi:

password_verify($password, $hash)

Tidak boleh:

- MD5
- SHA1
- plaintext password

## Session

Login menggunakan PHP Session.

Setelah login simpan minimal:

$_SESSION['user_id']
$_SESSION['username']
$_SESSION['nama']
$_SESSION['role']

Gunakan session_regenerate_id(true) setelah login berhasil.

## API Authentication

Semua endpoint API selain login harus melalui middleware.

Client harus mengirim:

Authorization: Bearer {token}

Token harus diverifikasi server-side.

Token dapat disimpan di session atau tabel session/token server-side.

Jangan percaya user_id yang dikirim dari frontend.

Identitas user harus diambil dari session/token yang sudah tervalidasi.

## SQL Injection

Semua query dengan input user wajib menggunakan prepared statement mysqli.

Jangan melakukan:

$sql = "SELECT ... WHERE id = ".$_GET['id'];

Gunakan prepared statement.

## Output

API mengembalikan JSON.

Contoh sukses:

{
  "success": true,
  "message": "Request Order berhasil dibuat",
  "data": {}
}

Contoh error:

{
  "success": false,
  "message": "Data tidak valid"
}

HTTP status harus sesuai:

200 sukses
201 created
400 bad request
401 unauthorized
403 forbidden
404 not found
422 validation error
500 server error

---

# 6. ROLE

Untuk V1 gunakan role:

1. ADMIN
2. MEKANIK
3. LOGISTIK
4. PURCHASING
5. MANAGER

Walaupun PO belum dibuat, role PURCHASING dan MANAGER tetap disiapkan.

## Hak akses RO

MEKANIK:

- Create RO
- Edit RO miliknya selama DRAFT
- Submit RO
- Melihat RO miliknya
- Melihat status RO

LOGISTIK:

- Melihat RO yang SUBMITTED
- Memproses RO
- Memverifikasi barang
- Memverifikasi vendor
- Mengubah data yang memang menjadi kewenangan logistik
- Mengubah status menjadi READY_FOR_PO

PURCHASING:

- Melihat RO READY_FOR_PO
- Melihat detail RO
- Belum membuat PO pada V1
- Hanya monitoring

MANAGER:

- Monitoring RO
- Belum melakukan approval RO pada V1

ADMIN:

- Semua akses
- Master data
- User

---

# 7. KONSEP REQUEST ORDER

Request Order adalah dokumen awal kebutuhan pembelian.

Mekanik menyampaikan:

- barang
- quantity
- satuan
- spesifikasi
- kebutuhan/penggunaan
- site
- project jika ada
- cost center jika ada
- vendor yang diinginkan jika diketahui
- prioritas
- keterangan

RO belum merupakan transaksi finansial.

Karena itu:

harga = 0
subtotal = 0

Harga dan subtotal baru akan digunakan pada tahap Purchase Order.

Walaupun field harga/subtotal tersedia di tabel RO, UI RO tidak perlu menampilkan harga sebagai nilai komersial. Bila ditampilkan, gunakan "Belum ditentukan", bukan Rp0.

---

# 8. VENDOR PADA RO

Mekanik diperbolehkan menentukan vendor.

Logistik juga dapat memeriksa atau mengoreksi vendor sesuai kewenangannya.

Vendor pada RO harus tetap menjadi referensi untuk proses berikutnya.

Aturan bisnis:

RO → vendor yang dipilih
PO nantinya → vendor yang sama
Receiving nantinya → vendor yang sama
Invoice nantinya → vendor yang sama

Untuk V1 jangan membuat mekanisme multi-vendor dalam satu RO.

Asumsi bisnis:

1 RO = 1 vendor = 1 PO

---

# 9. RELASI DOKUMEN YANG DISEPAKATI

Untuk roadmap aplikasi:

1 RO = 1 PO

1 PO = 1 Receiving

1 Receiving = 1 Invoice

Vendor tidak boleh berbeda sepanjang rantai.

Receiving nantinya boleh mengalami:

PARTIAL → COMPLETE

Jika ada retur, retur harus diselesaikan sebelum invoice boleh dibuat.

Namun semua modul setelah RO belum diimplementasikan pada V1.

---

# 10. STATUS REQUEST ORDER

Gunakan status berikut:

DRAFT

RO sedang dibuat.

SUBMITTED

RO sudah dikirim oleh mekanik dan menunggu proses logistik.

PROCESSING_LOGISTIC

RO sedang diproses oleh logistik.

READY_FOR_PO

Data sudah lengkap dan siap diteruskan ke purchasing.

CONVERTED_TO_PO

RO sudah menjadi PO.

REJECTED

RO ditolak.

CANCELLED

RO dibatalkan.

Untuk V1, fokus UI/logic sampai:

DRAFT
→ SUBMITTED
→ PROCESSING_LOGISTIC
→ READY_FOR_PO

Status CONVERTED_TO_PO tetap disiapkan karena field id_po sudah ada sebagai referensi ke tahap berikutnya.

---

# 11. TABEL REQUEST_ORDER

Gunakan struktur yang sudah ada di project pengguna bila kompatibel.

Konsep minimal:

request_order

- id_request
- nomor
- tanggal_ro
- id_karyawan
- id_site
- id_vendor
- status
- tanggal_status
- keterangan
- id_po
- created_at / updated_at bila tersedia

PENTING:

id_po dipertahankan.

Fungsinya:

Jika RO sudah diterbitkan menjadi PO, RO dapat langsung mengetahui PO terkait.

Contoh:

RO-2026-0012
status = CONVERTED_TO_PO
id_po = 125

Selain id_po, tetap gunakan history/activity untuk mengetahui perjalanan status.

---

# 12. TABEL REQUEST_ORDER_DETAIL

Konsep:

request_order_detail

- id_detail
- id_request
- id_barang
- kode_barang
- nama_barang
- qty
- satuan
- harga
- subtotal
- keterangan jika tersedia

Harga dan subtotal default:

0

Jangan menghitung harga pada tahap RO.

Data RO akan direplikasi/copy ke:

purchase_order
purchase_order_detail

pada tahap berikutnya.

Pastikan struktur detail tidak membuat PO bergantung secara live kepada RO.

PO nantinya menyimpan snapshot datanya sendiri.

---

# 13. HISTORY REQUEST ORDER

Jika tabel history belum ada, buat:

request_order_history

Field minimal:

- id_history
- id_request
- status_from
- status_to
- keterangan
- created_by
- created_at

Tujuan:

Mencatat siapa melakukan apa dan kapan.

Contoh:

19 Agustus 2026 09:10
Budi membuat RO
DRAFT → SUBMITTED

19 Agustus 2026 09:35
Logistik memproses
SUBMITTED → PROCESSING_LOGISTIC

19 Agustus 2026 10:20
Logistik menyelesaikan pemeriksaan
PROCESSING_LOGISTIC → READY_FOR_PO

19 Agustus 2026 11:00
Purchasing membuat PO
READY_FOR_PO → CONVERTED_TO_PO

---

# 14. FORM CREATE REQUEST ORDER

Halaman:

admin/pages/request_order/create.php

Header:

Buat Request Order

Field header:

- Nomor RO otomatis
- Tanggal RO
- Peminta
- Site
- Project/Pekerjaan
- Cost Center
- Vendor
- Prioritas
- Keterangan

Jika user adalah MEKANIK:

Peminta otomatis user yang login.

Jangan biarkan user mengganti id_karyawan secara bebas.

Jika ADMIN:

boleh memilih peminta.

---

# 15. DETAIL BARANG RO

Gunakan tabel dynamic.

Kolom:

No
Barang
Spesifikasi
Qty
Satuan
Keterangan
Action

Button:

+ Tambah Barang

Barang menggunakan searchable select.

Saat memilih barang:

- kode barang
- nama barang
- satuan

dapat otomatis terisi.

User masih boleh mengubah spesifikasi/keterangan sesuai kebutuhan jika bisnis mengizinkan.

Harga:

tidak perlu ditampilkan sebagai field aktif pada form mekanik.

Database tetap menyimpan:

harga = 0
subtotal = 0

---

# 16. VALIDASI FORM RO

Wajib:

- peminta
- site
- minimal 1 detail barang
- barang
- qty > 0
- satuan
- vendor jika vendor memang wajib pada bisnis perusahaan

Qty harus:

> 0

Tidak boleh negatif.

Jika prioritas kosong:

NORMAL

---

# 17. BUTTON PADA RO

Saat DRAFT:

Simpan Draft
Kirim Request
Hapus/Batalkan Draft

Setelah SUBMITTED:

Mekanik tidak boleh mengubah data kecuali kebijakan bisnis mengizinkan.

Logistik:

Proses Request

Saat PROCESSING_LOGISTIC:

Logistik dapat:

Simpan
Kirim ke Purchasing

Saat READY_FOR_PO:

Tidak boleh diedit oleh mekanik.

Purchasing dapat:

Lihat Detail

Pada V1 belum ada tombol Create PO karena modul PO belum dibuat.

---

# 18. LIST REQUEST ORDER

Halaman:

admin/pages/request_order/index.php

Tampilkan:

No RO
Tanggal
Peminta
Site
Vendor
Jumlah Item
Prioritas
Status
Tanggal Status
Action

Filter:

- Search
- Status
- Site
- Vendor
- Peminta
- Prioritas
- Rentang tanggal

Default sorting:

terbaru.

Gunakan pagination server-side jika jumlah data besar.

---

# 19. DETAIL REQUEST ORDER

Halaman:

admin/pages/request_order/detail.php

Layout:

HEADER

RO-2026-0012

Badge status

Tanggal

Peminta

Site

Project

Cost Center

Vendor

Prioritas

Keterangan

---

DETAIL BARANG

Tabel:

Barang
Spesifikasi
Qty
Satuan
Keterangan

Jangan menjadikan harga sebagai fokus UI RO.

---

TIMELINE

Tampilkan history:

- Created
- Submitted
- Processing Logistic
- Ready for PO

Dengan:

tanggal
jam
user
status
keterangan

---

DOKUMEN TERKAIT

Jika id_po tidak null:

Purchase Order:
PO-2026-0001

Pada V1 tombol dapat mengarah ke placeholder halaman PO atau disabled dengan label "Modul PO belum tersedia".

---

# 20. LOGISTIK PROCESS RO

Halaman khusus atau mode detail untuk Logistik.

Tampilkan dua bagian:

## Informasi Permintaan Mekanik

Read-only:

- Peminta
- Site
- Project
- Cost Center
- Prioritas
- Keterangan
- Barang yang diminta

## Verifikasi Logistik

Logistik dapat:

- memilih/mengoreksi barang jika diperlukan
- memeriksa quantity
- memeriksa vendor
- menambahkan catatan
- memastikan data siap untuk PO

Button:

Simpan

Tandai Ready for PO

Jika data belum lengkap:

jangan izinkan Ready for PO.

---

# 21. DASHBOARD V1

Dashboard sederhana, jangan terlalu ramai.

Untuk MEKANIK:

Card:

- Draft Saya
- Menunggu Logistik
- Selesai Diproses

Untuk LOGISTIK:

Card:

- Request Baru
- Sedang Diproses
- Ready for PO

Untuk PURCHASING:

Card:

- Ready for PO
- Sudah Menjadi PO

Untuk ADMIN:

Total:

- Request Hari Ini
- Request Bulan Ini
- Draft
- Processing Logistic
- Ready for PO

Tampilkan tabel:

Request terbaru.

---

# 22. UI DESIGN

Gunakan Bootstrap 5.

Desktop:

Sidebar kiri fixed/collapsible.

Topbar:

- Search bila diperlukan
- Notification
- Nama user
- Role
- Avatar/dropdown

Content:

Container-fluid.

Mobile:

Sidebar menjadi offcanvas.

Table pada mobile:

gunakan responsive table atau card layout.

Form:

desktop dua kolom bila sesuai.

mobile satu kolom.

---

# 23. KOMPONEN UI

Gunakan Bootstrap 5:

- Navbar
- Sidebar/offcanvas
- Card
- Badge
- Table
- Modal
- Alert
- Toast
- Dropdown
- Form controls
- Input group
- Pagination
- Breadcrumb
- Nav tabs

Jangan menggunakan framework frontend seperti React/Vue.

Gunakan JavaScript vanilla.

---

# 24. STATUS BADGE

Gunakan warna:

DRAFT = secondary

SUBMITTED = info

PROCESSING_LOGISTIC = primary

READY_FOR_PO = success

CONVERTED_TO_PO = dark

REJECTED = danger

CANCELLED = secondary

---

# 25. API

Endpoint minimal:

POST /api/auth/login.php
POST /api/auth/logout.php
GET  /api/auth/me.php

GET /api/request_order/list.php
GET /api/request_order/get.php
POST /api/request_order/create.php
POST /api/request_order/update.php
POST /api/request_order/submit.php
POST /api/request_order/process.php
POST /api/request_order/cancel.php

GET /api/master/barang.php
GET /api/master/vendor.php
GET /api/master/site.php
GET /api/master/karyawan.php

Semua endpoint kecuali login:

- validasi session
- validasi Bearer token
- validasi role
- prepared statement
- JSON response

---

# 26. TRANSACTION DATABASE

Perubahan status RO harus menggunakan database transaction.

Contoh:

SUBMITTED:

BEGIN

update request_order status

insert request_order_history

COMMIT

Jika gagal:

ROLLBACK

Demikian juga:

PROCESSING_LOGISTIC

READY_FOR_PO

CANCELLED

Jangan sampai status berubah tetapi history gagal tersimpan.

---

# 27. NOMOR REQUEST ORDER

Format:

RO-YYYY-NNNN

Contoh:

RO-2026-0001

RO-2026-0002

Nomor harus unique.

Jangan menggunakan timestamp sebagai nomor yang ditampilkan user.

Gunakan transaksi database saat generate nomor agar mengurangi risiko duplicate.

---

# 28. MASTER DATA

V1 hanya perlu membaca master yang sudah ada di database.

Jangan membuat ulang master jika tabel sudah tersedia.

Prioritaskan kompatibilitas dengan database project yang sudah ada.

Master utama:

- barang
- vendor
- site
- karyawan
- user

Jika struktur tabel existing berbeda, adaptasikan query/API ke struktur existing.

Jangan menghapus data existing.

Jangan melakukan DROP TABLE otomatis.

---

# 29. ERROR HANDLING

Frontend harus menampilkan error yang ramah user.

Contoh:

"Data belum lengkap."

"Minimal satu barang harus ditambahkan."

"Quantity harus lebih besar dari 0."

"Anda tidak memiliki hak untuk melakukan proses ini."

"Request Order sudah tidak dapat diubah karena telah diproses."

Jangan menampilkan SQL error mentah kepada user.

SQL error dicatat ke server log.

---

# 30. AUDIT

Minimal simpan:

created_by
created_at
updated_at

Untuk perubahan status:

request_order_history.

Jangan membuat sistem audit yang terlalu kompleks pada V1.

---

# 31. BUSINESS RULE YANG HARUS DIPATUHI

1. Mekanik adalah pihak utama yang membuat RO.
2. Mekanik boleh memilih vendor.
3. Logistik memproses dan memverifikasi RO.
4. Purchasing menerima RO yang READY_FOR_PO.
5. RO belum merupakan transaksi finansial.
6. Harga RO default 0.
7. Subtotal RO default 0.
8. Harga komersial akan ditentukan pada PO.
9. RO tidak membuat hutang.
10. RO tidak membuat jurnal accounting.
11. 1 RO = 1 PO.
12. Vendor RO menjadi vendor PO.
13. Tidak boleh mengganti vendor secara sembarangan pada PO.
14. id_po pada RO disiapkan sebagai referensi histori.
15. History status harus dicatat.
16. Jangan menghapus RO yang sudah masuk proses; gunakan CANCELLED.
17. Draft boleh dihapus jika belum submit, sesuai hak akses.
18. Semua endpoint API wajib terproteksi.
19. Semua input database menggunakan prepared statement.
20. Jangan gunakan PDO.

---

# 32. ROADMAP SETELAH V1

Jangan implementasikan sekarang, tetapi struktur harus memungkinkan:

V2:

Request Order
→ Purchase Order
→ Manager Approval
→ Vendor Confirmation

V3:

PO
→ Vendor Delivery
→ Receiving
→ Partial / Complete / Failed

V4:

Receiving
→ Retur
→ Ready for Invoice
→ Invoice

V5:

Invoice
→ Hutang
→ Jatuh Tempo
→ Pembayaran

V6:

Gudang
→ Distribusi ke Site
→ Surat Jalan Internal
→ Site Receiving
→ Bukti Penerimaan / BAST

V7:

COA
→ Cost Center
→ Project
→ Accounting / Reporting

---

# 33. IMPORTANT: JANGAN OVER-ENGINEER

Ini aplikasi internal sederhana.

Jangan menambahkan:

- Laravel
- Composer dependency besar
- React
- Vue
- Node build system
- JWT kompleks
- Microservice
- Repository pattern berlebihan
- ORM
- Docker wajib
- Redis
- WebSocket

Gunakan PHP 8 + mysqli + Bootstrap 5 + JavaScript vanilla.

Prioritas:

1. keamanan
2. struktur kode rapi
3. UX sederhana
4. validasi bisnis
5. database integrity
6. mudah dikembangkan

---

# 34. EXPECTED RESULT

Hasil V1 harus sudah bisa:

1. Login
2. Session authentication
3. Role-based access
4. Dashboard
5. Mekanik membuat RO
6. Menambahkan beberapa barang
7. Memilih vendor
8. Menentukan site
9. Menentukan project/cost center bila tersedia
10. Menyimpan draft
11. Submit RO
12. Logistik melihat RO masuk
13. Logistik memproses RO
14. Logistik mengubah status menjadi READY_FOR_PO
15. Purchasing melihat daftar READY_FOR_PO
16. Melihat detail RO
17. Melihat history RO
18. Menampilkan id_po bila sudah tersedia
19. Responsive desktop/tablet/mobile
20. Semua API protected

---

# 35. CODING STYLE

Gunakan kode yang mudah dibaca.

Prefer:

- fungsi kecil
- nama variabel jelas
- prepared statement
- reusable helper
- centralized authentication middleware
- centralized JSON response helper
- centralized database connection

Jangan membuat file PHP 1000+ baris.

Pisahkan:

- UI
- API
- database
- authentication
- CSS
- JavaScript

---

# 36. URUTAN IMPLEMENTASI

Implementasikan secara bertahap:

STEP 1
Review existing database schema.

STEP 2
Buat koneksi mysqli.

STEP 3
Buat session authentication.

STEP 4
Buat login.

STEP 5
Buat API middleware.

STEP 6
Buat dashboard berdasarkan role.

STEP 7
Buat Request Order list.

STEP 8
Buat Create Request Order.

STEP 9
Buat detail dynamic barang.

STEP 10
Buat Submit RO.

STEP 11
Buat Logistik Processing.

STEP 12
Buat Ready for PO.

STEP 13
Buat history/timeline.

STEP 14
Testing role permission.

STEP 15
Testing responsive.

STEP 16
Security review.

Jangan melompat langsung membuat seluruh modul masa depan.

---

# 37. ATURAN TERAKHIR UNTUK AI CODER

Sebelum membuat perubahan database:

- baca schema existing
- jangan menghapus tabel
- jangan mengganti nama field existing tanpa alasan kuat
- pertahankan data existing
- berikan SQL migration jika perlu
- jelaskan perubahan schema

Jika tabel request_order dan request_order_detail sudah tersedia, gunakan tabel tersebut.

Jangan membuat tabel duplikat dengan nama baru.

Jika ada konflik antara dokumen ini dan struktur database existing:

1. jangan menghapus database
2. jelaskan konflik
3. adaptasikan implementasi
4. hanya lakukan migration yang aman

Target utama adalah membuat V1 berjalan menggunakan database `jaya_teknis` yang sudah ada.

# END OF SPECIFICATION
