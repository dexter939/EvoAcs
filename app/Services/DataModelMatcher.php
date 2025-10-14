<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DataModelMatcher - Servizio per auto-mapping intelligente dei Data Model TR-069
 * 
 * Quando un dispositivo si connette all'ACS per la prima volta,
 * questo servizio identifica automaticamente il Data Model corretto
 * basandosi su Manufacturer, Model, Firmware Version.
 * 
 * Logica di matching:
 * 1. Match esatto: vendor + model + firmware
 * 2. Match parziale: vendor + model (qualsiasi firmware)
 * 3. Match vendor: solo vendor
 * 4. Fallback BBF: TR-181 Device:2.19 (moderno) o TR-098 (legacy)
 */
class DataModelMatcher
{
    /**
     * Trova il Data Model più appropriato per un dispositivo CPE
     * 
     * @param string $manufacturer Nome del produttore (es. "MikroTik", "AVM", "Grandstream")
     * @param string|null $modelName Nome/codice del modello (es. "RouterOS", "FRITZ!Box")
     * @param string|null $productClass Product Class TR-069 (es. "hAP ac2", "7590")
     * @param string|null $softwareVersion Versione firmware (es. "7.1.5", "7.25")
     * @param string|null $oui Organization Unique Identifier (es. "00D0C9" per MikroTik)
     * @return int|null ID del data model trovato, null se nessun match
     */
    public function findBestMatch(
        string $manufacturer, 
        ?string $modelName = null, 
        ?string $productClass = null,
        ?string $softwareVersion = null,
        ?string $oui = null
    ): ?int {
        
        Log::info('DataModelMatcher: Starting auto-mapping', [
            'manufacturer' => $manufacturer,
            'model_name' => $modelName,
            'product_class' => $productClass,
            'software_version' => $softwareVersion,
            'oui' => $oui
        ]);

        // Step 1: Match esatto per vendor-specific models (vendor + model + firmware)
        $exactMatch = $this->findExactMatch($manufacturer, $modelName, $productClass, $softwareVersion);
        if ($exactMatch) {
            Log::info('DataModelMatcher: Exact match found', ['data_model_id' => $exactMatch]);
            return $exactMatch;
        }

        // Step 2: Match parziale (vendor + model, ignora firmware version)
        $partialMatch = $this->findPartialMatch($manufacturer, $modelName, $productClass);
        if ($partialMatch) {
            Log::info('DataModelMatcher: Partial match found', ['data_model_id' => $partialMatch]);
            return $partialMatch;
        }

        // Step 3: Match solo vendor (qualsiasi modello del vendor)
        $vendorMatch = $this->findVendorMatch($manufacturer);
        if ($vendorMatch) {
            Log::info('DataModelMatcher: Vendor match found', ['data_model_id' => $vendorMatch]);
            return $vendorMatch;
        }

        // Step 4: Match per OUI (Organization Unique Identifier)
        if ($oui) {
            $ouiMatch = $this->findOuiMatch($oui);
            if ($ouiMatch) {
                Log::info('DataModelMatcher: OUI match found', ['data_model_id' => $ouiMatch, 'oui' => $oui]);
                return $ouiMatch;
            }
        }

        // Step 5: Fallback a Broadband Forum Universal Data Models
        $fallback = $this->findUniversalFallback($manufacturer, $modelName);
        if ($fallback) {
            Log::info('DataModelMatcher: Universal BBF fallback', ['data_model_id' => $fallback]);
            return $fallback;
        }

        Log::warning('DataModelMatcher: No match found for device', [
            'manufacturer' => $manufacturer,
            'model' => $modelName ?? $productClass
        ]);

        return null;
    }

    /**
     * Match esatto: vendor + (model o product class) + firmware version
     */
    private function findExactMatch(
        string $manufacturer, 
        ?string $modelName, 
        ?string $productClass,
        ?string $softwareVersion
    ): ?int {
        if (!$softwareVersion) {
            return null;
        }

        $query = DB::table('tr069_data_models')
            ->where('vendor', 'ILIKE', '%' . $manufacturer . '%')
            ->where('is_active', true);

        if ($modelName) {
            $query->where('model_name', 'ILIKE', '%' . $modelName . '%');
        } elseif ($productClass) {
            $query->where('model_name', 'ILIKE', '%' . $productClass . '%');
        }

        if ($softwareVersion) {
            $query->where('firmware_version', 'ILIKE', '%' . $softwareVersion . '%');
        }

        $result = $query->orderByRaw("
            CASE 
                WHEN vendor ILIKE ? THEN 1
                ELSE 2
            END
        ", [$manufacturer])
        ->first();

        return $result?->id;
    }

    /**
     * Match parziale: vendor + (model o product class), ignora firmware
     */
    private function findPartialMatch(
        string $manufacturer, 
        ?string $modelName, 
        ?string $productClass
    ): ?int {
        $query = DB::table('tr069_data_models')
            ->where('vendor', 'ILIKE', '%' . $manufacturer . '%')
            ->where('is_active', true);

        if ($modelName) {
            $query->where('model_name', 'ILIKE', '%' . $modelName . '%');
        } elseif ($productClass) {
            $query->where('model_name', 'ILIKE', '%' . $productClass . '%');
        } else {
            return null;
        }

        $result = $query->orderBy('created_at', 'desc')->first();

        return $result?->id;
    }

    /**
     * Match vendor: solo produttore (qualsiasi modello)
     */
    private function findVendorMatch(string $manufacturer): ?int
    {
        $result = DB::table('tr069_data_models')
            ->where('vendor', 'ILIKE', '%' . $manufacturer . '%')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->first();

        return $result?->id;
    }

    /**
     * Match per OUI (Organization Unique Identifier)
     * Es: 00D0C9 = MikroTik, 00040E = AVM
     */
    private function findOuiMatch(string $oui): ?int
    {
        $ouiVendorMap = [
            '00D0C9' => 'MikroTik',
            '00040E' => 'AVM',
            '002618' => 'Grandstream',
            'C46E1F' => 'TP-Link',
        ];

        $vendor = $ouiVendorMap[strtoupper($oui)] ?? null;
        
        if (!$vendor) {
            return null;
        }

        return $this->findVendorMatch($vendor);
    }

    /**
     * Fallback universale a Broadband Forum standard
     * - Device:2.19 (TR-181) per dispositivi moderni
     * - InternetGatewayDevice:1.8 (TR-098) per dispositivi legacy
     */
    private function findUniversalFallback(?string $manufacturer, ?string $modelName): ?int
    {
        // Preferisci TR-181 Device:2.19 (moderno, copertura 70-80% dei device)
        $tr181 = DB::table('tr069_data_models')
            ->where('vendor', 'Broadband Forum')
            ->where('model_name', 'Device:2.19')
            ->where('is_active', true)
            ->first();

        if ($tr181) {
            return $tr181->id;
        }

        // Fallback a TR-098 InternetGatewayDevice:1.8 (legacy)
        $tr098 = DB::table('tr069_data_models')
            ->where('vendor', 'Broadband Forum')
            ->where('model_name', 'ILIKE', '%InternetGatewayDevice%')
            ->where('is_active', true)
            ->first();

        return $tr098?->id;
    }

    /**
     * Associa un Data Model a un dispositivo CPE
     * 
     * @param int $deviceId ID del dispositivo CPE
     * @param int $dataModelId ID del data model da associare
     * @return bool True se l'associazione è riuscita
     */
    public function assignDataModelToDevice(int $deviceId, int $dataModelId): bool
    {
        try {
            DB::table('cpe_devices')
                ->where('id', $deviceId)
                ->update([
                    'data_model_id' => $dataModelId,
                    'updated_at' => now()
                ]);

            Log::info('DataModelMatcher: Data model assigned to device', [
                'device_id' => $deviceId,
                'data_model_id' => $dataModelId
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('DataModelMatcher: Failed to assign data model', [
                'device_id' => $deviceId,
                'data_model_id' => $dataModelId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Auto-mapping completo: trova e assegna il data model migliore
     * 
     * @param int $deviceId ID del dispositivo CPE
     * @param string $manufacturer Nome del produttore
     * @param string|null $modelName Nome del modello
     * @param string|null $productClass Product Class TR-069
     * @param string|null $softwareVersion Versione firmware
     * @param string|null $oui Organization Unique Identifier
     * @return int|null ID del data model assegnato, null se fallito
     */
    public function autoMapDevice(
        int $deviceId,
        string $manufacturer,
        ?string $modelName = null,
        ?string $productClass = null,
        ?string $softwareVersion = null,
        ?string $oui = null
    ): ?int {
        
        $dataModelId = $this->findBestMatch(
            $manufacturer, 
            $modelName, 
            $productClass, 
            $softwareVersion,
            $oui
        );

        if ($dataModelId && $this->assignDataModelToDevice($deviceId, $dataModelId)) {
            return $dataModelId;
        }

        return null;
    }
}
