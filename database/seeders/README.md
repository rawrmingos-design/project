# Database Seeders

Dokumentasi lengkap untuk database seeders di project Backend Game Top-Up.

## 📋 **Daftar Seeders**

### 1. **UserSeeder**
Membuat data user dengan berbagai role dan balance.

**Total Users: 16**
- 1 Admin
- 2 Platinum Members
- 3 Gold Members
- 10 Regular Members

**Default Password:** `password` (untuk semua user)

**Data yang di-generate:**
- name (Full Name)
- username (unique)
- email (unique)
- password (hashed)
- no_wa (WhatsApp number)
- balance (varying amounts)
- role (Admin/Platinum/Gold/Member)
- api_key (auto-generated)

**Example Users:**
```
Admin:
- Email: admin@topup.com
- Username: admin
- Balance: Rp 1,000,000

Platinum:
- Email: budi.platinum@example.com
- Username: budi_platinum
- Balance: Rp 500,000

Gold:
- Email: andi.gold@example.com
- Username: andi_gold
- Balance: Rp 300,000

Member:
- Email: ahmad.member@example.com
- Username: ahmad_member
- Balance: Rp 50,000 - 200,000 (random)
```

---

### 2. **DepositSeeder**
Membuat data deposit untuk semua users yang ada di database.

**Jumlah Deposits per User:** 2-5 deposits (random)

**Data yang di-generate:**
- order_id (format: DEP-XXXXXXXXXX)
- username (mengambil dari users)
- metode (payment method)
- no_pembayaran (payment number/reference)
- jumlah (amount: Rp 10,000 - 500,000)
- status (70% Success, 30% Pending)
- created_at (random dalam 30 hari terakhir)

**Payment Methods:**
- **Banks:** BCA, BNI, BRI, Mandiri
- **E-Wallets:** OVO, DANA, GoPay, ShopeePay
- **QRIS**

**Payment Number Generation:**
- Banks: 10-digit account number (starts with 12)
- E-Wallets: 11-digit phone number (starts with 08)
- QRIS: Reference number with prefix "QRIS"

**Status Distribution:**
- 70% deposits: Success
- 30% deposits: Pending

---

## 🚀 **Cara Menggunakan**

### **Option 1: Run All Seeders**
```bash
php artisan db:seed
```
Akan menjalankan semua seeders dalam urutan:
1. UserSeeder
2. DepositSeeder

### **Option 2: Run Specific Seeder**

**User Seeder Only:**
```bash
php artisan db:seed --class=UserSeeder
```

**Deposit Seeder Only:**
```bash
php artisan db:seed --class=DepositSeeder
```
⚠️ **Note:** DepositSeeder memerlukan data users, jadi jalankan UserSeeder terlebih dahulu!

### **Option 3: Fresh Migration + Seed**
```bash
php artisan migrate:fresh --seed
```
Akan drop semua tables, migrate ulang, dan seed data.

---

## 📊 **Expected Results**

Setelah seeding selesai, Anda akan memiliki:

**Users Table:**
- 16 users total
- Mix of roles (Admin, Platinum, Gold, Member)
- Varying balances
- All with password: `password`

**Deposits Table:**
- Approximately 32-80 deposits (2-5 per user)
- Mix of payment methods
- Mix of statuses (Success/Pending)
- Timestamps spread over last 30 days

---

## 🔐 **Login Credentials**

**Admin Panel:**
```
Email: admin@topup.com
Password: password
```

**Other Users:**
All users dapat login dengan:
```
Email: [username]@example.com
Password: password
```

Example:
- `budi.platinum@example.com` / `password`
- `ahmad.member@example.com` / `password`

---

## 🛠️ **Customization**

### **Mengubah Jumlah Users**

Edit `UserSeeder.php`:
```php
// Tambah/kurangi di array $memberNames
$memberNames = [
    'Ahmad Member', 'Rina Member', // dst...
];
```

### **Mengubah Jumlah Deposits per User**

Edit `DepositSeeder.php`:
```php
// Line 40: Ubah range
$numberOfDeposits = rand(2, 5); // Ubah 2 dan 5 sesuai keinginan
```

### **Mengubah Status Distribution**

Edit `DepositSeeder.php`:
```php
// Line 52: Ubah persentase
// 70% Success, 30% Pending
$status = (rand(1, 100) <= 70) ? 'Success' : 'Pending';
```

### **Mengubah Amount Range**

Edit `DepositSeeder.php`:
```php
// Line 49: Ubah range
$jumlah = rand(10, 500) * 1000; // 10k - 500k
```

---

## ⚠️ **Important Notes**

1. **Foreign Key Constraints:**
   - DepositSeeder bergantung pada data dari UserSeeder
   - Selalu jalankan UserSeeder terlebih dahulu

2. **Duplicate Data:**
   - Jika Anda run seeder berkali-kali, akan ada duplicate data
   - Gunakan `migrate:fresh --seed` untuk reset database

3. **Production Environment:**
   - ⚠️ **JANGAN** jalankan seeder di production!
   - Seeders hanya untuk development/testing

4. **Password Security:**
   - Default password (`password`) sangat lemah
   - Di production, gunakan password yang kuat

---

## 🧪 **Testing**

Untuk testing purposes, Anda bisa:

1. **Test User Management:**
   - Login sebagai admin
   - Lihat daftar users dengan berbagai roles
   - Test balance adjustment

2. **Test Deposit Management:**
   - Approve pending deposits
   - Reject deposits
   - Check balance updates

3. **Test Filters:**
   - Filter by status (Success/Pending)
   - Filter by payment method
   - Filter by date range

---

## 📝 **Changelog**

**v1.0.0 - 2025-01-16**
- Initial seeders created
- UserSeeder with 16 users
- DepositSeeder with dynamic deposit generation
- DatabaseSeeder with proper ordering

---

## 💡 **Tips**

1. **Quick Reset:**
   ```bash
   php artisan migrate:fresh --seed
   ```

2. **Check Data:**
   ```bash
   php artisan tinker
   >>> User::count()
   >>> Deposit::count()
   ```

3. **Clear Cache After Seeding:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

---

**Happy Seeding! 🌱**
