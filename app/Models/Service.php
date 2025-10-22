<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';

    protected $fillable = [
        'name',
        'endpoint',
    ];

    public function messages()
    {
        return $this->hasMany(Message::class, 'service_id');
    }
}
