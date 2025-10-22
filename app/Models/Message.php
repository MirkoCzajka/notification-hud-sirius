<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\MessageStatus;

class Message extends Model
{
    use HasFactory;

    protected $table = 'messages';

    protected $fillable = [
        'message_status_id',
        'content',
        'user_id',
        'service_id',
        'date_sent',
        'provider_response',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'date_sent' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function statusRef()
    {
        return $this->belongsTo(MessageStatus::class, 'message_status_id');
    }
}
