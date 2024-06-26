<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignCategory extends Model
{
    use HasFactory;
    
    protected $fillable = ['category_name'];

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->where('category_name', 'like', "%{$searchTerm}%");
    }
}
