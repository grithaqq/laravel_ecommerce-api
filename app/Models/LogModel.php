<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'log';
    protected $primaryKey = 'log_id';

    protected $fillable = [
        'log_id',
        'user_id',
        'log_method',
        'log_url',
        'log_ip',
        'log_request',
        'log_response'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
