<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigurationTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'WiFi Sicuro - FRITZ!Box',
                'vendor' => 'AVM',
                'model' => 'FRITZ!Box',
                'protocol_version' => 'TR-098',
                'description' => 'Configurazione WiFi sicura con WPA3, SSID personalizzato e canali ottimizzati',
                'data_model_id' => 1,
                'parameters' => json_encode([
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.Enable' => true,
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID' => 'MySecureWiFi',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.BeaconType' => 'WPAand11i',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.Standard' => 'ax',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.Channel' => 'Auto',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSIDAdvertisementEnabled' => true,
                ]),
                'validation_rules' => json_encode([
                    'SSID' => ['required', 'min:1', 'max:32'],
                    'BeaconType' => ['required', 'in:None,Basic,WPA,11i,WPAand11i'],
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'VoIP Standard - FRITZ!Box',
                'vendor' => 'AVM',
                'model' => 'FRITZ!Box',
                'protocol_version' => 'TR-104',
                'description' => 'Configurazione VoIP con capacità FaxT38, RTCP e supporto sessioni multiple',
                'data_model_id' => 2,
                'parameters' => json_encode([
                    'VoiceService.1.Capabilities.FaxT38' => true,
                    'VoiceService.1.Capabilities.RTCP' => true,
                    'VoiceService.1.Capabilities.MaxLineCount' => 5,
                    'VoiceService.1.Capabilities.MaxSessionCount' => 10,
                ]),
                'validation_rules' => json_encode([
                    'MaxLineCount' => ['required', 'integer', 'between:1,10'],
                    'MaxSessionCount' => ['required', 'integer', 'between:1,50'],
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Storage NAS - FRITZ!Box',
                'vendor' => 'AVM',
                'model' => 'FRITZ!Box',
                'protocol_version' => 'TR-140',
                'description' => 'Configurazione storage NAS con server FTP/HTTP e supporto protocolli rete',
                'data_model_id' => 3,
                'parameters' => json_encode([
                    'StorageService.1.Enable' => true,
                    'StorageService.1.FTPServer.Enable' => true,
                    'StorageService.1.FTPServer.MaxNumUsers' => 5,
                    'StorageService.1.Capabilities.FTPCapable' => true,
                    'StorageService.1.Capabilities.HTTPCapable' => true,
                ]),
                'validation_rules' => json_encode([
                    'MaxNumUsers' => ['required', 'integer', 'between:1,20'],
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'WiFi Guest Network - FRITZ!Box',
                'vendor' => 'AVM',
                'model' => 'FRITZ!Box',
                'protocol_version' => 'TR-098',
                'description' => 'Rete WiFi guest isolata con autenticazione WPA2 e limitazioni accesso',
                'data_model_id' => 1,
                'parameters' => json_encode([
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.2.Enable' => true,
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.2.SSID' => 'GuestWiFi',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.2.BeaconType' => '11i',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.2.Standard' => 'ac',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.2.SSIDAdvertisementEnabled' => true,
                ]),
                'validation_rules' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($templates as $template) {
            DB::table('configuration_templates')->insert($template);
        }
        
        $this->command->info('✅ Created ' . count($templates) . ' configuration templates');
    }
}
