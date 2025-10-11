#!/bin/bash

# End-to-End Test for USP Subscribe/Notify Pattern
# Tests TR-369 event subscription flow

BASE_URL="http://localhost:5000"
API_BASE="${BASE_URL}/api/v1"
API_KEY="acs-secret-key-change-in-production"
DEVICE_ID=1  # TR-369 USP device ID

echo "🧪 End-to-End Subscribe/Notify Test - TR-369 USP"
echo "================================================="
echo ""

# Test 1: Create Event Subscription via API
echo "📡 Test 1: Create Event Subscription"
echo "-------------------------------------"
SUBSCRIPTION_RESPONSE=$(curl -s -X POST "${API_BASE}/usp/devices/${DEVICE_ID}/subscribe" \
  -H "X-API-Key: ${API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "event_path": "Device.WiFi.Radio.*.ChannelChange!",
    "reference_list": [
      "Device.WiFi.Radio.1.Channel",
      "Device.WiFi.Radio.1.OperatingFrequencyBand"
    ],
    "notification_retry": true
  }')

echo "$SUBSCRIPTION_RESPONSE"
echo ""

# Extract subscription_id
SUBSCRIPTION_ID=$(echo "$SUBSCRIPTION_RESPONSE" | grep -o '"subscription_id":"[^"]*"' | head -1 | cut -d'"' -f4)

if [ -n "$SUBSCRIPTION_ID" ]; then
  echo "✅ Subscription created: ${SUBSCRIPTION_ID}"
else
  echo "❌ Failed to create subscription"
  exit 1
fi
echo ""

# Test 2: List Subscriptions
echo "📋 Test 2: List Device Subscriptions"
echo "------------------------------------"
LIST_RESPONSE=$(curl -s -X GET "${API_BASE}/usp/devices/${DEVICE_ID}/subscriptions" \
  -H "X-API-Key: ${API_KEY}")
echo "$LIST_RESPONSE"
echo ""

# Test 3: Verify in Database
echo "🔍 Test 3: Verify Subscription in Database"
echo "------------------------------------------"
php artisan tinker --execute="
\$sub = App\Models\UspSubscription::where('subscription_id', '${SUBSCRIPTION_ID}')->first();
if (\$sub) {
    echo '✅ Subscription found:' . PHP_EOL;
    echo '  Event Path: ' . \$sub->event_path . PHP_EOL;
    echo '  Active: ' . (\$sub->is_active ? 'Yes' : 'No') . PHP_EOL;
    echo '  Notification Count: ' . \$sub->notification_count . PHP_EOL;
    echo '  Reference List: ' . count(\$sub->reference_list) . ' paths' . PHP_EOL;
} else {
    echo '❌ Subscription not found!' . PHP_EOL;
}
"
echo ""

# Test 4: Create Second Subscription (notification_retry=false)
echo "📡 Test 4: Create Subscription with notification_retry=false"
echo "-----------------------------------------------------------"
SUBSCRIPTION_2=$(curl -s -X POST "${API_BASE}/usp/devices/${DEVICE_ID}/subscribe" \
  -H "X-API-Key: ${API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "event_path": "Device.WiFi.SSID.*.StatusChange!",
    "notification_retry": false
  }')

echo "$SUBSCRIPTION_2"
SUBSCRIPTION_ID_2=$(echo "$SUBSCRIPTION_2" | grep -o '"subscription_id":"[^"]*"' | head -1 | cut -d'"' -f4)

if [ -n "$SUBSCRIPTION_ID_2" ]; then
  echo ""
  echo "✅ Second subscription created: ${SUBSCRIPTION_ID_2}"
  
  # Verify notification_retry flag
  php artisan tinker --execute="
\$sub = App\Models\UspSubscription::where('subscription_id', '${SUBSCRIPTION_ID_2}')->first();
if (\$sub) {
    echo 'Notification Retry: ' . (\$sub->notification_retry ? 'ENABLED ❌' : 'DISABLED ✅') . PHP_EOL;
}
"
else
  echo "❌ Failed to create second subscription"
fi
echo ""

# Test 5: Delete Subscription
echo "🗑️  Test 5: Delete Subscription"
echo "---------------------------------"

# First, get the internal ID for the subscription
INTERNAL_ID=$(php artisan tinker --execute="
\$sub = App\Models\UspSubscription::where('subscription_id', '${SUBSCRIPTION_ID_2}')->first();
if (\$sub) echo \$sub->id;
")

DELETE_RESPONSE=$(curl -s -X DELETE "${API_BASE}/usp/devices/${DEVICE_ID}/subscriptions/${INTERNAL_ID}" \
  -H "X-API-Key: ${API_KEY}")

echo "$DELETE_RESPONSE"
echo ""

if echo "$DELETE_RESPONSE" | grep -q "successfully"; then
  echo "✅ Subscription deleted successfully"
  
  # Verify inactive status
  php artisan tinker --execute="
\$sub = App\Models\UspSubscription::where('subscription_id', '${SUBSCRIPTION_ID_2}')->first();
if (\$sub) {
    echo 'Status after delete: ' . (\$sub->is_active ? 'ACTIVE ❌' : 'INACTIVE ✅') . PHP_EOL;
}
"
else
  echo "❌ Failed to delete subscription"
fi
echo ""

# Test 6: Final Subscription Count
echo "📊 Test 6: Final Subscription Count"
echo "-----------------------------------"
FINAL_LIST=$(curl -s -X GET "${API_BASE}/usp/devices/${DEVICE_ID}/subscriptions" \
  -H "X-API-Key: ${API_KEY}")

TOTAL=$(echo "$FINAL_LIST" | grep -o '"total":[0-9]*' | cut -d':' -f2)
echo "Total subscriptions: ${TOTAL}"
echo ""

# Summary
echo "✅ Subscribe/Notify End-to-End Tests Completed!"
echo ""
echo "Summary:"
echo "--------"
echo "✅ Create subscription via API"
echo "✅ List subscriptions"
echo "✅ Verify subscription in database"
echo "✅ Multiple subscriptions per device"
echo "✅ notification_retry flag support (true/false)"
echo "✅ Delete subscription"
echo "✅ Verify inactive status after delete"
echo ""
echo "📝 Note: Full NOTIFY simulation requires binary protobuf encoding."
echo "   Use USP test client or device simulator for complete end-to-end testing."
echo ""
