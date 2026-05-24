# 🎨 Setup Logo D'florist

## Lokasi Logo
Letakkan file logo Anda di:
```
assets/images/logo.png
```

## Spesifikasi Logo

### Format File
- **Format:** PNG (dengan background transparan)
- **Alternatif:** JPG, SVG

### Ukuran Rekomendasi
- **Tinggi:** 40-50px
- **Lebar:** Proporsional (biasanya 120-200px)
- **Rasio:** Landscape (horizontal) lebih baik

### Contoh Ukuran
- Desktop: 40px tinggi
- Mobile: 32-35px tinggi (otomatis responsive)

## Cara Upload Logo

### Opsi 1: Manual
1. Buat folder `assets/images/` jika belum ada
2. Copy file logo Anda ke folder tersebut
3. Rename menjadi `logo.png`

### Opsi 2: Via FTP/cPanel
1. Login ke cPanel/FTP
2. Navigate ke folder `assets/images/`
3. Upload file logo
4. Rename menjadi `logo.png`

## Fallback
Jika logo tidak ditemukan, sistem akan otomatis menampilkan:
- Icon bunga dalam lingkaran pink
- Tetap terlihat profesional

## Tips Desain Logo

### Untuk Toko Bunga:
- Gunakan elemen bunga/floral
- Warna pink/pastel yang matching dengan tema
- Font yang elegan dan feminine
- Simple dan mudah dikenali

### Format Terbaik:
```
[Icon Bunga] D'florist
```
atau
```
Logo dengan tulisan D'florist terintegrasi
```

## Testing
Setelah upload logo:
1. Refresh halaman dengan `Ctrl + Shift + R`
2. Cek di desktop dan mobile
3. Pastikan logo terlihat jelas dan proporsional

## Troubleshooting

### Logo tidak muncul?
1. Cek path file: `assets/images/logo.png`
2. Cek permission folder (755)
3. Cek nama file (case-sensitive)
4. Hard refresh browser

### Logo terlalu besar/kecil?
Edit di `assets/css/style.css`:
```css
.logo-img {
    height: 40px; /* Ubah nilai ini */
    width: auto;
    object-fit: contain;
}
```

### Logo pecah/blur?
- Gunakan file dengan resolusi lebih tinggi
- Minimal 2x ukuran tampilan (80px untuk tampilan 40px)
- Format PNG untuk kualitas terbaik

## Contoh Logo Online
Jika belum punya logo, bisa gunakan:
- Canva.com (template gratis)
- Logo.com (generator)
- Fiverr.com (hire designer)

Atau sementara gunakan icon default yang sudah ada! 🌸
