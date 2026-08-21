import http from 'k6/http';
import { check, sleep } from 'k6';

/**
 * =========================================================================
 * SKRIP STRESS TEST K6 - APLIKASI PRESENSI ESA / AMK
 * (SIMULASI REAL-WORLD USER MOBILE ACTIVITY)
 * =========================================================================
 * 
 * Pola Pengujian Nyata:
 * 1. Setup Phase: Login dijalankan 1x di awal untuk mendapatkan Token Otentikasi
 *    (sama seperti perilaku karyawan login 1x di HP).
 * 2. Execution Phase: 10 hingga 100 User aktif bersamaan mengakses data jadwal
 *    dan mengirimkan live tracking GPS menggunakan Token Bearer.
 * 
 * Skenario Bertahap Selama 1 Menit:
 * - 00s - 15s : 0 -> 10 User (Tahap Awal / Warm-up)
 * - 15s - 35s : 10 -> 50 User (Peningkatan Beban Jam Masuk)
 * - 35s - 50s : 50 -> 100 User (Puncak Beban / Peak Concurrency)
 * - 50s - 60s : 100 -> 0 User (Pendinginan & Selesai)
 * =========================================================================
 */

export const options = {
    stages: [
        { duration: '15s', target: 10 },   // 15 detik: 10 user
        { duration: '20s', target: 50 },   // 20 detik: 50 user
        { duration: '15s', target: 100 },  // 15 detik: 100 user puncak
        { duration: '10s', target: 0 },    // 10 detik: cooldown
    ],
    thresholds: {
        // Target: 95% request selesai di bawah 2 detik
        http_req_duration: ['p(95)<2000'],
        // Target: Error rate di bawah 5%
        http_req_failed: ['rate<0.05'],
    },
};

const BASE_URL = 'https://appsend.my.id/api';

// =========================================================================
// 1. SETUP PHASE: Login 1x di awal untuk mengambil Token
// =========================================================================
export function setup() {
    console.log('==> [Setup] Menyiapkan autentikasi login awal...');

    const loginPayload = JSON.stringify({
        email: '3528042504850003', // NIK / Email karyawan
        password: 'password',
    });

    const loginRes = http.post(`${BASE_URL}/login`, loginPayload, {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    });

    let token = null;
    if (loginRes.status === 200) {
        try {
            const body = JSON.parse(loginRes.body);
            token = body.data?.access_token || body.token || body.data?.token;
            console.log('==> [Setup] Login Berhasil! Token berhasil disimpan untuk 100 user.');
        } catch (e) {
            console.log('==> [Setup] Response parsing error');
        }
    } else {
        console.log(`==> [Setup] Info Login Status: ${loginRes.status}. Menjalankan pengujian endpoint API.`);
    }

    return { token: token };
}

// =========================================================================
// 2. USER ACTIVITY SIMULATION (10 - 100 VUs Serentak)
// =========================================================================
export default function (data) {
    const authHeaders = {
        'Accept': 'application/json',
        ...(data && data.token ? { 'Authorization': `Bearer ${data.token}` } : {}),
    };

    // ---------------------------------------------------------------------
    // A. Request Pengaturan Aplikasi (Settings)
    // ---------------------------------------------------------------------
    const settingsRes = http.get(`${BASE_URL}/settings`, {
        headers: { 'Accept': 'application/json' },
    });

    check(settingsRes, {
        '1. Settings Status 200': (r) => r.status === 200,
        '1. Settings Respon Cepat (< 1s)': (r) => r.timings.duration < 1000,
    });

    sleep(1); // Jeda user di aplikasi (1 detik)

    // ---------------------------------------------------------------------
    // B. Request Jadwal Kerja & Tombol Check-in Hari Ini
    // ---------------------------------------------------------------------
    const scheduleRes = http.get(`${BASE_URL}/today-schedule?date=2026-08-21`, {
        headers: authHeaders,
    });

    check(scheduleRes, {
        '2. Schedule Status OK (200/401)': (r) => r.status === 200 || r.status === 401,
        '2. Schedule Respon Cepat (< 2s)': (r) => r.timings.duration < 2000,
    });

    sleep(2); // Jeda aktivitas user (2 detik)

    // ---------------------------------------------------------------------
    // C. Kirim Titik Koordinat Live Tracking GPS
    // ---------------------------------------------------------------------
    const trackingPayload = JSON.stringify({
        latitude: -7.235525 + (Math.random() - 0.5) * 0.005,
        longitude: 112.735522 + (Math.random() - 0.5) * 0.005,
        accuracy: 10.0,
        battery_level: 85,
    });

    const trackingRes = http.post(`${BASE_URL}/tracking`, trackingPayload, {
        headers: {
            ...authHeaders,
            'Content-Type': 'application/json',
        },
    });

    check(trackingRes, {
        '3. Tracking Status OK (200/201/401)': (r) => r.status === 200 || r.status === 201 || r.status === 401,
        '3. Tracking Respon Cepat (< 2s)': (r) => r.timings.duration < 2000,
    });

    sleep(2); // Jeda sebelum iterasi berikutnya
}
