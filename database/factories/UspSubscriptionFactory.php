<?php

namespace Database\Factories;

use App\Models\UspSubscription;
use App\Models\CpeDevice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UspSubscription>
 */
class UspSubscriptionFactory extends Factory
{
    protected $model = UspSubscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $notificationTypes = ['ValueChange', 'Event', 'ObjectCreation', 'ObjectDeletion', 'OperationComplete'];
        
        return [
            'cpe_device_id' => CpeDevice::factory(),
            'subscription_id' => 'sub-' . Str::random(10),
            'notification_type' => fake()->randomElement($notificationTypes),
            'reference_list' => [
                'Device.DeviceInfo.UpTime',
                'Device.WiFi.SSID.1.SSID'
            ],
            'enabled' => true,
            'persistent' => true,
            'status' => 'active',
            'expires_at' => null,
            'notification_count' => 0,
            'last_notification_at' => null,
        ];
    }

    /**
     * Indicate that the subscription is for value change notifications
     */
    public function valueChange(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => 'ValueChange',
        ]);
    }

    /**
     * Indicate that the subscription is for event notifications
     */
    public function event(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => 'Event',
        ]);
    }

    /**
     * Indicate that the subscription is disabled
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
        ]);
    }

    /**
     * Indicate that the subscription is not persistent
     */
    public function nonPersistent(): static
    {
        return $this->state(fn (array $attributes) => [
            'persistent' => false,
        ]);
    }

    /**
     * Indicate that the subscription is expired
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the subscription is cancelled
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
