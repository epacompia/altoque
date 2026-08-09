<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'icono',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function articulos()
    {
        return $this->hasMany(HelpArticle::class, 'category_id')
            ->where('activo', true)
            ->orderBy('orden');
    }
}