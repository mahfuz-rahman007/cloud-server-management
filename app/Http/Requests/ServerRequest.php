<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $serverId = $this->route('server')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => [
                'required',
                'ip:ipv4',
                Rule::unique('servers', 'ip_address')->ignore($serverId),
            ],
            'provider' => ['required', Rule::in(['aws', 'digitalocean', 'vultr', 'other'])],
            'status' => ['required', Rule::in(['active', 'inactive', 'maintenance'])],
            'cpu_cores' => ['required', 'integer', 'between:1,128'],
            'ram_mb' => ['required', 'integer', 'between:512,1048576'],
            'storage_gb' => ['required', 'integer', 'between:10,1048576'],
        ];
    }

    /**
     * Get the validation rules for name uniqueness per provider.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $serverId = $this->route('server')?->id;

            $exists = \App\Models\Server::where('name', $this->input('name'))
                ->where('provider', $this->input('provider'))
                ->when($serverId, fn ($query) => $query->where('id', '!=', $serverId))
                ->exists();

            if ($exists) {
                $validator->errors()->add('name', 'The name has already been taken for this provider.');
            }
        });
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'ip_address.ip' => 'The IP address must be a valid IPv4 address.',
            'cpu_cores.between' => 'CPU cores must be between 1 and 128.',
            'ram_mb.between' => 'RAM must be between 512MB and 1,048,576MB.',
            'storage_gb.between' => 'Storage must be between 10GB and 1,048,576GB.',
        ];
    }
}
