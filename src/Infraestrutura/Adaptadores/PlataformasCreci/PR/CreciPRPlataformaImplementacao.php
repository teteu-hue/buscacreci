<?php

declare(strict_types=1);

namespace App\Infraestrutura\Adaptadores\PlataformasCreci\PR;

use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaConsultarCreciPlataforma;
use App\Aplicacao\CasosDeUso\PlataformaCreci;
use Exception;
use GuzzleHttp\Client;

class CreciPRPlataformaImplementacao implements PlataformaCreci
{
    private Client $clientHttp;

    public function __construct()
    {
        $this->clientHttp = new Client([
            "base_uri" => "https://www.crecipr.conselho.net.br",
            "timeout" => 99
        ]);
    }

    private function consultarApiCreci($uri, $body)
    {
        try {
            $response = $this->clientHttp->post($uri, [
                'headers' => [
					'Content-Type' => 'application/x-www-form-urlencoded',
					'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3'
				],
                "form_params" => $body
            ]);
            
        } catch(Exception $e){
            throw new Exception("Erro ao consultar a API do creci PR". $e->getMessage());
        }

        if($response->getStatusCode() !== 200){
            throw new Exception("Creci invalido!");
        }

        return $response;
    }

    public function consultarCreci(string $creci, string $tipoCreci): SaidaConsultarCreciPlataforma
    {
        $creciConsultado = $this->consultarApiCreci('/form_pesquisa_cadastro_geral_site.php', ["inscricao" => $creci]);
        $creciResponse = json_decode($creciConsultado->getBody()->getContents(), true);
        if(empty($creciResponse['cadastros'])){
            throw new Exception("CRECI Inexistente no estado informado");
        }

        if($creciResponse['cadastros'][0]['situacao'] !== 1){
            $situacao = "Invalido!";
        }
        $situacao = "Valido!";
        
        $body = [
            "inscricao" => strval($creciResponse['cadastros'][0]['creci']),
            "nomeCompleto" => $creciResponse['cadastros'][0]['nome'],
            "cidade" => "Parana",
            "estado" => "PR",
            "documento" => $creciResponse['cadastros'][0]['cpf'],
            "fantasia" => ""
        ];

        return new SaidaConsultarCreciPlataforma(
            inscricao: $body['inscricao'],
            nomeCompleto: $body['nomeCompleto'],
            fantasia: $body['fantasia'],
            situacao: $situacao,
            cidade: $body['cidade'],
            estado: $body['estado'],
            numeroDocumento: $body['documento']
        );
    }
}
