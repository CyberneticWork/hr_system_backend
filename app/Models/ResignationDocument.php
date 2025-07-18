<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResignationDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'resignation_id',
        'document_name',
        'file_path',
        'file_type',
        'file_size'
    ];

    public function resignation()
    {
        return $this->belongsTo(Resignation::class);
    }
}