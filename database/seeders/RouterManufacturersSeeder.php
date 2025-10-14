<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RouterManufacturersSeeder extends Seeder
{
    public function run(): void
    {
        $manufacturers = [
            // TOP TIER (Premium/Gaming)
            [
                'name' => 'ASUS',
                'oui_prefix' => '00:1E:8C,AC:22:0B,2C:FD:A1',
                'category' => 'premium',
                'country' => 'Taiwan',
                'product_lines' => 'RT-AC, RT-AX, ROG Rapture',
                'tr069_support' => true,
                'tr369_support' => true,
                'notes' => 'Include linea ROG (Republic of Gamers) per gaming. WiFi 6/6E/7'
            ],
            [
                'name' => 'Netgear',
                'oui_prefix' => 'A0:63:91,E0:46:9A,B0:B9:8A',
                'category' => 'premium',
                'country' => 'USA',
                'product_lines' => 'Nighthawk, Orbi, Armor',
                'tr069_support' => true,
                'tr369_support' => true,
                'notes' => 'Linea Nighthawk per prestazioni elevate. Leader nel gaming'
            ],
            [
                'name' => 'AVM',
                'oui_prefix' => '00:04:0E,7C:AE:FA,C0:25:06',
                'category' => 'premium',
                'country' => 'Germany',
                'product_lines' => 'FRITZ!Box',
                'tr069_support' => true,
                'tr369_support' => true,
                'notes' => 'Serie FRITZ!Box, molto diffusa in Germania. Eccellente supporto TR-064'
            ],
            [
                'name' => 'Cisco',
                'oui_prefix' => '00:1E:14,00:26:98,C0:8C:60',
                'category' => 'enterprise',
                'country' => 'USA',
                'product_lines' => 'RV, ISR, ASR',
                'tr069_support' => true,
                'tr369_support' => false,
                'notes' => 'Principalmente router enterprise, alcuni modelli SMB'
            ],

            // FASCIA MEDIA/GENERALISTA
            [
                'name' => 'TP-Link',
                'oui_prefix' => '50:C7:BF,60:32:B1,A4:2B:B0',
                'category' => 'mainstream',
                'country' => 'China',
                'product_lines' => 'Archer, Deco, Omada',
                'tr069_support' => true,
                'tr369_support' => true,
                'notes' => 'Leader mondiale ~18% market share. Vulnerabilità sicurezza segnalate USA 2024'
            ],
            [
                'name' => 'Linksys',
                'oui_prefix' => '00:14:BF,C0:56:27,14:91:82',
                'category' => 'mainstream',
                'country' => 'USA',
                'product_lines' => 'Velop, MR, EA',
                'tr069_support' => true,
                'tr369_support' => true,
                'notes' => 'Storico produttore americano, ora di Belkin/Foxconn'
            ],
            [
                'name' => 'D-Link',
                'oui_prefix' => '00:05:5D,14:D6:4D,B8:A3:86',
                'category' => 'mainstream',
                'country' => 'Taiwan',
                'product_lines' => 'DIR, DAP, DSL',
                'tr069_support' => true,
                'tr369_support' => false,
                'notes' => 'Produttore taiwanese, gamma molto ampia'
            ],
            [
                'name' => 'Tenda',
                'oui_prefix' => 'C8:3A:35,E8:AB:FA,50:FA:84',
                'category' => 'budget',
                'country' => 'China',
                'product_lines' => 'AC, AX, MW',
                'tr069_support' => true,
                'tr369_support' => false,
                'notes' => 'Ottimo rapporto qualità/prezzo, gamma economica'
            ],
            [
                'name' => 'Huawei',
                'oui_prefix' => '00:E0:FC,78:D0:04,24:DA:33',
                'category' => 'mainstream',
                'country' => 'China',
                'product_lines' => 'AX, HG, B',
                'tr069_support' => true,
                'tr369_support' => true,
                'notes' => 'Router 4G/5G e domestici. Forte su TR-069/TR-181'
            ],

            // SISTEMI MESH E SMART
            [
                'name' => 'Google',
                'oui_prefix' => 'F4:F5:D8,6C:AD:F8,00:1A:11',
                'category' => 'mesh',
                'country' => 'USA',
                'product_lines' => 'Nest Wifi, Google Wifi',
                'tr069_support' => false,
                'tr369_support' => false,
                'notes' => 'Sistemi mesh consumer. Gestione via app Google Home'
            ],
            [
                'name' => 'Amazon',
                'oui_prefix' => '74:C2:46,B0:4E:26,FC:A6:67',
                'category' => 'mesh',
                'country' => 'USA',
                'product_lines' => 'eero, eero Pro',
                'tr069_support' => false,
                'tr369_support' => false,
                'notes' => 'Sistemi mesh acquisiti da Amazon. Integrazione Alexa'
            ],
            [
                'name' => 'Ubiquiti',
                'oui_prefix' => '00:15:6D,24:A4:3C,78:8A:20',
                'category' => 'prosumer',
                'country' => 'USA',
                'product_lines' => 'UniFi, EdgeRouter, AmpliFi',
                'tr069_support' => false,
                'tr369_support' => false,
                'notes' => 'Soluzioni prosumer/enterprise. UniFi molto popolare'
            ],

            // ALTRI PRODUTTORI RILEVANTI
            [
                'name' => 'Xiaomi',
                'oui_prefix' => '34:CE:00,64:09:80,F4:8E:92',
                'category' => 'budget',
                'country' => 'China',
                'product_lines' => 'Mi Router, Redmi Router',
                'tr069_support' => true,
                'tr369_support' => false,
                'notes' => 'Router economici, integrazione smart home Xiaomi'
            ],
            [
                'name' => 'ZTE',
                'oui_prefix' => '68:DB:F5,B0:75:0E,54:4A:16',
                'category' => 'telco',
                'country' => 'China',
                'product_lines' => 'H, MF, ZXA',
                'tr069_support' => true,
                'tr369_support' => true,
                'notes' => 'Router per telco operators. Ottimo supporto TR-069/181'
            ],
            [
                'name' => 'ZyXEL',
                'oui_prefix' => '00:A0:C5,4C:ED:DE,B0:B2:DC',
                'category' => 'mainstream',
                'country' => 'Taiwan',
                'product_lines' => 'VMG, NBG, USG',
                'tr069_support' => true,
                'tr369_support' => true,
                'notes' => 'Produttore completo, forte su VDSL/FTTH'
            ],
            [
                'name' => 'Alcatel-Lucent',
                'oui_prefix' => '00:23:8E,A8:D0:E5,E0:B9:4D',
                'category' => 'telco',
                'country' => 'France',
                'product_lines' => 'I-series, HH series',
                'tr069_support' => true,
                'tr369_support' => true,
                'notes' => 'Router per ISP europei'
            ],
            [
                'name' => 'Belkin',
                'oui_prefix' => '00:30:BD,EC:1A:59,94:44:52',
                'category' => 'mainstream',
                'country' => 'USA',
                'product_lines' => 'AC, N',
                'tr069_support' => false,
                'tr369_support' => false,
                'notes' => 'Produttore storico, ora proprietario di Linksys'
            ],
            [
                'name' => 'Devolo',
                'oui_prefix' => '00:50:67,D8:15:0D,F0:84:2F',
                'category' => 'powerline',
                'country' => 'Germany',
                'product_lines' => 'Magic, dLAN',
                'tr069_support' => true,
                'tr369_support' => false,
                'notes' => 'Specializzato in PowerLine con WiFi'
            ],

            // PRODUTTORI TELCO/ISP
            [
                'name' => 'Technicolor',
                'oui_prefix' => '00:14:7D,00:1F:9F,E4:83:99',
                'category' => 'telco',
                'country' => 'France',
                'product_lines' => 'TG, TC, DWA',
                'tr069_support' => true,
                'tr369_support' => true,
                'notes' => 'Leader mondiale router ISP. Ottimo supporto TR-069'
            ],
            [
                'name' => 'Sercomm',
                'oui_prefix' => '00:0C:E5,00:15:F2,64:16:F0',
                'category' => 'telco',
                'country' => 'Taiwan',
                'product_lines' => 'Router white-label per ISP',
                'tr069_support' => true,
                'tr369_support' => true,
                'notes' => 'OEM per ISP, produce per TIM, Vodafone, etc'
            ],
            [
                'name' => 'Sagemcom',
                'oui_prefix' => '00:07:CB,44:23:7C,80:1F:02',
                'category' => 'telco',
                'country' => 'France',
                'product_lines' => 'F@st, LiveBox',
                'tr069_support' => true,
                'tr369_support' => true,
                'notes' => 'Router per ISP europei, buon supporto TR-069/181'
            ]
        ];

        foreach ($manufacturers as $manufacturer) {
            DB::table('router_manufacturers')->updateOrInsert(
                ['name' => $manufacturer['name']],
                array_merge($manufacturer, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }

        $this->command->info('✅ Loaded ' . count($manufacturers) . ' router manufacturers');
    }
}
