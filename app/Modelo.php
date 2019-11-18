<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Modelo extends Model
{

    protected $fillable = ['name', 'combustivel', 'numero_portas', 'ano_fabricacao', 'ano_modelo', 'cambio_id', 'versao_id'];

    public function versao () {

        return $this->belongsTo('App\Versao', 'versao_id');

    }

    public function cambio () {

        return $this->belongsTo('App\Cambio', 'cambio_id');

    }

}
