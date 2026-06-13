# Prompt Engineering Log — Tugas 3 IAE

Dokumen ini berisi log interaksi dan teknik *Prompt Engineering* yang digunakan bersama AI (Antigravity) dalam merancang dan mengimplementasikan solusi integrasi untuk **Tugas 3 Integrasi Aplikasi Enterprise**.

---

## 1. Strategi & System Prompting
Pengembangan dilakukan dengan membagi tugas besar menjadi modul-modul modular terkontrol:
1. **Analisis Struktur Awal**: Membaca dependency framework (Laravel 12) dan memetakan model database yang sudah ada (`Member`, `Voucher`, `MemberVoucher`).
2. **Federated SSO**: Merancang logic verifikasi token JWT RS256 menggunakan JWKS key set agar tidak hardcoded public key lokal.
3. **SOAP Client**: Mengatasi kekakuan XML SOAP request secara manual tanpa extension PHP-SOAP berat, melainkan menggunakan `Illuminate\Support\Facades\Http` dengan raw body XML.
4. **AMQP Client**: Memanfaatkan REST API publish RabbitMQ dari Cloud Dosen karena keterbatasan driver sockets lokal.

---

## 2. Riwayat Log Prompting Utama

### Prompt 1: Analisis Kebutuhan Tugas & Mapping
> **User**: *[Kirim deskripsi tugas Tugas Besar & Tugas 3]*  
> **AI (Antigravity)**: Mengurai tugas menjadi komponen utama: Tugas Kelompok (API Gateway) & Tugas Individu (SSO, SOAP, RabbitMQ). Melakukan pemetaan struktur file proyek Laravel `dpark-membership`.

### Prompt 2: Pembuatan SsoService & Middleware JWT
> **User**: *coba kerjakan dulu yang individu karena itu deadline nya besok*  
> **AI (Antigravity)**: 
> - Menginstal dependensi `firebase/php-jwt` untuk pembacaan key RS256 JWKS.
> - Membuat file `SsoService.php` dengan method `verifyAndDecodeJwt()` dan `mapToLocalRole()`.
> - Membuat `SsoAuthMiddleware.php` untuk memotong request masuk dan memvalidasi token dari SSO Dosen.
> - Membuat migration `create_local_roles_table.php` untuk database lokal.

### Prompt 3: Implementasi SOAP Client Legacy & RabbitMQ Publisher
> **AI (Antigravity)**:
> - Membuat `SoapAuditService.php` untuk membungkus data transaksi ke dalam XML Envelope sesuai format instruksi SOAP dosen, serta parsing respons `<ReceiptNumber>`.
> - Membuat `AmqpPublisherService.php` untuk integrasi RabbitMQ via API broadcast.
> - Mengintegrasikan seluruh lapis orkestrasi ke dalam controller utama `MemberController.php` di endpoint `/members/verification`.

### Prompt 4: Pembuatan Dokumen Luaran Analisis & Log Prompting
> **User**: *apa saja yang sudah kamu ubah dan kamu lakukan dengan tugas saya dan bagaimana cara saya untuk mengecek apakah tugas saya sudah selesai*  
> **AI (Antigravity)**: 
> - Menjelaskan komponen-komponen yang telah ditambahkan.
> - Memberikan panduan pengujian menggunakan `php artisan migrate`, `serve`, dan request postman.
> - Menuliskan dokumen `analisis_tugas_3.md` lengkap dengan diagram alur (Mermaid sequence diagram) dan justifikasi transaksi kritis.
> - Membuat dokumen `prompt_engineering_log.md` ini.

### Prompt 5: Debugging SSO Subject & Live Testing
> **User**: *kenapa ornag orang ada sso subject nya kalo aku ada apa sso subject project aku*
> **AI (Antigravity)**: 
> - Menjelaskan bahwa sistem sudah menggunakan default SSO subject, namun memperbarui kode agar mengambil data dinamis `sub` dari hasil dekode M2M token.
> - Menggunakan curl/Invoke-RestMethod untuk memanggil endpoint dan mendekode JWT token payload secara manual (Base64) guna menunjukkan bentuk asli data JWT.
> - Menguji API endpoint secara live dengan memicu transaksi kritis (`POST /api/v1/members/verification`) dan mengonfirmasi bahwa seluruh integrasi SSO, SOAP Audit, dan RabbitMQ AMQP berhasil terkirim.

---

## 3. Hasil Sintesis Solusi
Seluruh komponen kode program telah diatur agar modular dan ditaruh di bawah namespace Laravel yang semestinya:
- Controller: [MemberController.php](file:///c:/laragon/www/dpark-membership/app/Http/Controllers/MemberController.php), [SsoController.php](file:///c:/laragon/www/dpark-membership/app/Http/Controllers/SsoController.php)
- Services: [SsoService.php](file:///c:/laragon/www/dpark-membership/app/Services/SsoService.php), [SoapAuditService.php](file:///c:/laragon/www/dpark-membership/app/Services/SoapAuditService.php), [AmqpPublisherService.php](file:///c:/laragon/www/dpark-membership/app/Services/AmqpPublisherService.php)
- Middleware: [SsoAuthMiddleware.php](file:///c:/laragon/www/dpark-membership/app/Http/Middleware/SsoAuthMiddleware.php)
- Model & Migration: [LocalRole.php](file:///c:/laragon/www/dpark-membership/app/Models/LocalRole.php), [Migration](file:///c:/laragon/www/dpark-membership/database/migrations/2026_06_12_155711_create_local_roles_table.php)
