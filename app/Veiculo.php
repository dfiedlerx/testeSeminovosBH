<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Veiculo extends Model
{

    protected $fillable = ['slug', 'placa', 'valor', 'cor_id', 'modelo_id', 'quilometragem'];

    public function cor () {

        return $this->belongsTo('App\Cor', 'cor_id');

    }

    public function modelo () {

        return $this->belongsTo('App\Modelo', 'modelo_id');

    }

    public function acessorios () {

        return $this->hasMany('App\VeiculoAcessorio', 'veiculo_id', 'id');

    }

}
