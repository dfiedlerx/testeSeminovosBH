<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Versao extends Model
{

    protected $table = 'versoes';
    protected $fillable = ['name'];

}
