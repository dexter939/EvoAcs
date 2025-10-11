#!/bin/bash

# Test script for USP API endpoints
# Requires: curl, jq

BASE_URL="http://localhost:5000/api/v1"
API_KEY="acs-secret-key-change-in-production"
DEVICE_ID=1  # Adjust this to match your test device ID

echo "🧪 Testing USP RESTful API Endpoints"
echo "======================================"
echo ""

# Test 1: Get Parameters
echo "📡 Test 1: GET Parameters"
echo "------------------------"
curl -X POST "${BASE_URL}/usp/devices/${DEVICE_ID}/get-params" \
  -H "X-API-Key: ${API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "paths": [
      "Device.DeviceInfo.ModelName",
      "Device.DeviceInfo.SoftwareVersion"
    ]
  }' | jq '.'
echo ""
echo ""

# Test 2: Set Parameters
echo "📝 Test 2: SET Parameters"
echo "------------------------"
curl -X POST "${BASE_URL}/usp/devices/${DEVICE_ID}/set-params" \
  -H "X-API-Key: ${API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "parameters": {
      "Device.ManagementServer.PeriodicInformInterval": "300"
    },
    "allow_partial": true
  }' | jq '.'
echo ""
echo ""

# Test 3: Operate Command
echo "⚙️  Test 3: OPERATE Command"
echo "-------------------------"
curl -X POST "${BASE_URL}/usp/devices/${DEVICE_ID}/operate" \
  -H "X-API-Key: ${API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "command": "Device.SelfTest()",
    "params": {}
  }' | jq '.'
echo ""
echo ""

# Test 4: Add Object
echo "➕ Test 4: ADD Object"
echo "--------------------"
curl -X POST "${BASE_URL}/usp/devices/${DEVICE_ID}/add-object" \
  -H "X-API-Key: ${API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "object_path": "Device.WiFi.AccessPoint.",
    "params": {
      "Enable": "true",
      "SSIDReference": "Device.WiFi.SSID.1"
    }
  }' | jq '.'
echo ""
echo ""

# Test 5: Delete Object
echo "🗑️  Test 5: DELETE Object"
echo "-----------------------"
curl -X POST "${BASE_URL}/usp/devices/${DEVICE_ID}/delete-object" \
  -H "X-API-Key: ${API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "object_paths": [
      "Device.WiFi.AccessPoint.5."
    ]
  }' | jq '.'
echo ""
echo ""

# Test 6: Reboot Device
echo "🔄 Test 6: REBOOT Device"
echo "-----------------------"
curl -X POST "${BASE_URL}/usp/devices/${DEVICE_ID}/reboot" \
  -H "X-API-Key: ${API_KEY}" \
  -H "Content-Type: application/json" | jq '.'
echo ""
echo ""

# Test 7: Invalid Device (TR-069)
echo "❌ Test 7: Invalid Device Protocol"
echo "----------------------------------"
# Assuming device ID 2 is TR-069
curl -X POST "${BASE_URL}/usp/devices/2/get-params" \
  -H "X-API-Key: ${API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "paths": ["Device.DeviceInfo.ModelName"]
  }' | jq '.'
echo ""
echo ""

# Test 8: Invalid API Key
echo "🔒 Test 8: Invalid API Key"
echo "-------------------------"
curl -X POST "${BASE_URL}/usp/devices/${DEVICE_ID}/get-params" \
  -H "X-API-Key: invalid-key" \
  -H "Content-Type: application/json" \
  -d '{
    "paths": ["Device.DeviceInfo.ModelName"]
  }' | jq '.'
echo ""
echo ""

echo "✅ USP API Tests Completed!"
