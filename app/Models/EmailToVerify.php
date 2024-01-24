<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailToVerify extends Model
{
    use HasFactory;
    protected $table = 'email_to_verify';

    protected $username = 'email';
    protected $fillable = ['email', 'code'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
