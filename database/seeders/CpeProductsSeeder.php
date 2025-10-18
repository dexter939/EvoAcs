<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RouterManufacturer;
use App\Models\RouterProduct;

class CpeProductsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔄 Clearing existing CPE data...');
        RouterProduct::query()->delete();
        RouterManufacturer::query()->delete();

        $manufacturers = [
            [
                'name' => 'Huawei',
                'website' => 'https://www.huawei.com',
                'country' => 'China',
                'description' => 'Leading global provider of ICT infrastructure and smart devices',
                'products' => [
                    ['model_name' => '5G CPE Pro (H112-370)', 'oui' => '001882', 'product_class' => '5G CPE', 'release_year' => 2020, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 7200, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => true],
                    ['model_name' => '5G CPE Pro 2 (H122-373)', 'oui' => '00259E', 'product_class' => '5G CPE', 'release_year' => 2021, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 9600, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => true],
                    ['model_name' => 'MC801A 5G Indoor CPE', 'oui' => '00464B', 'product_class' => '5G CPE', 'release_year' => 2020, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 4800, 'ports_count' => 3, 'has_usb' => false, 'supports_tr069' => true, 'supports_tr369' => true],
                    ['model_name' => 'MC888s 5G WiFi 6 CPE', 'oui' => '286ED4', 'product_class' => '5G CPE', 'release_year' => 2021, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 4800, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => true],
                    ['model_name' => 'AX3 Wi-Fi 6', 'oui' => 'E0191D', 'product_class' => 'Home Router', 'release_year' => 2020, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 3000, 'ports_count' => 4, 'has_usb' => false, 'supports_tr069' => false, 'supports_tr369' => false],
                    ['model_name' => 'AX3 Pro', 'oui' => 'F4C714', 'product_class' => 'Home Router', 'release_year' => 2021, 'wifi_standard' => 'Wi-Fi 6 Plus', 'max_speed_mbps' => 3000, 'ports_count' => 4, 'has_usb' => false, 'supports_tr069' => false, 'supports_tr369' => false],
                ],
            ],
            [
                'name' => 'ZTE',
                'website' => 'https://www.zte.com.cn',
                'country' => 'China',
                'description' => 'Global telecommunications equipment and network solutions provider',
                'products' => [
                    ['model_name' => 'MC7010 5G Sub6 CPE', 'oui' => '3C7843', 'product_class' => '5G CPE', 'release_year' => 2020, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 4800, 'ports_count' => 4, 'has_usb' => false, 'supports_tr069' => true, 'supports_tr369' => true],
                    ['model_name' => 'ZLT X21 5G Indoor CPE', 'oui' => '54A050', 'product_class' => '5G CPE', 'release_year' => 2021, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 3600, 'ports_count' => 3, 'has_usb' => false, 'supports_tr069' => true, 'supports_tr369' => true],
                    ['model_name' => 'MC8020 5G WiFi6', 'oui' => '68DB54', 'product_class' => '5G CPE', 'release_year' => 2021, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 4800, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => true],
                ],
            ],
            [
                'name' => 'Zyxel',
                'website' => 'https://www.zyxel.com',
                'country' => 'Taiwan',
                'description' => 'Network access solutions for service providers and SMEs',
                'products' => [
                    ['model_name' => 'LTE3316-M604', 'oui' => '001349', 'product_class' => '4G LTE CPE', 'release_year' => 2020, 'wifi_standard' => 'Wi-Fi 5', 'max_speed_mbps' => 1200, 'ports_count' => 4, 'has_usb' => false, 'supports_tr069' => true, 'supports_tr369' => true],
                    ['model_name' => 'LTE7490-M904', 'oui' => '980CA4', 'product_class' => '5G NR CPE', 'release_year' => 2021, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 3600, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => true],
                    ['model_name' => 'VMG8825-T50', 'oui' => '44BF57', 'product_class' => 'VDSL2 Gateway', 'release_year' => 2020, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 4800, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => true],
                    ['model_name' => 'NR7101', 'oui' => 'B4ECE1', 'product_class' => '5G Outdoor Router', 'release_year' => 2021, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 1200, 'ports_count' => 2, 'has_usb' => false, 'supports_tr069' => true, 'supports_tr369' => true],
                ],
            ],
            [
                'name' => 'TP-Link',
                'website' => 'https://www.tp-link.com',
                'country' => 'China',
                'description' => 'Global provider of reliable networking devices and accessories',
                'products' => [
                    ['model_name' => 'Archer AX21', 'oui' => '50C7BF', 'product_class' => 'Home Router', 'release_year' => 2020, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 1775, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => false, 'supports_tr369' => false],
                    ['model_name' => 'Archer A6 V3 (AC1200)', 'oui' => '6C5AB0', 'product_class' => 'Home Router', 'release_year' => 2020, 'wifi_standard' => 'Wi-Fi 5', 'max_speed_mbps' => 1200, 'ports_count' => 4, 'has_usb' => false, 'supports_tr069' => false, 'supports_tr369' => false],
                    ['model_name' => 'Archer AX55', 'oui' => '98DED0', 'product_class' => 'Home Router', 'release_year' => 2021, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 2976, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => false, 'supports_tr369' => false],
                    ['model_name' => 'Archer AX73', 'oui' => 'D807B6', 'product_class' => 'Home Router', 'release_year' => 2021, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 5400, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => false, 'supports_tr369' => false],
                    ['model_name' => 'Archer MR600', 'oui' => 'EC086B', 'product_class' => '4G LTE Router', 'release_year' => 2020, 'wifi_standard' => 'Wi-Fi 5', 'max_speed_mbps' => 1200, 'ports_count' => 4, 'has_usb' => false, 'supports_tr069' => true, 'supports_tr369' => false],
                    ['model_name' => 'Archer NX200', 'oui' => '50C7BF', 'product_class' => '5G Router', 'release_year' => 2022, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 1800, 'ports_count' => 4, 'has_usb' => false, 'supports_tr069' => false, 'supports_tr369' => false],
                    ['model_name' => 'Deco X20', 'oui' => '6C5AB0', 'product_class' => 'Mesh System', 'release_year' => 2020, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 1800, 'ports_count' => 2, 'has_usb' => false, 'supports_tr069' => false, 'supports_tr369' => false],
                    ['model_name' => 'Archer BE9300', 'oui' => '50C7BF', 'product_class' => 'Home Router', 'release_year' => 2023, 'wifi_standard' => 'Wi-Fi 7', 'max_speed_mbps' => 9300, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => false, 'supports_tr369' => false],
                ],
            ],
            [
                'name' => 'MikroTik',
                'website' => 'https://mikrotik.com',
                'country' => 'Latvia',
                'description' => 'Manufacturer of routing and wireless networking equipment',
                'products' => [
                    ['model_name' => 'hAP ax² (C52iG-5HaxD2HaxD)', 'oui' => '48D38B', 'product_class' => 'Home Router', 'release_year' => 2021, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 1800, 'ports_count' => 5, 'has_usb' => false, 'supports_tr069' => true, 'supports_tr369' => false],
                    ['model_name' => 'hAP ax³ (C53UiG+5HPaxD2HPaxD)', 'oui' => '48D38B', 'product_class' => 'Home Router', 'release_year' => 2022, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 3000, 'ports_count' => 5, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => false],
                    ['model_name' => 'RB5009UG+S+IN', 'oui' => '2CC81B', 'product_class' => 'Enterprise Router', 'release_year' => 2020, 'wifi_standard' => 'N/A', 'max_speed_mbps' => 0, 'ports_count' => 8, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => false],
                    ['model_name' => 'RB5009UPr+S+OUT', 'oui' => '2CC81B', 'product_class' => 'Outdoor Router', 'release_year' => 2021, 'wifi_standard' => 'N/A', 'max_speed_mbps' => 0, 'ports_count' => 8, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => false],
                    ['model_name' => 'L009UiGS-RM', 'oui' => '48D38B', 'product_class' => 'Wired Router', 'release_year' => 2022, 'wifi_standard' => 'N/A', 'max_speed_mbps' => 0, 'ports_count' => 8, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => false],
                    ['model_name' => 'CCR2004-16G-2S+', 'oui' => '2CC81B', 'product_class' => 'Cloud Core Router', 'release_year' => 2020, 'wifi_standard' => 'N/A', 'max_speed_mbps' => 0, 'ports_count' => 16, 'has_usb' => false, 'supports_tr069' => true, 'supports_tr369' => false],
                ],
            ],
            [
                'name' => 'D-Link',
                'website' => 'https://www.dlink.com',
                'country' => 'Taiwan',
                'description' => 'Global leader in connectivity for home and business',
                'products' => [
                    ['model_name' => 'DWR-2101 5G NR Router', 'oui' => '00055D', 'product_class' => '5G Router', 'release_year' => 2020, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 3000, 'ports_count' => 4, 'has_usb' => false, 'supports_tr069' => true, 'supports_tr369' => false],
                    ['model_name' => 'DWR-978 5G WiFi AC2600', 'oui' => '28107B', 'product_class' => '5G Router', 'release_year' => 2020, 'wifi_standard' => 'Wi-Fi 5', 'max_speed_mbps' => 2600, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => false],
                    ['model_name' => 'DWR-953 4G LTE Cat7', 'oui' => 'B0C554', 'product_class' => '4G LTE Router', 'release_year' => 2020, 'wifi_standard' => 'Wi-Fi 5', 'max_speed_mbps' => 1200, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => false],
                ],
            ],
            [
                'name' => 'Netgear',
                'website' => 'https://www.netgear.com',
                'country' => 'United States',
                'description' => 'Networking products for consumers, businesses, and service providers',
                'products' => [
                    ['model_name' => 'Nighthawk M5 (MR5200)', 'oui' => '00146C', 'product_class' => '5G Mobile Router', 'release_year' => 2020, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 4800, 'ports_count' => 1, 'has_usb' => true, 'supports_tr069' => false, 'supports_tr369' => false],
                    ['model_name' => 'LAX20 4G LTE WiFi 6', 'oui' => '28C68E', 'product_class' => '4G LTE Router', 'release_year' => 2020, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 1800, 'ports_count' => 4, 'has_usb' => false, 'supports_tr069' => false, 'supports_tr369' => false],
                    ['model_name' => 'LM1200 4G LTE Modem', 'oui' => '9C3DCF', 'product_class' => '4G LTE Modem', 'release_year' => 2020, 'wifi_standard' => 'N/A', 'max_speed_mbps' => 0, 'ports_count' => 2, 'has_usb' => false, 'supports_tr069' => true, 'supports_tr369' => false],
                ],
            ],
            [
                'name' => 'Nokia',
                'website' => 'https://www.nokia.com',
                'country' => 'Finland',
                'description' => 'Global leader in network infrastructure and broadband equipment',
                'products' => [
                    ['model_name' => 'Fastmile 5G Gateway', 'oui' => '001AAF', 'product_class' => '5G CPE', 'release_year' => 2020, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 4800, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => true],
                    ['model_name' => 'G-240W-C WiFi 6 ONT', 'oui' => 'F8E71E', 'product_class' => 'ONT Gateway', 'release_year' => 2021, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 2400, 'ports_count' => 4, 'has_usb' => false, 'supports_tr069' => true, 'supports_tr369' => true],
                ],
            ],
            [
                'name' => 'Technicolor',
                'website' => 'https://www.technicolor.com',
                'country' => 'France',
                'description' => 'Provider of broadband gateways for service providers',
                'products' => [
                    ['model_name' => 'DGA4134 WiFi 6 Gateway', 'oui' => '440010', 'product_class' => 'Fiber Gateway', 'release_year' => 2021, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 4800, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => true],
                    ['model_name' => 'TG670 DSL Gateway', 'oui' => 'A0F3C1', 'product_class' => 'DSL Gateway', 'release_year' => 2020, 'wifi_standard' => 'Wi-Fi 5', 'max_speed_mbps' => 1200, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => false],
                ],
            ],
            [
                'name' => 'AVM',
                'website' => 'https://fritz.com',
                'country' => 'Germany',
                'description' => 'Leading European manufacturer of broadband equipment and smart home solutions',
                'products' => [
                    ['model_name' => 'FritzBox 5690 Pro', 'oui' => '3810D5', 'product_class' => 'Fiber Gateway', 'release_year' => 2024, 'wifi_standard' => 'Wi-Fi 7', 'max_speed_mbps' => 18490, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => false],
                    ['model_name' => 'FritzBox 5690 XGS', 'oui' => '3CA62F', 'product_class' => 'XGS-PON Gateway', 'release_year' => 2023, 'wifi_standard' => 'Wi-Fi 7', 'max_speed_mbps' => 18490, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => false],
                    ['model_name' => 'FritzBox 7690', 'oui' => 'C02506', 'product_class' => 'DSL Gateway', 'release_year' => 2024, 'wifi_standard' => 'Wi-Fi 7', 'max_speed_mbps' => 7200, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => false],
                    ['model_name' => 'FritzBox 7682', 'oui' => '7CFF4D', 'product_class' => 'DSL Gateway', 'release_year' => 2024, 'wifi_standard' => 'Wi-Fi 6/7', 'max_speed_mbps' => 7000, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => false],
                    ['model_name' => 'FritzBox 6690 Cable', 'oui' => '9CC7A6', 'product_class' => 'Cable Gateway', 'release_year' => 2023, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 4800, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => false],
                    ['model_name' => 'FritzBox 6670 Cable', 'oui' => '001A4F', 'product_class' => 'Cable Gateway', 'release_year' => 2024, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 4800, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => false],
                    ['model_name' => 'FritzBox 7590 AX', 'oui' => '00040E', 'product_class' => 'DSL Gateway', 'release_year' => 2021, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 3600, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => false],
                ],
            ],
            [
                'name' => 'Sercomm',
                'website' => 'https://www.sercomm.com',
                'country' => 'Taiwan',
                'description' => 'ODM manufacturer of broadband and IoT devices',
                'products' => [
                    ['model_name' => 'FG1000 Series Gateway', 'oui' => '001200', 'product_class' => 'Fiber Gateway', 'release_year' => 2020, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 4800, 'ports_count' => 4, 'has_usb' => true, 'supports_tr069' => true, 'supports_tr369' => true],
                    ['model_name' => 'DOCSIS 3.1 Cable Gateway', 'oui' => 'A42B8C', 'product_class' => 'Cable Gateway', 'release_year' => 2021, 'wifi_standard' => 'Wi-Fi 6', 'max_speed_mbps' => 3600, 'ports_count' => 4, 'has_usb' => false, 'supports_tr069' => true, 'supports_tr369' => false],
                ],
            ],
        ];

        foreach ($manufacturers as $mfrData) {
            $products = $mfrData['products'];
            unset($mfrData['products']);

            $manufacturer = RouterManufacturer::create($mfrData);

            foreach ($products as $productData) {
                $productData['manufacturer_id'] = $manufacturer->id;
                RouterProduct::create($productData);
            }
        }

        $this->command->info('✅ CPE Products Seeder completed: ' . RouterManufacturer::count() . ' manufacturers, ' . RouterProduct::count() . ' products');
    }
}
