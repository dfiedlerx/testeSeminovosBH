<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VeiculoAcessorio extends Model
{

    protected $table = 'veiculo_acessorios';
    protected $fillable = ['veiculo_id', 'acessorio_id'];

    public function veiculo () {

        return $this->belongsTo('App\Acessorio', 'veiculo_id');

    }

    public function acessorio () {

        return $this->belongsTo('App\Acessorio', 'acessorio_id');

    }

}
