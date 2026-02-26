<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topping extends Model
{
    use HasFactory;

    protected $fillable = [
        'stall_id',
        'name',
        'price',
        'description',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean',
        'price' => 'decimal:2'
    ];

    // Relación con FoodStall
    public function stall()
    {
        return $this->belongsTo(FoodStall::class, 'stall_id');
    }

    // Relación many-to-many con MenuItem
    public function menuItems()
    {
        return $this->belongsToMany(
            MenuItem::class,
            'menu_item_topping',
            'topping_id',
            'menu_item_id'
        )->withPivot('required')
         ->withTimestamps();
    }
}
