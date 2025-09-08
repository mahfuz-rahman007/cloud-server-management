<?php

namespace App\Http\Requests;

use App\Models\Server;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $serverId = $this->getServerId();

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
            'updated_at' => $this->isUpdate() ? ['required', 'date'] : ['sometimes', 'date'],
        ];
    }

    /**
     * Check for name uniqueness per provider
     * Check for version conflicts
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateNameUniquenessPerProvider($validator);
            $this->validateVersionConflicts($validator);
        });
    }

    public function messages(): array
    {
        return [
            'ip_address.ip' => 'Please enter a valid IPv4 address (e.g., 192.168.1.100 or 10.0.0.1).',
            'ip_address.unique' => 'This IP address is already assigned to another server.',
            'cpu_cores.between' => 'CPU cores must be between 1 and 128.',
            'ram_mb.between' => 'RAM must be between 512MB and 1,048,576MB (1TB).',
            'storage_gb.between' => 'Storage must be between 10GB and 1,048,576GB (1TB).',
            'name.required' => 'Server name is required.',
            'provider.required' => 'Please select a cloud provider.',
            'provider.in' => 'Please select a valid provider (AWS, DigitalOcean, Vultr, or Other).',
            'status.required' => 'Please select a server status.',
            'status.in' => 'Please select a valid status (Active, Inactive, or Maintenance).',
        ];
    }

    private function getServerId(): ?int
    {
        return $this->route('server')?->id;
    }

    private function isUpdate(): bool
    {
        return $this->getServerId() !== null;
    }

    private function validateNameUniquenessPerProvider($validator): void
    {
        $serverId = $this->getServerId();
        $name = $this->input('name');
        $provider = $this->input('provider');

        $query = DB::table('servers')
            ->select('id')
            ->where('name', $name)
            ->where('provider', $provider);

        if ($serverId) {
            $query->where('id', '!=', $serverId);
        }

        if ($query->exists()) {
            $validator->errors()->add('name', 'The name has already been taken for this provider.');
        }
    }

    /**
     * Check for version conflicts when updating a server by more user
     */
    private function validateVersionConflicts($validator): void
    {
        if (! $this->isUpdate() || ! $this->has('updated_at')) {
            return;
        }

        $serverId = $this->getServerId();
        $submittedTime = $this->input('updated_at');

        $currentUpdatedAt = DB::table('servers')
            ->where('id', $serverId)
            ->value('updated_at');

        if (! $currentUpdatedAt) {
            return; // Server doesn't exist
        }

        $submittedTime = Carbon::parse($submittedTime);
        $currentTime = Carbon::parse($currentUpdatedAt);

        if (! $submittedTime->equalTo($currentTime)) {
            $validator->errors()->add(
                'updated_at',
                'This server was modified by another user when you submitted your changes. Please try again.'
            );
        }
    }
}
