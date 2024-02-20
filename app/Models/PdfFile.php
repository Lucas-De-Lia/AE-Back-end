<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\News;

class PdfFile extends Model
{
    use HasFactory;
    protected $table= "pdf_files";

    protected $fillable = [
        'title',
        'file_path',
    ];
    public function news()
    {
        return $this->belongsTo(News::class);
    }

}
