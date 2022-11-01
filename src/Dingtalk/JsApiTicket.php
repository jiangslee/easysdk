<?php

declare(strict_types=1);

namespace EasySdk\Dingtalk;

use EasySdk\Kernel\Exceptions\HttpException;
use JetBrains\PhpStorm\ArrayShape;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class JsApiTicket
{
    protected HttpClientInterface $httpClient;
    protected CacheInterface $cache;

    public function __construct(
        protected string $appKey,
        protected ?string $key = null,
        ?CacheInterface $cache = null,
        ?HttpClientInterface $httpClient = null
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create(['base_uri' => 'https://oapi.dingtalk.com/']);
        $this->cache = $cache ?? new Psr16Cache(new FilesystemAdapter(namespace: 'easydingtalk', defaultLifetime: 1500));
    }

    /**
     * @return array<string, mixed>
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \EasySdk\Kernel\Exceptions\HttpException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     */
    #[ArrayShape([
        'url' => "string",
        'nonceStr' => "string",
        'timestamp' => "int",
        'appId' => "string",
        'signature' => "string",
    ])]
    public function createConfigSignature(string $url, string $nonce, int $timestamp): array
    {
        return [
            'appId' => $this->appKey,
            'nonceStr' => $nonce,
            'timestamp' => $timestamp,
            'url' => $url,
            'signature' => $this->getTicketSignature($this->getTicket(), $nonce, $timestamp, $url),
        ];
    }

    public function getTicketSignature(string $ticket, string $nonce, int $timestamp, string $url): string
    {
        return sha1(sprintf('jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s', $ticket, $nonce, $timestamp, $url));
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \EasySdk\Kernel\Exceptions\HttpException
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     */
    public function getTicket(): string
    {
        $key = $this->getKey();
        $ticket = $this->cache->get($key);

        if (!!$ticket && \is_string($ticket)) {
            return $ticket;
        }

        $response = $this->httpClient->request('GET', '/get_jsapi_ticket')->toArray(false);

        if (empty($response['ticket'])) {
            throw new HttpException('Failed to get jssdk ticket: '.json_encode($response, JSON_UNESCAPED_UNICODE));
        }

        $this->cache->set($key, $response['ticket'], \intval($response['expires_in']));

        return $response['ticket'];
    }

    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getKey(): string
    {
        return $this->key ?? $this->key = \sprintf('dingtalk.jsapi_ticket.%s', $this->appKey);
    }
}
