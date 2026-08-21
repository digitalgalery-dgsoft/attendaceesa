import http from 'k6/http';
import { check, sleep } from 'k6';

/**
 * =========================================================================
 * SKRIP STRESS TEST K6 - APLIKASI PRESENSI ESA / AMK
 * =========================================================================
 * 
 * Cara Menjalankan:
 * 1. Buka Terminal / PowerShell di folder ini:
 *    cd "g:\My File\Project APlikasi Absensi\New\load_testing"
 * 
 * 2. Jalankan perintah k6:
 *    k6 run stress_test.js
 * 
 * Skenario Pengujian:
 * - 30 detik pertama: Naik ke 50 User serentak
 * - 1 menit berikutnya: Naik ke 150 User serentak (Jam sibuk presensi)
 * - 1 menit puncak: 300 User serentak (Peak Rush Hour)
 * - 30 detik terakhir: Pendinginan (Cooldown) kembali ke 0
 * =========================================================================
 */

export const options = {
    stages: [
        { duration: '30s', target: 50 },   // Warm-up ke 50 user
        { duration: '1m',  target: 150 },  // Naik ke 150 user
        { duration: '1m',  target: 300 },  // Peak 300 user serentak
        { duration: '30s', target: 0 },    // Cooldown
    ],
    thresholds: {
        // Target: 95% request harus selesai di bawah 2 detik
        http_req_duration: ['p(95)<2000'],
        // Target: Tingkat error (500 / 504 / 429) harus di bawah 2%
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
        'settings response fast': (r) => r.timings.duration < 1000,
    });

    sleep(1); // Simulasi jeda user membuka aplikasi

    // ---------------------------------------------------------------------
    // 2. Tes Endpoint Login Karyawan (Simulasi Otentikasi)
    // ---------------------------------------------------------------------
    const loginPayload = JSON.stringify({
        email: 'jamil@dgsoft.id', // Sesuaikan dengan akun test atau NIK
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

    sleep(2);

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

    sleep(3); // Jeda sebelum iterasi berikutnya
}
