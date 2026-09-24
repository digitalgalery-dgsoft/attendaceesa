---
trigger: always_on
description: Mandatory CI/CD SOP - Auto-Push GitHub, Staging Test, Production Deploy, and Graphify Memory Update on every update.
---

## Deployment & Graphify Memory Pipeline SOP

Setiap kali asisten AI selesai melakukan modifikasi/pembaruan kode (update), WAJIB menjalankan alur pipeline berikut secara berurutan:

1. **Push ke GitHub**:
   - Lakukan commit perubahan kode dan push ke remote branch `main`:
     ```powershell
     git add -A
     git commit -m "[Deskripsi update yang jelas dan ringkas]"
     git push origin main
     ```

2. **Deploy & Pengujian ke Server Staging (`appsend.my.id`)**:
   - Picu webhook deployment staging:
     `POST https://appsend.my.id/deploy.php?token=dgsoft_rahasia_123`
   - Uji kesehatan server staging:
     `GET https://appsend.my.id/api/v1/sync/ping` (Wajib status HTTP 200 OK).
   - **PENTING**: Jika server staging gagal merespons atau error, HENTIKAN proses dan JANGAN deploy ke server production.

3. **Deploy ke Cluster Production (3 Server: AMK, AKP, ATK)**:
   - Jika pengujian staging berhasil, picu deployment ke seluruh cluster production:
     `POST https://appsend.my.id/deploy-production.php?token=dgsoft_rahasia_123`
   - Verifikasi respon health check HTTP 200 OK pada ketiga server:
     - Server 1 (AMK): `https://amk.esa-solutions.id/api/v1/sync/ping`
     - Server 2 (AKP): `https://akp.esa-solutions.id/api/v1/sync/ping`
     - Server 3 (ATK): `https://atk.esa-solutions.id/api/v1/sync/ping`

4. **Pembaruan Memory Knowledge Graph (Graphify Update)**:
   - Jalankan pembaruan memory graphify agar seluruh node, relasi, dan konteks arsitektur terbaru langsung tercatat di `graphify-out/`:
     ```powershell
     python scratch/build_knowledge_graph.py
     ```
   - Alternatif: Seluruh langkah 1 s.d 4 di atas dapat dieksekusi sekaligus via skrip:
     ```powershell
     powershell -ExecutionPolicy Bypass -File pipeline.ps1 -CommitMessage "[Deskripsi update]"
     ```
