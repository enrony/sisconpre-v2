<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledTaskLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'scheduled_tasks_log';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'task_name',
        'started_at',
        'finished_at',
        'successful',
        'error_message',
        'records_processed',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'successful' => 'boolean',
        'status' => 'boolean',
    ];
}
