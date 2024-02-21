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
        'title',
        'abstract'
    ];

    public function image(){
        return $this->hasOne(Image::class);
    }

    public function pdfFile(){
        return $this->hasOne(PdfFile::class);
    }
}
