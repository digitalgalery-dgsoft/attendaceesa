import http from 'k6/http';
import { check, sleep } from 'k6';

/**
 * =========================================================================
 * SKRIP STRESS TEST K6 - APLIKASI PRESENSI ESA / AMK
 * =========================================================================
 * 
 * Skenario Pengujian: Bertahap Selama 1 Menit (10 hingga 100 User)
 * - 15 detik pertama: Mulai dari 0 naik ke 10 User (Tahap Awal)
 * - 20 detik berikutnya: Naik bertahap dari 10 ke 50 User (Beban Sedang)
 * - 15 detik berikutnya: Puncak di 100 User Serentak (Beban Puncak / Rush Hour)
 * - 10 detik terakhir: Pendinginan (Cooldown) kembali ke 0 User
 * 
 * Total Waktu Pengujian: Tepat 1 Menit (60 Detik)
 * =========================================================================
 * 
 * Cara Menjalankan di Terminal:
 * cd "g:\My File\Project APlikasi Absensi\New\load_testing"
 * k6 run stress_test.js
 * =========================================================================
 */

export const options = {
    stages: [
        { duration: '15s', target: 10 },   // Tahap 1 (15 detik): Naik ke 10 user
        { duration: '20s', target: 50 },   // Tahap 2 (20 detik): Naik ke 50 user
        { duration: '15s', target: 100 },  // Tahap 3 (15 detik): Puncak di 100 user
        { duration: '10s', target: 0 },    // Tahap 4 (10 detik): Cooldown kembali ke 0
    ],
    thresholds: {
        // 95% request harus dijawab server di bawah 2 detik
        http_req_duration: ['p(95)<2000'],
        // Error rate (kegagalan request) harus di bawah 2%
        http_req_failed: ['rate<0.02'],
    },
};

const BASE_URL = 'https://appsend.my.id/api';

export default function () {
    // ---------------------------------------------------------------------
    // 1. Tes Endpoint Publik: Cek Pengaturan Aplikasi
    // ---------------------------------------------------------------------
    const settingsRes = http.get(`${BASE_URL}/settings`, {
        headers: { 'Accept': 'application/json' },
    });

    check(settingsRes, {
        'settings status 200': (r) => r.status === 200,
        'settings response < 1s': (r) => r.timings.duration < 1000,
    });

    sleep(1); // Simulasi jeda user membuka aplikasi (1 detik)

    // ---------------------------------------------------------------------
    // 2. Tes Endpoint Login Karyawan (Simulasi Otentikasi)
    // ---------------------------------------------------------------------
    const loginPayload = JSON.stringify({
        email: 'jamil@dgsoft.id',
        password: 'password',
    });

    const loginRes = http.post(`${BASE_URL}/login`, loginPayload, {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    });

    const loginSuccess = check(loginRes, {
        'login status 200': (r) => r.status === 200,
    });

    let token = null;
    if (loginSuccess) {
        try {
            const body = JSON.parse(loginRes.body);
            token = body.token || (body.data && body.data.token);
        } catch (e) {}
    }

    sleep(1);

    // ---------------------------------------------------------------------
    // 3. Tes Endpoint Terotentikasi: Mengambil Jadwal Hari Ini
    // ---------------------------------------------------------------------
    const authHeaders = {
        'Accept': 'application/json',
        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };

    const scheduleRes = http.get(`${BASE_URL}/today-schedule?date=2026-08-21`, {
        headers: authHeaders,
    });

    check(scheduleRes, {
        'schedule status 200': (r) => r.status === 200,
        'schedule response < 1.5s': (r) => r.timings.duration < 1500,
    });

    sleep(1.5);

    // ---------------------------------------------------------------------
    // 4. Tes Endpoint Live Tracking: Kirim Titik Koordinat GPS
    // ---------------------------------------------------------------------
    const trackingPayload = JSON.stringify({
        latitude: -7.235525 + (Math.random() - 0.5) * 0.01,
        longitude: 112.735522 + (Math.random() - 0.5) * 0.01,
        accuracy: 12.5,
    });

    const trackingRes = http.post(`${BASE_URL}/tracking`, trackingPayload, {
        headers: {
            ...authHeaders,
            'Content-Type': 'application/json',
        },
    });

    check(trackingRes, {
        'tracking status 200/201': (r) => r.status === 200 || r.status === 201,
    });

    sleep(2); // Jeda sebelum mengulang iterasi berikutnya
}
