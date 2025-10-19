<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SecureDeviceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('devices.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'serial_number' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-zA-Z0-9\-_]+$/',
                Rule::unique('cpe_devices')->ignore($this->device),
            ],
            'oui' => [
                'required',
                'string',
                'size:6',
                'regex:/^[0-9A-F]{6}$/',
            ],
            'product_class' => [
                'nullable',
                'string',
                'max:128',
                'regex:/^[a-zA-Z0-9\-_. ]+$/',
            ],
            'manufacturer' => [
                'nullable',
                'string',
                'max:64',
                'regex:/^[a-zA-Z0-9\-_. ]+$/',
            ],
            'model' => [
                'nullable',
                'string',
                'max:64',
                'regex:/^[a-zA-Z0-9\-_. ]+$/',
            ],
            'software_version' => [
                'nullable',
                'string',
                'max:64',
                'regex:/^[a-zA-Z0-9\-_.]+$/',
            ],
            'hardware_version' => [
                'nullable',
                'string',
                'max:64',
                'regex:/^[a-zA-Z0-9\-_.]+$/',
            ],
            'ip_address' => [
                'nullable',
                'ip',
            ],
            'mac_address' => [
                'nullable',
                'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
            ],
            'external_ip' => [
                'nullable',
                'ip',
            ],
            'connection_url' => [
                'nullable',
                'url',
                'max:255',
            ],
            'username' => [
                'nullable',
                'string',
                'max:64',
                'regex:/^[a-zA-Z0-9\-_@.]+$/',
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'max:128',
            ],
            'profile_id' => [
                'nullable',
                'exists:configuration_profiles,id',
            ],
            'data_model_id' => [
                'nullable',
                'exists:data_models,id',
            ],
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'serial_number.regex' => 'Serial number can only contain alphanumeric characters, hyphens, and underscores.',
            'oui.regex' => 'OUI must be exactly 6 hexadecimal characters (A-F, 0-9).',
            'mac_address.regex' => 'Invalid MAC address format. Use XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX.',
            'password.min' => 'Password must be at least 8 characters long.',
        ];
    }

    /**
     * Prepare input for validation - sanitization
     */
    protected function prepareForValidation(): void
    {
        $input = [];

        if ($this->has('serial_number')) {
            $input['serial_number'] = strip_tags(trim($this->serial_number));
        }

        if ($this->has('oui')) {
            $input['oui'] = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $this->oui));
        }

        if ($this->has('mac_address')) {
            $input['mac_address'] = strtoupper(str_replace('-', ':', $this->mac_address));
        }

        if ($this->has('manufacturer')) {
            $input['manufacturer'] = strip_tags(trim($this->manufacturer));
        }

        if ($this->has('model')) {
            $input['model'] = strip_tags(trim($this->model));
        }

        $this->merge($input);
    }
}
