# Referensi Sistem - Alfagift

## Tentang Alfagift
Alfagift (https://alfagift.id) adalah platform e-commerce retail dari Alfamart yang menyediakan layanan belanja online dengan pengiriman cepat dan terjadwal.

## Fitur Alfagift yang Diadopsi di D'florist

### 1. **Multi-Address Management**
**Alfagift:**
- Customer bisa menyimpan multiple alamat
- Set alamat utama
- Label alamat (Rumah, Kantor, dll)
- Integrasi Google Maps untuk pin lokasi

**D'florist:**
- ✅ Implementasi sama dengan Alfagift
- ✅ Tambahan: Validasi koordinat GPS wajib
- ✅ Tambahan: Nama & nomor HP penerima berbeda

### 2. **Slot Waktu Pengiriman**
**Alfagift:**
- Pilih tanggal pengiriman
- Pilih slot waktu pengiriman (pagi, siang, sore)
- Sistem kuota per slot

**D'florist:**
- ✅ Implementasi sama dengan Alfagift
- ✅ Tambahan: Pre-order minimal H+2
- ✅ Tambahan: Kuota per metode pengiriman (5 per hari)
- ✅ Slot waktu berbeda untuk Kurir Toko dan Pick Up

### 3. **Metode Pengiriman**
**Alfagift:**
- Instant Delivery (kurir sendiri)
- Same Day Delivery
- Next Day Delivery
- Pick Up di toko

**D'florist:**
- ✅ Kurir Toko (mirip Instant Delivery Alfagift)
- ✅ Ekspedisi (mirip Next Day Delivery)
- ✅ Pick Up (sama dengan Alfagift)
- ✅ Tambahan: Perhitungan jarak otomatis untuk menentukan metode

### 4. **Keranjang Belanja**
**Alfagift:**
- Add to cart tanpa login
- Update quantity
- Remove item
- View total
- Badge counter di navbar

**D'florist:**
- ✅ Implementasi identik dengan Alfagift
- ✅ Session-based cart
- ✅ Checkout wajib login

### 5. **Checkout Flow**
**Alfagift:**
1. Pilih alamat pengiriman
2. Pilih metode pengiriman
3. Pilih slot waktu
4. Pilih metode pembayaran
5. Review & konfirmasi
6. Pembayaran

**D'florist:**
- ✅ Flow identik dengan Alfagift
- ✅ Tambahan: Validasi kuota pengiriman
- ✅ Tambahan: Perhitungan jarak untuk metode pengiriman

### 6. **Metode Pembayaran**
**Alfagift:**
- E-wallet (OVO, GoPay, DANA, dll)
- Transfer Bank
- Virtual Account
- COD

**D'florist:**
- ✅ DANA (e-wallet)
- ✅ Bank Jago (transfer bank)
- ✅ SeaBank (transfer bank)
- ✅ COD (dengan pembatasan untuk metode tertentu)

### 7. **Order Tracking**
**Alfagift:**
- Status pesanan real-time
- Timeline pesanan
- Notifikasi status
- Filter berdasarkan status

**D'florist:**
- ✅ Implementasi sama dengan Alfagift
- ✅ 6 status pesanan
- ✅ Timeline visual
- ✅ Filter status

### 8. **Review & Rating**
**Alfagift:**
- Rating 1-5 bintang
- Komentar ulasan
- Hanya bisa review setelah pesanan selesai
- View ulasan produk

**D'florist:**
- ✅ Implementasi identik dengan Alfagift
- ✅ Satu review per produk per pesanan

### 9. **User Interface**
**Alfagift:**
- Clean & modern design
- Responsive (mobile & desktop)
- Card-based layout
- Easy navigation
- Color scheme konsisten

**D'florist:**
- ✅ Adopsi prinsip UI/UX Alfagift
- ✅ Responsive design
- ✅ Card-based layout
- ✅ Warna tema: Pink & Blue Pastel (sesuai branding toko bunga)

### 10. **Admin Dashboard**
**Alfagift:**
- Dashboard dengan statistik
- Manajemen produk
- Manajemen pesanan
- Laporan penjualan
- Pengaturan sistem

**D'florist:**
- ✅ Implementasi sama dengan Alfagift
- ✅ Tambahan: Pengaturan radius kurir
- ✅ Tambahan: Pengaturan kuota pengiriman

---

## Perbedaan dengan Alfagift

### Fitur Khusus D'florist (Tidak Ada di Alfagift):

1. **Pre-Order System**
   - Minimal H+2 dari tanggal pemesanan
   - Validasi tanggal pengiriman
   - Tidak bisa pilih tanggal sebelum H+2

2. **Perhitungan Jarak Otomatis**
   - Menggunakan Haversine formula
   - Menentukan metode pengiriman berdasarkan jarak
   - Radius maksimal kurir toko (configurable)

3. **Sistem Kuota Terjadwal**
   - Max 5 pesanan per tanggal per metode
   - Indikator kuota penuh
   - Tanggal disabled jika kuota penuh

4. **Produk Promo**
   - Halaman khusus produk promo
   - Harga normal vs harga promo
   - Badge promo di produk

5. **E-Receipt**
   - Receipt yang bisa dicetak
   - Format professional
   - Informasi lengkap

6. **OTP untuk Lupa Password**
   - 6 digit OTP via email
   - Berlaku 10 menit
   - 3 step verification

---

## Fitur Alfagift yang Belum Diimplementasikan

Fitur-fitur ini bisa ditambahkan di versi mendatang:

1. **Live Chat Support**
   - Chat dengan customer service
   - Bot automation

2. **Push Notification**
   - Notifikasi real-time
   - Update status pesanan
   - Promo & penawaran

3. **Loyalty Program**
   - Point reward
   - Member tier
   - Exclusive deals

4. **Voucher System**
   - Discount voucher
   - Cashback
   - Referral code

5. **Wishlist**
   - Save produk favorit
   - Share wishlist

6. **Product Recommendation**
   - AI-based recommendation
   - "Frequently bought together"
   - "You may also like"

7. **Advanced Search**
   - Search autocomplete
   - Filter advanced
   - Sort options

8. **Mobile App**
   - iOS & Android app
   - Push notification
   - Better UX

9. **Real-time Tracking**
   - Live tracking kurir
   - GPS tracking
   - ETA estimation

10. **Multiple Payment Gateway**
    - Midtrans integration
    - Xendit integration
    - QRIS payment

---

## Best Practices dari Alfagift yang Diterapkan

### 1. User Experience (UX)
- ✅ Minimal clicks untuk checkout
- ✅ Clear call-to-action buttons
- ✅ Progress indicator di checkout
- ✅ Error handling yang jelas
- ✅ Success confirmation

### 2. Security
- ✅ Password hashing
- ✅ SQL injection protection
- ✅ XSS protection
- ✅ Session management
- ✅ HTTPS ready

### 3. Performance
- ✅ Optimized queries
- ✅ Image optimization ready
- ✅ Browser caching
- ✅ GZIP compression

### 4. Mobile-First Design
- ✅ Responsive layout
- ✅ Touch-friendly buttons
- ✅ Mobile navigation
- ✅ Optimized for small screens

### 5. Business Logic
- ✅ Clear pricing
- ✅ Transparent shipping cost
- ✅ Order confirmation
- ✅ Status tracking
- ✅ Easy cancellation (admin)

---

## Kesimpulan

D'florist mengadopsi best practices dari Alfagift dengan penyesuaian untuk:
- Industri toko bunga
- Sistem pre-order
- Pengiriman terjadwal
- Perhitungan jarak otomatis

Sistem ini menggabungkan kelebihan Alfagift dengan kebutuhan spesifik toko bunga untuk memberikan pengalaman belanja online yang optimal.

---

## Referensi Tambahan

- Alfagift Website: https://alfagift.id
- Alfagift Mobile App: Available on iOS & Android
- Best Practices E-Commerce: https://www.shopify.com/blog/ecommerce-best-practices
- UI/UX Guidelines: https://www.nngroup.com/articles/

---

## Credits

Sistem D'florist dikembangkan dengan inspirasi dari:
- **Alfagift** - E-commerce platform reference
- **Bootstrap 5** - UI framework
- **Google Maps API** - Location services
- **Font Awesome** - Icons
- **PHPMailer** - Email functionality
