<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitaPagina extends Model
{
    protected $table = 'visita_paginas';

    protected $fillable = [
        'ruta',
        'contador',
    ];
}
