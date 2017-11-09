<?php

namespace Gregurco\Bundle\GuzzleBundleCachePlugin\Event;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\UriInterface;
use Symfony\Component\EventDispatcher\Event;
use function GuzzleHttp\Psr7\uri_for;

class InvalidateRequestEvent extends Event
{
    /**
     * @var Request[]
     */
    private $requests;

    /**
     * @var Client
     */
    private $client;

    /**
     * InvalidateRequestEvent constructor.
     *
     * @param Client    $client
     * @param Request[] $requests
     */
    public function __construct(Client $client, array $requests)
    {
        $this->client = $client;

        array_walk($requests, [$this, 'addRequest']);
    }

    /**
     * @param Request $request
     */
    public function addRequest(Request $request): void
    {
        $request          = $request->withUri($this->buildUri($this->client, $request->getUri()));
        $this->requests[] = $request;
    }

    /**
     * @param Client $client
     * @param string $uri
     *
     * @return UriInterface
     */
    private function buildUri(Client $client, string $uri): UriInterface
    {
        $uri = uri_for($uri);

        $baseUri = $client->getConfig('base_uri');
        if (null !== $baseUri) {
            $uri = UriResolver::resolve(uri_for($baseUri), $uri);
        }

        return $uri->getScheme() === '' && $uri->getHost() !== '' ? $uri->withScheme('http') : $uri;
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @return Request[]
     */
    public function getRequests(): array
    {
        return $this->requests;
    }
}
