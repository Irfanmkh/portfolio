<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    //
    protected $fillable = [
        'sekolah',
        'jurusan',
        'start_date',
        'end_date',
        'description',
        'jurusan',
        'tugas_akhir',
        'highlights',
        'tech_stack',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'highlights' => 'array',
        'tech_stack' => 'array',
    ];
}
