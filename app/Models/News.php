<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Image;
use App\Models\PdfFile;

class News extends Model
{
    use HasFactory;
    protected $table= "news";

     protected $fillable = [
        'titulo_principal',
        'titulo_secundario',
        'texto_principal',
        'texto_secundario',
        'tiempo_lectura',
    ];

    public function images(){
        return $this->hasMany(Image::class);
    }

    public function pdfFile(){
        return $this->hasOne(PdfFile::class);
    }
}