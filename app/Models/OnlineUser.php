<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlineUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'last_seen_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
