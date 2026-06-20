<?php

namespace App\Service\External;

use App\DTO\External\TreflePlantDto;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TrefleClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $trefleToken // Injecté via services.yaml
    ) {}

    /**
     * @return TreflePlantDto[]
     */
    public function search(string $query): array
    {
        $response = $this->httpClient->request('GET', 'https://trefle.io/api/v1/plants/search', [
            'query' => [
                'token' => $this->trefleToken,
                'q' => $query,
            ],
        ]);

        $content = $response->toArray();
        $dtos = [];

        foreach ($content['data'] ?? [] as $plantData) {
            $dtos[] = TreflePlantDto::fromArray($plantData);
        }

        return $dtos;
    }
}
