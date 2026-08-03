# H2H P05: Homepage Popup

## Summary
Fokus iterasi ini adalah menyempurnakan fitur P05 Homepage Popup untuk mengejar paritas antara React/Inertia (tema modern) dengan Blade/Alpine (tema legacy).

Yang telah diselesaikan di batch ini:
- **Sinkronisasi State/Storage:** Menyamakan mekanisme "Don't show again" antara React dan Blade menggunakan localStorage dengan key unik per popup ID (`hidePopup_{id}`). 
- **Delay Standarisasi:** Mengubah delay munculnya popup di React dari 2.2 detik menjadi 500ms agar sama dengan Blade.
- **Implicit Opt-Out:** Pengguna secara implisit akan opt-out (tidak melihat popup yang sama lagi) setiap kali mereka menutup popup, baik via tombol close, klik backdrop, maupun tombol Escape. UI checkbox "Jangan tampilkan info ini lagi" dari versi Blade telah dihapus.
- **Focus Lifecycle & Accessibility:** 
  - Fokus otomatis pindah ke elemen interaktif pertama (atau panel) saat popup terbuka.
  - Implementasi *focus trap* via `Tab` dan `Shift+Tab` agar fokus tidak keluar dari modal.
  - *Focus restoration* saat popup ditutup, mengembalikan fokus ke elemen yang aktif sebelum popup terbuka.
  - Perbaikan label aksesibilitas ARIA untuk popup yang hanya berisi gambar.
- **Test Infrastructure:** Setup Playwright E2E testing framework (`playwright.config.js`) dan test suite untuk `homepage-popup.spec.js`.
- **PHPUnit Migration:** Konfigurasi `phpunit.xml` dan `phpunit.mysql.xml` telah dimigrasikan ke schema PHPUnit 11.

## Test Plan
- Unit/Feature Tests: 
  - `php artisan test tests/Feature/ArticleLayoutParityTest.php`
  - `php artisan test tests/Feature/PasswordRecoveryTest.php`
- E2E Playwright Tests:
  - `npx playwright test tests/e2e/homepage-popup.spec.js` (Memerlukan server lokal yang berjalan di port 8000).

## Note
Semua gate test P01-P04 sebelumnya telah diverifikasi dan lulus. Checklist rilis (`release-bangjeff-checklist.md`) telah diperbarui untuk menyertakan test P04 Article Parity dan revisi test Password Recovery.
