<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MessageStatus extends Model
{
    use HasFactory;

    protected $fillable = ['key','name','description'];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public static function idByKey(string $key): ?int
    {
        static $cache = [];
        $lk = strtolower($key);
        if (!array_key_exists($lk, $cache)) {
            $cache[$lk] = static::where('key',$lk)->value('id');
        }
        return $cache[$lk];
    }
}
