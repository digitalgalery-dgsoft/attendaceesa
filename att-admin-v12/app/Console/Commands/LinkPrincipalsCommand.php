<?php

namespace App\Console\Commands;

use App\Models\Principal;
use App\Models\ReportTemplate;
use Illuminate\Console\Command;

class LinkPrincipalsCommand extends Command
{
    protected $signature = 'reporting:link-principals';
    protected $description = 'Menghubungkan seluruh Report Template ke data Principal Klien resmi di database';

    public function handle(): int
    {
        $this->info('Memulai penataan dan sinkronisasi relasi Template Laporan -> Principal...');

        // 1. Pastikan Principal Utama Terdaftar
        $principals = [
            'fonterra' => Principal::firstOrCreate(
                ['code' => 'PR-FONTERRA'],
                [
                    'name' => 'PT FONTERRA BRANDS INDONESIA',
                    'subdomain' => 'fonterra',
                    'portal_title' => 'Portal Pelaporan & Monitoring Fonterra Brands',
                    'theme_color' => '#003399',
                    'is_active' => true,
                ]
            ),
            'dulux' => Principal::firstOrCreate(
                ['code' => 'PR-ICI-PAINTS'],
                [
                    'name' => 'PT ICI PAINTS INDONESIA (DULUX)',
                    'subdomain' => 'dulux',
                    'portal_title' => 'Portal Pelaporan & Monitoring Dulux (ICI Paints)',
                    'theme_color' => '#0F52BA',
                    'is_active' => true,
                ]
            ),
            'wings' => Principal::firstOrCreate(
                ['code' => 'PR-WINGS'],
                [
                    'name' => 'WINGS GROUP INDONESIA',
                    'subdomain' => 'wings',
                    'portal_title' => 'Portal Pelaporan & Monitoring Wings Group',
                    'theme_color' => '#D32F2F',
                    'is_active' => true,
                ]
            ),
            'mamasuka' => Principal::firstOrCreate(
                ['code' => 'PR-DAESANG'],
                [
                    'name' => 'PT DAESANG AGUNG INDONESIA (MAMASUKA)',
                    'subdomain' => 'mamasuka',
                    'portal_title' => 'Portal Pelaporan & Monitoring Daesang MamaSuka',
                    'theme_color' => '#FF6F00',
                    'is_active' => true,
                ]
            ),
            'sidomuncul' => Principal::firstOrCreate(
                ['code' => 'PR-SIDOMUNCUL'],
                [
                    'name' => 'PT INDUSTRI JAMU DAN FARMASI SIDO MUNCUL',
                    'subdomain' => 'sidomuncul',
                    'portal_title' => 'Portal Pelaporan & Monitoring Sido Muncul',
                    'theme_color' => '#2E7D32',
                    'is_active' => true,
                ]
            ),
        ];

        $templates = ReportTemplate::all();
        $linkedCount = 0;

        foreach ($templates as $tpl) {
            $text = strtoupper($tpl->code . ' ' . $tpl->title);
            $targetPrincipal = null;

            if (str_contains($text, 'FONTERRA') || str_contains($text, 'ANLENE') || str_contains($text, 'BONEETO') || str_contains($text, 'ANCHOR')) {
                $targetPrincipal = $principals['fonterra'];
            } elseif (str_contains($text, 'DULUX') || str_contains($text, 'ICI') || str_contains($text, 'AKZONOBEL') || str_contains($text, 'TINTER') || str_contains($text, 'CATYLAC')) {
                $targetPrincipal = $principals['dulux'];
            } elseif (str_contains($text, 'WINGS') || str_contains($text, 'SAYAP')) {
                $targetPrincipal = $principals['wings'];
            } elseif (str_contains($text, 'MAMASUKA') || str_contains($text, 'DAESANG') || str_contains($text, 'MIWON')) {
                $targetPrincipal = $principals['mamasuka'];
            } elseif (str_contains($text, 'SIDO') || str_contains($text, 'MUNCUL') || str_contains($text, 'TOLAK ANGIN')) {
                $targetPrincipal = $principals['sidomuncul'];
            }

            if ($targetPrincipal) {
                $tpl->principal_id = $targetPrincipal->id;
                $tpl->save();
                $tpl->principals()->syncWithoutDetaching([$targetPrincipal->id]);
                $linkedCount++;
                $this->line(" - Template [{$tpl->code}] linked to {$targetPrincipal->name}");
            }
        }

        $this->info("Selesai! Sebanyak {$linkedCount} template berhasil dihubungkan ke Principal.");
        return 0;
    }
}
