import http from 'k6/http';
import { check, sleep } from 'k6';

/**
 * =========================================================================
 * SKRIP STRESS TEST K6 - APLIKASI PRESENSI ESA / AMK
 * =========================================================================
 * 
 * Skenario Pengujian: Bertahap Selama 1 Menit (10 hingga 100 User)
 * - 15 detik: Naik ke 10 User (Warm-up)
 * - 20 detik: Naik ke 50 User (Peningkatan Beban)
 * - 15 detik: Puncak di 100 User Serentak (Peak Rush Hour)
 * - 10 detik: Cooldown kembali ke 0 User
 * 
 * Total Durasi: 1 Menit (60 Detik)
 * =========================================================================
 */

export const options = {
    stages: [
        { duration: '15s', target: 10 },   // Tahap 1: Naik ke 10 user
        { duration: '20s', target: 50 },   // Tahap 2: Naik ke 50 user
        { duration: '15s', target: 100 },  // Tahap 3: Puncak di 100 user
        { duration: '10s', target: 0 },    // Tahap 4: Cooldown kembali ke 0
    ],
    thresholds: {
        // Target: 95% request selesai di bawah 2 detik
        http_req_duration: ['p(95)<2000'],
        // Target: Error rate di bawah 2%
        http_req_failed: ['rate<0.02'],
    },
};

const BASE_URL = 'https://appsend.my.id/api';

// =========================================================================
// KONFIGURASI AKUN PENGUJIAN:
// Masukkan NIK / Email dan Password salah satu akun karyawan yang aktif
// =========================================================================
const TEST_CREDENTIALS = {
    email: '3528042504850003', // NIK atau Email karyawan
    password: 'password',      // Password akun karyawan
};

// Atau isi Token Bearer langsung jika sudah punya token login:
const DIRECT_BEARER_TOKEN = '';

export default function () {
    // ---------------------------------------------------------------------
    // 1. Tes Endpoint Publik: Pengaturan Aplikasi (Settings)
    // ---------------------------------------------------------------------
    const settingsRes = http.get(`${BASE_URL}/settings`, {
        headers: { 'Accept': 'application/json' },
    });

    check(settingsRes, {
        'settings status 200': (r) => r.status === 200,
        'settings response < 1s': (r) => r.timings.duration < 1000,
    });

    sleep(1); // Simulasi jeda user membuka aplikasi

    // ---------------------------------------------------------------------
    // 2. Simulasi Login Karyawan
    // ---------------------------------------------------------------------
    let token = DIRECT_BEARER_TOKEN;

    if (!token && TEST_CREDENTIALS.email) {
        const loginPayload = JSON.stringify({
            email: TEST_CREDENTIALS.email,
            password: TEST_CREDENTIALS.password,
        });

        const loginRes = http.post(`${BASE_URL}/login`, loginPayload, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        });

        const loginSuccess = check(loginRes, {
            'login response received': (r) => r.status === 200 || r.status === 401,
        });

        if (loginRes.status === 200) {
            try {
                const body = JSON.parse(loginRes.body);
                token = body.data?.access_token || body.token || body.data?.token;
            } catch (e) {}
        }
    }

    sleep(1);

    // ---------------------------------------------------------------------
    // 3. Tes Endpoint Terotentikasi: Memuat Jadwal Kerja Hari Ini
    // ---------------------------------------------------------------------
    if (token) {
        const authHeaders = {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`,
        };

        const scheduleRes = http.get(`${BASE_URL}/today-schedule?date=2026-08-21`, {
            headers: authHeaders,
        });

        check(scheduleRes, {
            'schedule status 200': (r) => r.status === 200,
            'schedule response < 1.5s': (r) => r.timings.duration < 1500,
        });

        sleep(1.5);

        // -----------------------------------------------------------------
        // 4. Tes Endpoint Live Tracking: Kirim Titik Koordinat GPS
        // -----------------------------------------------------------------
        const trackingPayload = JSON.stringify({
            latitude: -7.235525 + (Math.random() - 0.5) * 0.01,
            longitude: 112.735522 + (Math.random() - 0.5) * 0.01,
            accuracy: 10.0,
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
    }

    sleep(2); // Jeda sebelum mengulang iterasi berikutnya
}
