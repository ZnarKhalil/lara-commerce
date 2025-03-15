<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active'
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
} 