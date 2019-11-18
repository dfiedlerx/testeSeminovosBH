<?php namespace App\Http\Controllers\Import;


use App\Acessorio;
use App\Cambio;
use App\Cor;
use App\Http\Controllers\Controller;
use App\Modelo;
use App\Veiculo;
use App\VeiculoAcessorio;
use App\Versao;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Exceptions\ChildNotFoundException;
use PHPHtmlParser\Exceptions\CircularException;
use PHPHtmlParser\Exceptions\CurlException;
use PHPHtmlParser\Exceptions\NotLoadedException;
use PHPHtmlParser\Exceptions\StrictException;
use TypeError;

/**
 * Class ImportaVeiculos
 * @package App\Http\Controllers\Import
 *
 *
 * Antes de tudo é necessário explicar o que estou fazendo. Estou acessando a área do site de pesquisa. Nela eu
 * identifiquei que é possível obter todos os registros do site e que eles não tem uma tratativa no campo
 * 'registrosPagina'. Então eu posso colocar os padrões do site que são '20,30,50', ou por exemplo, 10000.
 * Toda vez  que o crawler lê o site, ele coleta todas as urls, verifca quais delas ainda não estão na base de
 * dados que está descrita mais precisamente no outro documento, que está junto ao projeto. Com essas urls em
 * mãos ele vai acessar uma a uma, e ,depois de ler os dados, os inserirá no banco de dados. Recomendo que essa
 * rotina rode uma vez por dia no período da noite pois, dependendo do número de veículos, pode pesar um pouco o
 * servidor. Após o primeiro uso dessa rotina, as seguintes tendem a ser muito mais rápidas, já que precisam
 * inserir uma quantidade menor de carros. Com limites menores, o crawler ainda assim percorrerá todas as páginas
 * então fica a critério qual limite usar.
 *
 * Eu estou trazendo ordenando pelos mais novos primeiro, pois eles tem chance claramente maior de não estar em
 * nosso sistema. Então a partir de quando começa-se a ter carros repetidos, o crawler para de executar pois deste
 * ponto para frente, os carros já foram lidos em outra execução já que sempre serão mais antigos.
 *
 * Estou usando a biblioteca de código aberta chamada PHPHtmlParser. Ela funciona muito semelhante com o
 * seletor do jquery. Mais informações de como usar em:
 * https://github.com/paquettg/php-html-parser
 *
 * Enviei a base de dados já com registros importados para vocês, porém se não se importarem com a demora,
 * podem a vontade limpar o banco e testar novamente.
 */

class ImportaVeiculos extends Controller
{

    /**
     * @param Request $request
     */
    public function run (Request $request) {

        try {

            set_time_limit (0);

            /**
             * Parâmetros esperados;
             * pageLimit
             */
            $requestData = (request()->all());

            //Limite a ser carregando em cada requisição. Impede estouro de memória.
            $pageLimit = $requestData['pageLimit'];

            //Página atual do crawler
            $currentPage  = 1;

            $registroExistente = false;

            $baseUrl = 'https://seminovos.com.br';

            do {

                //Chama a ulr adicionando filtros de 'Mais novo primeiro' e 'Limite de Página'
                $html = $baseUrl . '/carro?ordenarPor=5&registrosPagina='.$pageLimit.'&page=' . $currentPage;

                $dom = $this->getUrlContent($html);

                //Essa div comporta todos os carros da página
                $listOfCars = $this->getCarsUrls($dom->find('.list-of-cards'));

                //Essa div me fornece o numero total de páginas que o seminovosbh possui
                $totalPageNumber = $this->getElementInnerHTML($dom->find('.pagination-container')->find('.info')->find('.cor-laranja')['1']);

                foreach ($listOfCars as $currentSlug) {

                    //A partir daqui, o crawler já pode parar pois os registros seguintes também existirão por ser mais antigos
                    if ($this->carExists($currentSlug)) {

                        $registroExistente = true;

                    } else {

                        $this->insertNewCar($baseUrl . $currentSlug, $currentSlug);

                    }

                }

                //Condição de parada: Registro deve existir ou todas as páginas devem ter sido lidas
                if ($registroExistente || $totalPageNumber <= $currentPage) {

                    $stopTime = true;

                } else {

                    $stopTime = false;

                }

                $currentPage += 1;

            } while (!$stopTime);


            echo json_encode($listOfCars);

        } catch(TypeError  $e) {

            echo $e->getMessage();

        } catch(Exception  $e) {

            echo $e->getMessage();

        }


    }

    /**
     * @param $object
     * @return array
     */
    public function getCarsUrls ($object) : array {

        $card = $object->find('.card');

        $carsToReturn = [];

        foreach ($card as $currentCard) {

            $currentUrl = $currentCard->find('figure')->find('a')->getAttribute('href');
            $carsToReturn[] = $this->optimizeCarUrl($currentUrl);

        }

        //Reverse pois essa biblioteca retorna a ordem invertida. (Primeiro por último, último em primeiro)
        return array_reverse($carsToReturn);

    }

    /**
     * @param string $url
     * @return string
     */
    private function optimizeCarUrl (string $url) : string {

        return explode('?', $url)['0'];

    }

    /**
     * O slug é devido a que a seminovos utiliza esse tipo de string para diferenciação dos carros
     * @param string $slug
     * @return bool
     */
    private function carExists (string $slug) : bool {

        return Veiculo::where('slug', '=', $slug)->exists();

    }

    /**
     * Irá obter dados do carro disponíveis no site e inserir no banco de dados.
     * @param $url
     * @param $slug
     * @throws ChildNotFoundException
     * @throws CircularException
     * @throws CurlException
     * @throws NotLoadedException
     * @throws StrictException
     */
    private function insertNewCar ($url, $slug) {

        $carEspecifications = $this->getCarEspecifications($url);

        //Inserindo dados de tabelas estrageiras
        $idCores = Cor::updateOrCreate
        (
            ['name' => $carEspecifications['cor']],
            ['name' => $carEspecifications['cor']]
        );

        $idVersao = Versao::updateOrCreate
        (
            ['name' => $carEspecifications['versao']],
            ['name' => $carEspecifications['versao']]
        );

        $idCambio = Cambio::updateOrCreate
        (
            ['name' => $carEspecifications['transmissao']],
            ['name' => $carEspecifications['transmissao']]
        );

        $idModelo = Modelo::updateOrCreate
        (
            [
                'name' => $carEspecifications['modelo'],
                'cambio_id' => $idCambio->id,
                'versao_id' => $idVersao->id,
                'ano_fabricacao' => explode('/', $carEspecifications['ano_modelo'])['0'],
                'ano_modelo' => explode('/', $carEspecifications['ano_modelo'])['1'],
                'combustivel' => $carEspecifications['combustivel'],
                'numero_portas' => $carEspecifications['portas'],
            ],
            [
                'name' => $carEspecifications['modelo'],
                'cambio_id' => $idCambio->id,
                'versao_id' => $idVersao->id,
                'ano_fabricacao' => explode('/', $carEspecifications['ano_modelo'])['0'],
                'ano_modelo' => explode('/', $carEspecifications['ano_modelo'])['1'],
                'combustivel' => $carEspecifications['combustivel'],
                'numero_portas' => $carEspecifications['portas'],
            ]
        );

        //Agora será criado o veiculo anunciado
        Veiculo::updateOrCreate
        (
            ['slug' => $slug],
            [
                'slug' => $slug,
                'placa' => $carEspecifications['placa'],
                'valor' => $carEspecifications['valor'],
                'quilometragem' => $carEspecifications['quilometragem'],
                'cor_id' => $idCores->id,
                'modelo_id' => $idModelo->id
            ]
        );

        $veiculoId = DB::getPdo()->lastInsertId();

        //E por fim adicionado todas as características adicionais
        $acessorioIds = [];

        foreach ($carEspecifications['acessorios'] as $currentAcessorio) {

            $currentAcessorioId = Acessorio::updateOrCreate
            (
                ['name' => $currentAcessorio],
                [
                    'name' => $currentAcessorio,
                ]
            );

            VeiculoAcessorio::updateOrCreate
            (
                [
                    'veiculo_id' => $veiculoId,
                    'acessorio_id' => $currentAcessorioId->id,
                ]
            );

        }

    }

    /**
     * @param string $url
     * @return array
     * @throws ChildNotFoundException
     * @throws CircularException
     * @throws CurlException
     * @throws StrictException
     * @throws NotLoadedException
     */
    private function getCarEspecifications (string $url) : array {

        $dom = $this->getUrlContent($url);

        $itemInfo = $dom->find('.item-info');

        $carEspecifications = $itemInfo->find('.attr-list')->find('.row-print')->find('dd');

        return
            [
                'modelo' => $this->getElementInnerHTML($itemInfo->find('h1')),
                'versao' => $this->getElementInnerHTML($itemInfo->find('.col-print-7')->find('p')),
                'valor' => str_replace('<span>R$</span> ', '', str_replace(',', '.', str_replace('.', '', $this->getElementInnerHTML($itemInfo->find('.price'))))),
                'ano_modelo' => $this->getElementInnerHTML($carEspecifications['0']->find('span')),
                'quilometragem' => preg_replace('/[^0-9.]/', '', $this->getElementInnerHTML($carEspecifications['1']->find('span'))),
                'transmissao' => $this->getElementInnerHTML($carEspecifications['2']->find('span')),
                'portas' => $this->getElementInnerHTML($carEspecifications['3']->find('span')),
                'combustivel' => $this->getElementInnerHTML($carEspecifications['4']->find('span')),
                'cor' => $this->getElementInnerHTML($carEspecifications['5']->find('span')),
                'placa' => $this->getElementInnerHTML($carEspecifications['6']->find('span')),
                'acessorios' => $this->getFeatureList($dom->find('.full-features')->find('.list-styled'))
            ];

    }

    public function getFeatureList ($dom) : array {

        $features = [];

        foreach ($dom as $current) {

            $li = $current->find('li');

            foreach ($li as $currentLi) {

                $features[] = $this->getElementInnerHTML($currentLi->find('span'));

            }

        }

        return $features;

    }

    /**
     * @param $element
     * @return string
     */
    private function getElementInnerHTML ($element) : string {

        return trim($element->innerHtml);

    }

    /**
     * @param string $url
     * @return Dom
     * @throws ChildNotFoundException
     * @throws CircularException
     * @throws CurlException
     * @throws StrictException
     */
    private function getUrlContent (string $url) {

        $dom = new Dom;

        $dom->setOptions([
            'strict' => false, //Parâmetro que permite o parse do html
        ]);


        $dom->load($url, [
            'whitespaceTextNode' => true, // Otimiza para que venha sem espaços em branco
        ]);

        return $dom;

    }

}