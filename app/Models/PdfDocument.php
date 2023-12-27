<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdfDocument extends Model
{
    use HasFactory;
    /**
     * The attributes that are mydatabasemass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['title', 'abstract', 'img', 'content'];
}
