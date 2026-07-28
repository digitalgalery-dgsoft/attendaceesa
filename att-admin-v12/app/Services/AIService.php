<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\SalesReport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    public static function generateSalesAnalysis(SalesReport $report)
    {
        $setting = Setting::first();
        $sumopodKey = $setting ? $setting->sumopod_api_key : null;
        $sumopodModel = $setting ? $setting->sumopod_model : null;
        
        // Fallback to defaults if not set
        if (!$sumopodModel) {
            $sumopodModel = 'gpt-4o-mini';
        }

        if (!$sumopodKey) {
            return "Error: API Key Sumopod belum dikonfigurasi di halaman AI Configurations.";
        }

        $revenueFormatted = "Rp " . number_format((float) $report->revenue, 0, ',', '.');
        
        $prompt = "Tolong berikan analisa singkat dan saran langkah selanjutnya (follow up) untuk laporan aktivitas sales berikut:\n" .
                  "- Klien: " . $report->client_name . "\n" .
                  "- Perusahaan: " . ($report->client_company ?? '-') . "\n" .
                  "- Nilai Pendapatan/Revenue: " . $revenueFormatted . "\n" .
                  "- Status Saat Ini: " . $report->status . "\n" .
                  "- Catatan Sales: " . ($report->notes ?? '-') . "\n\n" .
                  "Berikan respon yang profesional, singkat, dan berikan poin-poin saran strategis yang bisa dilakukan oleh tim sales. Jangan bertele-tele.";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $sumopodKey,
            ])
            ->timeout(30)
            ->post('https://ai.sumopod.com/v1/chat/completions', [
                'model' => $sumopodModel,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Anda adalah asisten AI ahli dalam strategi penjualan dan CRM.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 300,
                'temperature' => 0.7
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? "Berhasil terhubung ke AI, tapi respon kosong.";
            }

            Log::error('Sumopod API Error: ' . $response->body());
            
            // Try to parse error message
            $errorBody = json_decode($response->body(), true);
            $errorMsg = $errorBody['error']['message'] ?? $response->status();
            
            return "Error dari AI API: " . $errorMsg;

        } catch (\Exception $e) {
            Log::error('Sumopod Exception: ' . $e->getMessage());
            return "Terjadi kesalahan koneksi ke AI: " . $e->getMessage();
        }
    }
}
