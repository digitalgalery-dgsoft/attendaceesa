# Aturan Penamaan Versi Aplikasi (att-mobile)

Ketika melakukan *build* atau pembaruan aplikasi mobile (APK), ikuti aturan penomoran versi berikut:

1. **Pembaruan Kompleks/Mayor**:
   - Jika pembaruan (update) lumayan banyak, kompleks, atau mengubah fitur secara signifikan, **naikkan angka versi terakhir sebanyak 1 digit**.
   - *Contoh*: Dari `1.0.119` menjadi `1.0.120`.

2. **Pembaruan Minor/Kecil**:
   - Jika pembaruan hanya berupa perbaikan kecil (minor bug fix) atau perubahan teks/UI yang sangat sedikit, **tambahkan huruf alfabet di akhir versi**.
   - *Contoh*: Dari `1.0.119` menjadi `1.0.119a`, `1.0.119b`, dan seterusnya.

Catatan:
- Pastikan untuk selalu merujuk pada `pubspec.yaml` untuk mengecek versi terakhir sebelum melakukan *build*.
- Perbarui juga `ROADMAP.md` dengan nomor versi yang baru jika ada rilis APK.
