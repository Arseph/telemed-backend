<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class Login extends Model implements Auditable
{
	use \OwenIt\Auditing\Auditable;
    protected $table = 'logins';
    protected $guarded = array();

    public function user() {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
