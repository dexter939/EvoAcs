<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\RouterManufacturer;

class RouterProductsSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // ASUS - WiFi 7 Gaming Routers
            ['manufacturer' => 'ASUS', 'model' => 'ROG Rapture GT-BE98', 'wifi' => 'WiFi 7', 'speed' => '25 Gbps', 'year' => 2024, 'price' => 699.00, 'features' => 'Quad-band, MLO, Premio iF Design 2024, 4096-QAM', 'line' => 'ROG Rapture', 'form' => 'Desktop', 'gaming' => true],
            ['manufacturer' => 'ASUS', 'model' => 'ROG Rapture GT-BE19000', 'wifi' => 'WiFi 7', 'speed' => '19 Gbps', 'year' => 2024, 'price' => 599.00, 'features' => 'Tri-band, 320 MHz, porta 10G, Guest Network Pro', 'line' => 'ROG Rapture', 'form' => 'Desktop', 'gaming' => true],
            ['manufacturer' => 'ASUS', 'model' => 'TUF Gaming BE6500', 'wifi' => 'WiFi 7', 'speed' => '6.5 Gbps', 'year' => 2024, 'price' => 399.00, 'features' => 'Design drone-inspired, porte 2.5G multiple', 'line' => 'TUF Gaming', 'form' => 'Desktop', 'gaming' => true],
            ['manufacturer' => 'ASUS', 'model' => 'TUF Gaming BE3600', 'wifi' => 'WiFi 7', 'speed' => '3.6 Gbps', 'year' => 2024, 'price' => 249.00, 'features' => 'Entry-level gaming, 4 antenne, porta 2.5G', 'line' => 'TUF Gaming', 'form' => 'Desktop', 'gaming' => true],

            // ASUS - WiFi 7 Consumer Routers
            ['manufacturer' => 'ASUS', 'model' => 'RT-BE96U', 'wifi' => 'WiFi 7', 'speed' => '19 Gbps', 'year' => 2023, 'price' => 549.00, 'features' => 'Primo WiFi 7 ASUS consumer, AiMesh, Tri-band', 'line' => 'RT', 'form' => 'Desktop', 'gaming' => false],
            ['manufacturer' => 'ASUS', 'model' => 'RT-BE92U', 'wifi' => 'WiFi 7', 'speed' => '9.7 Gbps', 'year' => 2024, 'price' => 449.00, 'features' => 'Porta 10G WAN/LAN, 2750 sq.ft. copertura, AiMesh', 'line' => 'RT', 'form' => 'Desktop', 'gaming' => false],
            ['manufacturer' => 'ASUS', 'model' => 'RT-BE86U', 'wifi' => 'WiFi 7', 'speed' => '6.8 Gbps', 'year' => 2024, 'price' => 379.00, 'features' => '1x 10G + 4x 2.5G porte, CPU quad-core 2.6 GHz, MLO', 'line' => 'RT', 'form' => 'Desktop', 'gaming' => false],
            ['manufacturer' => 'ASUS', 'model' => 'RT-BE58U', 'wifi' => 'WiFi 7', 'speed' => '5.8 Gbps', 'year' => 2024, 'price' => 299.00, 'features' => 'Travel router tri-mode, 2000 sq.ft., VPN integrato', 'line' => 'RT', 'form' => 'Portable', 'gaming' => false],

            // ASUS - WiFi 7 Mesh Systems
            ['manufacturer' => 'ASUS', 'model' => 'ZenWiFi BQ16 Pro', 'wifi' => 'WiFi 7', 'speed' => '16 Gbps+', 'year' => 2024, 'price' => 899.00, 'features' => 'Mesh flagship, velocità 2.62 Gbps test reali', 'line' => 'ZenWiFi', 'form' => 'Mesh 2-pack', 'mesh' => true],
            ['manufacturer' => 'ASUS', 'model' => 'ZenWiFi BQ16', 'wifi' => 'WiFi 7', 'speed' => '16 Gbps+', 'year' => 2024, 'price' => 799.00, 'features' => 'Mesh system, AiMesh, copertura estesa', 'line' => 'ZenWiFi', 'form' => 'Mesh 2-pack', 'mesh' => true],
            ['manufacturer' => 'ASUS', 'model' => 'ZenWiFi BD4 Outdoor', 'wifi' => 'WiFi 7', 'speed' => '3.6 Gbps', 'year' => 2024, 'price' => 449.00, 'features' => 'IP65 waterproof, dual 2.5G PoE, outdoor installation', 'line' => 'ZenWiFi', 'form' => 'Outdoor', 'mesh' => true],
            ['manufacturer' => 'ASUS', 'model' => 'ZenMesh BT8', 'wifi' => 'WiFi 7', 'speed' => 'N/A', 'year' => 2025, 'price' => null, 'features' => 'Mid-range mesh system', 'line' => 'ZenMesh', 'form' => 'Mesh 2-pack', 'mesh' => true],
            ['manufacturer' => 'ASUS', 'model' => 'ZenMesh BD5', 'wifi' => 'WiFi 7', 'speed' => 'N/A', 'year' => 2025, 'price' => null, 'features' => 'Entry-level mesh', 'line' => 'ZenMesh', 'form' => 'Mesh 2-pack', 'mesh' => true],

            // ASUS - WiFi 6/6E
            ['manufacturer' => 'ASUS', 'model' => 'RT-AX58U', 'wifi' => 'WiFi 6', 'speed' => '3 Gbps', 'year' => 2023, 'price' => 149.00, 'features' => 'Budget-friendly, popolare rapporto qualità/prezzo', 'line' => 'RT', 'form' => 'Desktop', 'gaming' => false],
            ['manufacturer' => 'ASUS', 'model' => 'RT-AX59U', 'wifi' => 'WiFi 6', 'speed' => '3 Gbps', 'year' => 2023, 'price' => 139.00, 'features' => 'Dual-band, AiMesh expandable, famiglia', 'line' => 'RT', 'form' => 'Desktop', 'gaming' => false],
            ['manufacturer' => 'ASUS', 'model' => 'RT-AXE7800', 'wifi' => 'WiFi 6E', 'speed' => '7.8 Gbps', 'year' => 2023, 'price' => 299.00, 'features' => '6 antenne retrattili, 6GHz band, Tri-band', 'line' => 'RT', 'form' => 'Desktop', 'gaming' => false],

            // NETGEAR - WiFi 7 Nighthawk Series
            ['manufacturer' => 'Netgear', 'model' => 'Nighthawk RS700S', 'wifi' => 'WiFi 7', 'speed' => '19 Gbps', 'year' => 2023, 'price' => 699.00, 'features' => 'Primo WiFi 7 Netgear, Broadcom chip, 3500 sq.ft.', 'line' => 'Nighthawk', 'form' => 'Desktop', 'gaming' => false],
            ['manufacturer' => 'Netgear', 'model' => 'Nighthawk RS700', 'wifi' => 'WiFi 7', 'speed' => '19 Gbps', 'year' => 2023, 'price' => 699.00, 'features' => 'Tri-band, ultra-performance flagship', 'line' => 'Nighthawk', 'form' => 'Desktop', 'gaming' => false],
            ['manufacturer' => 'Netgear', 'model' => 'Nighthawk RS600', 'wifi' => 'WiFi 7', 'speed' => 'N/A', 'year' => 2024, 'price' => 499.00, 'features' => 'Mid-range WiFi 7, Nighthawk lineup expansion', 'line' => 'Nighthawk', 'form' => 'Desktop', 'gaming' => false],
            ['manufacturer' => 'Netgear', 'model' => 'Nighthawk RS500', 'wifi' => 'WiFi 7', 'speed' => 'N/A', 'year' => 2024, 'price' => 399.00, 'features' => 'Mid-range WiFi 7, secure networking', 'line' => 'Nighthawk', 'form' => 'Desktop', 'gaming' => false],
            ['manufacturer' => 'Netgear', 'model' => 'Nighthawk RS300', 'wifi' => 'WiFi 7', 'speed' => '9.3 Gbps', 'year' => 2024, 'price' => 299.00, 'features' => 'Budget WiFi 7, 2500 sq.ft., 200 dispositivi', 'line' => 'Nighthawk', 'form' => 'Desktop', 'gaming' => false],
            ['manufacturer' => 'Netgear', 'model' => 'Nighthawk RS200', 'wifi' => 'WiFi 7', 'speed' => 'N/A', 'year' => 2024, 'price' => 249.00, 'features' => 'Entry-level WiFi 7', 'line' => 'Nighthawk', 'form' => 'Desktop', 'gaming' => false],

            // NETGEAR - WiFi 7 Orbi Mesh
            ['manufacturer' => 'Netgear', 'model' => 'Orbi 970', 'wifi' => 'WiFi 7', 'speed' => 'N/A', 'year' => 2023, 'price' => 2299.00, 'features' => 'Flagship mesh system, 3-pack', 'line' => 'Orbi', 'form' => 'Mesh 3-pack', 'mesh' => true],
            ['manufacturer' => 'Netgear', 'model' => 'Orbi 870', 'wifi' => 'WiFi 7', 'speed' => 'N/A', 'year' => 2024, 'price' => 1699.00, 'features' => 'Tri-band mesh, prestazioni 627 Mbps a 50-75 ft', 'line' => 'Orbi', 'form' => 'Mesh 2-pack', 'mesh' => true],
            ['manufacturer' => 'Netgear', 'model' => 'Orbi 770', 'wifi' => 'WiFi 7', 'speed' => 'N/A', 'year' => 2024, 'price' => 999.00, 'features' => 'Tri-band mesh, affordable WiFi 7 mesh', 'line' => 'Orbi', 'form' => 'Mesh 2-pack', 'mesh' => true],
            ['manufacturer' => 'Netgear', 'model' => 'Orbi 370', 'wifi' => 'WiFi 7', 'speed' => 'N/A', 'year' => 2024, 'price' => 599.00, 'features' => 'Dual-band WiFi 7 (no 6GHz), budget mesh', 'line' => 'Orbi', 'form' => 'Mesh 2-pack', 'mesh' => true],

            // TP-LINK - WiFi 7 Gaming
            ['manufacturer' => 'TP-Link', 'model' => 'Archer GE800', 'wifi' => 'WiFi 7', 'speed' => 'N/A', 'year' => 2023, 'price' => 599.00, 'features' => 'Primo gaming router WiFi 7, design batwing, RGB', 'line' => 'Archer', 'form' => 'Desktop', 'gaming' => true],
            ['manufacturer' => 'TP-Link', 'model' => 'Archer GE650', 'wifi' => 'WiFi 7', 'speed' => 'N/A', 'year' => 2024, 'price' => 350.00, 'features' => 'Gaming triangolare compatto, RGB, 2+ Gbps', 'line' => 'Archer', 'form' => 'Desktop', 'gaming' => true],

            // TP-LINK - WiFi 7 Consumer
            ['manufacturer' => 'TP-Link', 'model' => 'Archer BE9300', 'wifi' => 'WiFi 7', 'speed' => '9.3 Gbps', 'year' => 2024, 'price' => 249.00, 'features' => 'Budget WiFi 7, ottimo rapporto qualità/prezzo', 'line' => 'Archer', 'form' => 'Desktop', 'gaming' => false],

            // AVM FRITZ!Box - WiFi 7
            ['manufacturer' => 'AVM', 'model' => 'FRITZ!Box 5690 Pro', 'wifi' => 'WiFi 7', 'speed' => '18.5 Gbps', 'year' => 2023, 'price' => 499.00, 'features' => 'Glasfaser+DSL Hybrid, Tri-band, GPON/AON, Zigbee+DECT-ULE', 'line' => 'FRITZ!Box', 'form' => 'Desktop', 'gaming' => false],
            ['manufacturer' => 'AVM', 'model' => 'FRITZ!Box 5690 XGS', 'wifi' => 'WiFi 7', 'speed' => '10 Gbps', 'year' => 2024, 'price' => 549.00, 'features' => 'XGS-PON 10 Gbps, 10G LAN port, Zigbee support', 'line' => 'FRITZ!Box', 'form' => 'Desktop', 'gaming' => false],
            ['manufacturer' => 'AVM', 'model' => 'FRITZ!Box 5690', 'wifi' => 'WiFi 7', 'speed' => 'N/A', 'year' => 2025, 'price' => null, 'features' => 'Glasfaser, GPON/AON compatible', 'line' => 'FRITZ!Box', 'form' => 'Desktop', 'gaming' => false],
            ['manufacturer' => 'AVM', 'model' => 'FRITZ!Box 4690', 'wifi' => 'WiFi 7', 'speed' => 'N/A', 'year' => 2025, 'price' => null, 'features' => 'Glasfaser WLAN router', 'line' => 'FRITZ!Box', 'form' => 'Desktop', 'gaming' => false],
            ['manufacturer' => 'AVM', 'model' => 'FRITZ!Box 7690', 'wifi' => 'WiFi 7', 'speed' => '7.2 Gbps', 'year' => 2024, 'price' => 399.00, 'features' => 'DSL, 4x4 antennas, 2x 2.5G LAN, Zigbee', 'line' => 'FRITZ!Box', 'form' => 'Desktop', 'gaming' => false],
            ['manufacturer' => 'AVM', 'model' => 'FRITZ!Box 7682', 'wifi' => 'WiFi 7', 'speed' => '1 Gbps', 'year' => 2023, 'price' => 349.00, 'features' => 'G.fast up to 1 Gbps, Supervectoring 35b (300 Mbps)', 'line' => 'FRITZ!Box', 'form' => 'Desktop', 'gaming' => false],
            ['manufacturer' => 'AVM', 'model' => 'FRITZ!Box 6670 Cable', 'wifi' => 'WiFi 7', 'speed' => 'N/A', 'year' => 2023, 'price' => 379.00, 'features' => 'DOCSIS 3.1, Gigabit speeds, Zigbee+DECT-ULE', 'line' => 'FRITZ!Box', 'form' => 'Desktop', 'gaming' => false],

            // AVM FRITZ!Box - 5G/Mobile
            ['manufacturer' => 'AVM', 'model' => 'FRITZ!Box 6860 5G', 'wifi' => 'WiFi 6', 'speed' => '1.3 Gbps', 'year' => 2024, 'price' => 449.00, 'features' => '5G 1.3 Gbps, compact/weatherproof, PoE, VoLTE/VoNR', 'line' => 'FRITZ!Box', 'form' => 'Desktop/Outdoor', 'gaming' => false],
            ['manufacturer' => 'AVM', 'model' => 'FRITZ!Box 6850 5G', 'wifi' => 'WiFi 6', 'speed' => 'N/A', 'year' => 2024, 'price' => 399.00, 'features' => '5G + LTE Cat 19, comprehensive feature set', 'line' => 'FRITZ!Box', 'form' => 'Desktop', 'gaming' => false],

            // AVM FRITZ!Box - WiFi 6
            ['manufacturer' => 'AVM', 'model' => 'FRITZ!Box 6690 Cable', 'wifi' => 'WiFi 6', 'speed' => 'N/A', 'year' => 2023, 'price' => 299.00, 'features' => 'Top cable model, DOCSIS 3.1', 'line' => 'FRITZ!Box', 'form' => 'Desktop', 'gaming' => false],
            ['manufacturer' => 'AVM', 'model' => 'FRITZ!Box 7590 AX', 'wifi' => 'WiFi 6', 'speed' => 'N/A', 'year' => 2023, 'price' => 249.00, 'features' => 'DSL standard model', 'line' => 'FRITZ!Box', 'form' => 'Desktop', 'gaming' => false],
        ];

        foreach ($products as $product) {
            $manufacturer = RouterManufacturer::where('name', $product['manufacturer'])->first();
            
            if ($manufacturer) {
                DB::table('router_products')->insert([
                    'manufacturer_id' => $manufacturer->id,
                    'model_name' => $product['model'],
                    'wifi_standard' => $product['wifi'],
                    'max_speed' => $product['speed'],
                    'release_year' => $product['year'],
                    'price_usd' => $product['price'],
                    'key_features' => $product['features'],
                    'product_line' => $product['line'],
                    'form_factor' => $product['form'],
                    'mesh_support' => $product['mesh'] ?? false,
                    'gaming_features' => $product['gaming'] ?? false,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        $this->command->info('✅ Loaded ' . count($products) . ' router products');
    }
}
