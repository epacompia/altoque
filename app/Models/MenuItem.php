<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stall_id',
        'name',
        'description',
        'price',
        'category_id',
        'active',
        'featured',
        'image_path'
    ];

    public function stall()
    {
        return $this->belongsTo(FoodStall::class, 'stall_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relación many-to-many con Topping
    public function toppings()
    {
        return $this->belongsToMany(
            Topping::class,
            'menu_item_topping',
            'menu_item_id',
            'topping_id'
        )->withPivot('required')
         ->withTimestamps();
    }
}