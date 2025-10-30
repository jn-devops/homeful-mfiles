<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UploadAndConvert extends Model
{
    use HasUuids;

    protected $fillable = [
        'request_from_ip',
        'request_from_website',
        'user_id',
        'user_name',
        'object_id',
        'data',
        'file_name',
        'link',
        'file_size',
        'expires_at',
    ];

    protected $casts = [
        'data' => 'array',
        'expires_at' => 'datetime',
    ];
}
