<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    use HasFactory;

    protected $casts = [
        'attributes' => 'json',
    ];
    
    protected $fillable = [
        'user_id',
        'tx_hash',
        'link',
        'metadata',
        'status',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
