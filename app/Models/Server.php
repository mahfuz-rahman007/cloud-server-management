<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    /** @use HasFactory<\Database\Factories\ServerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'ip_address',
        'provider',
        'status',
        'cpu_cores',
        'ram_mb',
        'storage_gb',
    ];

    protected function casts(): array
    {
        return [
            'cpu_cores' => 'integer',
            'ram_mb' => 'integer',
            'storage_gb' => 'integer',
        ];
    }
}
