<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $fillable = [
        'status',
        'total_data',
        'result_path',
        'job_id',
        'design_id',
        'result_url',
        'edit_url',
        'view_url',
        'message',
    ];
}