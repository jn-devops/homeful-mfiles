<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadAndConvert extends Model
{
    protected $fillable = [
        'request_from_ip',
        'request_from_website',
        'user_id',
        'user_name',
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
