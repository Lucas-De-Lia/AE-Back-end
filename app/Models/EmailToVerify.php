<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailToVerify extends Model
{
    use HasFactory;
    protected $username = 'email';
    protected $fillable = ['email', 'code'];
}
