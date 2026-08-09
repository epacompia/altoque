<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'pregunta',
        'respuesta',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function categoria()
    {
        return $this->belongsTo(HelpCategory::class, 'category_id');
    }
}