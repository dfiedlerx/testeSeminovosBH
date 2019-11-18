<?php namespace App\Http\Controllers\Read;


use App\Http\Controllers\Controller;
use App\Veiculo;
use Illuminate\Http\Request;
use Symfony\Component\Console\Input\Input;

class RetornaVeiculos extends Controller
{

    /**
     * @param Request $request
     * @return array
     */
    public function veiculoEspecifico(Request $request) {

        /**
         * Parâmetros esperados;
         * search (Nesse parametro poderá conter o numero id ou o slug do carro como diferencial)
         */
        $requestData = (request()->all());


        $resultado =
            Veiculo::with('cor')
                ->with('modelo.cambio')
                ->with('modelo.versao')
                ->with('acessorios.acessorio')
                ->where(['slug' => $requestData['search']])
                ->orWhere(['id' => $requestData['search']])
                ->get()
                ->first();


        $return =
            [
                'id' => $resultado['id'],
                'slug' => $resultado['slug'],
                'placa' => $resultado['placa'],
                'valor' => $resultado['valor'],
                'quilometragem' => $resultado['valor'],
                'cor' => $resultado['cor']['name'],
                'modelo' => $resultado['modelo']['name'],
                'versao' => $resultado['modelo']['versao']['name'],
                'ano_fabricacao' => $resultado['modelo']['ano_fabricacao'],
                'ano_modelo' => $resultado['modelo']['ano_modelo'],
                'numero_portas' => $resultado['modelo']['numero_portas'],
                'cambio' => $resultado['modelo']['cambio']['name'],
                'combustivel' => $resultado['modelo']['combustivel'],
                'acessorios' => []
            ];


        foreach ($resultado['acessorios'] as $currentAcessorio) {

            $return['acessorios'][] =  $currentAcessorio['acessorio']['name'];

        }

        return $return;

    }

    /**
     * Os filtros podem ser passados na url
     */
    public function filtraVeiculos (Request $request) {

        $requestData = (request()->all());

        $veiculo =
            Veiculo::with('cor')
                ->whereHas('cor', function ($veiculo) use ($requestData) {

                    if (!empty($requestData['cor'])) {

                        $veiculo->where('name', 'LIKE', $requestData['cor']);

                    }

                })
                ->with('modelo')
                ->whereHas('modelo', function ($veiculo) use ($requestData) {

                    if (!empty($requestData['modelo'])) {

                        $veiculo->where('name', 'LIKE', '%' . $requestData['modelo'] . '%');

                    }

                    if (!empty($requestData['anoFabricacaoMaiorQue'])) {

                       $veiculo->where('ano_fabricacao', '>=', $requestData['anoFabricacaoMaiorQue']);

                    }

                    if (!empty($requestData['anoFabricacaoMenorQue'])) {

                        $veiculo->where('ano_fabricacao', '<=', $requestData['anoFabricacaoMaiorQue']);

                    }

                    if (!empty($requestData['anoModeloMaiorQue'])) {

                        $veiculo->where('ano_modelo', '>=', $requestData['anoModeloMaiorQue']);

                    }

                    if (!empty($requestData['anoModeloMenorQue'])) {

                        $veiculo->where('ano_modelo', '<=', $requestData['anoModeloMenorQue']);

                    }

                    if (!empty($requestData['numeroPortas'])) {

                        $veiculo->where('numero_portas', '=', $requestData['numeroPortas']);

                    }

                    if (!empty($requestData['combustivel'])) {

                        $veiculo->where('combustivel', 'LIKE', '%' . $requestData['combustivel'] . '%');

                    }

                })
                ->with('modelo.cambio')
                ->whereHas('modelo.cambio', function ($veiculo) use ($requestData) {

                    if (!empty($requestData['cambio'])) {

                        $veiculo->where('name', 'LIKE', '%' . $requestData['cambio'] . '%');

                    }

                })
                ->with('modelo.versao')
                ->whereHas('modelo.versao', function ($veiculo) use ($requestData) {

                    if (!empty($requestData['versao'])) {

                        $veiculo->where('name', 'LIKE', '%' . $requestData['versao'] . '%');

                    }

                })
                ->with('acessorios.acessorio');

        if (!empty($requestData['placa'])) {

           $veiculo = $veiculo->where('placa', '=', $requestData['placa']);

        }

        if (!empty($requestData['valorMaiorQue'])) {

            $veiculo = $veiculo->where('valor', '>=', $requestData['valorMaiorQue']);

        }

        if (!empty($requestData['valorMenorQue'])) {

            $veiculo = $veiculo->where('valor', '<=', $requestData['valorMenorQue']);

        }

        if (!empty($requestData['quilometragemMaiorQue'])) {

            $veiculo = $veiculo->where('quilometragem', '>=', $requestData['quilometragemMaiorQue']);

        }

        if (!empty($requestData['quilometragemMenorQue'])) {

            $veiculo = $veiculo->where('quilometragem', '<=', $requestData['quilometragemMenorQue']);

        }

        $veiculo = $veiculo->get()->toArray();

        $toReturn = [];

        foreach ($veiculo as $resultado) {

            $toReturn[] =
                [
                    'id' => $resultado['id'],
                    'slug' => $resultado['slug'],
                    'modelo' => $resultado['modelo']['name'],
                    'versao' => $resultado['modelo']['versao']['name'],
                    'preco' => $resultado['valor'],
                    'cambio' => $resultado['modelo']['cambio']['name'],
                    'quilometragem' => $resultado['quilometragem'],
                    'cor' => $resultado['cor']['name'],
                    'numero_portas' => $resultado['modelo']['numero_portas'],
                ];

        }

        return $toReturn;

    }

}