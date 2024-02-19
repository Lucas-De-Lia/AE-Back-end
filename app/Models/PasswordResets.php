<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResets extends Model
{
    use HasFactory;
    protected $table = 'password_resets';

    protected $username = 'email';

    protected $fillable = [ 'email' , 'token'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
