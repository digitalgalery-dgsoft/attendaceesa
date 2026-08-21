# Panduan Menjalankan Stress Test Aplikasi Presensi

Folder ini berisi skrip pengujian beban (*Load & Stress Testing*) menggunakan **Grafana k6** untuk menguji ketahanan server saat diakses ratusan user secara bersamaan.

---

### **1. Install k6 di Komputer Anda (Local Windows)**
Buka PowerShell / Command Prompt di komputer Anda, lalu jalankan:
```powershell
winget install k6 --source winget
```
*(Atau download installer dari https://dl.k6.io/msi/k6-latest-amd64.msi)*

---

### **2. Lokasi File Skrip**
File skrip berada di folder ini:
`g:\My File\Project APlikasi Absensi\New\load_testing\stress_test.js`

---

### **3. Cara Menjalankan Uji Beban**
1. Buka Terminal / PowerShell.
2. Masuk ke folder ini:
   ```powershell
   cd "g:\My File\Project APlikasi Absensi\New\load_testing"
   ```
3. Jalankan pengujian:
   ```powershell
   k6 run stress_test.js
   ```

---

### **4. Menguji dengan Jumlah User Tertentu (Manual CLI)**
Jika ingin menguji jumlah user tertentu tanpa mengikuti tahapan skrip:
- **Uji 100 User selama 30 detik:**
  ```powershell
  k6 run --vus 100 --duration 30s stress_test.js
  ```
- **Uji 250 User selama 1 menit:**
  ```powershell
  k6 run --vus 250 --duration 1m stress_test.js
  ```
- **Uji 500 User selama 2 menit (Stress Peak):**
  ```powershell
  k6 run --vus 500 --duration 2m stress_test.js
  ```

---

### **5. Membaca Hasil Metrik**
- **`http_reqs`**: Total request per detik (RPS / Throughput) yang berhasil diproses server.
- **`http_req_duration (p95)`**: 95% user mendapatkan respon di bawah waktu ini (target: < 2 detik).
- **`http_req_failed`**: Persentase request yang gagal/error (target: < 1%).
