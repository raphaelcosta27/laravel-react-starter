<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Tenta os serviços em ordem. Só o CEP sai daqui. As chaves cobrem as respostas
 * da ViaCEP e da BrasilAPI.
 */
class PostalCodeLookup
{
    /**
     * @return array{street: string, neighborhood: string, city: string, state: string}|null null quando o CEP não existe
     *
     * @throws ConnectionException quando nenhum serviço respondeu
     */
    public function find(string $postalCode): ?array
    {
        foreach (config('template.address.postal_code_urls') as $url) {
            try {
                $response = Http::acceptJson()->timeout(3)->get(str_replace('{cep}', $postalCode, $url));
            } catch (ConnectionException) {
                continue;
            }

            if ($this->notFound($response)) {
                return null;
            }

            $state = $response->json('uf') ?? $response->json('state');

            if ($response->successful() && is_string($state) && $state !== '') {
                return [
                    'street' => (string) ($response->json('logradouro') ?? $response->json('street')),
                    'neighborhood' => (string) ($response->json('bairro') ?? $response->json('neighborhood')),
                    'city' => (string) ($response->json('localidade') ?? $response->json('city')),
                    'state' => $state,
                ];
            }
        }

        throw new ConnectionException('Postal code services are unavailable.');
    }

    private function notFound(Response $response): bool
    {
        return in_array($response->status(), [400, 404], true) || filter_var($response->json('erro'), FILTER_VALIDATE_BOOL);
    }
}
