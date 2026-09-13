<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCredential extends Model
{
    protected $fillable = ['user_id', 'password_hash', 'must_change_password', 'password_changed_at'];
    protected $hidden = ['password_hash'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
