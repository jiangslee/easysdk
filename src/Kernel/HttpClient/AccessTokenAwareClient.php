<?php

declare(strict_types=1);

namespace EasySdk\Kernel\HttpClient;

use Closure;
use EasySdk\Kernel\Contracts\AccessToken as AccessTokenInterface;
use EasySdk\Kernel\Contracts\AccessTokenAwareHttpClient as AccessTokenAwareHttpClientInterface;
use EasySdk\Kernel\Traits\MockableHttpClient;
use Symfony\Component\HttpClient\AsyncDecoratorTrait;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function array_merge;

class AccessTokenAwareClient implements AccessTokenAwareHttpClientInterface
{
    use AsyncDecoratorTrait;
    use HttpClientMethods;
    use RetryableClient;
    use MockableHttpClient;
    use RequestWithPresets;

    public function __construct(
        ?HttpClientInterface $client = null,
        protected ?AccessTokenInterface $accessToken = null,
        protected ?Closure $failureJudge = null,
        protected bool $throw = true
    ) {
        $this->client = $client ?? HttpClient::create();
    }

    public function withAccessToken(AccessTokenInterface $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $options
     * @throws TransportExceptionInterface
     */
    public function request(string $method, string $url, array $options = []): Response
    {
        if ($this->accessToken) {
            $options['query'] = array_merge((array) ($options['query'] ?? []), $this->accessToken->toQuery());
        }

        $options = RequestUtil::formatBody($this->mergeThenResetPrepends($options));

        return new Response(
            response: $this->client->request($method, ltrim($url, '/'), $options),
            failureJudge: $this->failureJudge,
            throw: $this->throw
        );
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->client->$name(...$arguments);
    }

    public static function createMockClient(MockHttpClient $mockHttpClient): HttpClientInterface
    {
        return new self($mockHttpClient);
    }
}
