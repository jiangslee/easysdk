<?php

declare(strict_types=1);

namespace EasySdk\Dingtalk;

use EasySdk\Dingtalk\Contracts\Account as AccountInterface;
use EasySdk\Dingtalk\Contracts\Application as ApplicationInterface;
use EasySdk\Kernel\Contracts\AccessToken as AccessTokenInterface;
use EasySdk\Kernel\Contracts\Server as ServerInterface;
use EasySdk\Kernel\HttpClient\AccessTokenAwareClient;
use EasySdk\Kernel\HttpClient\Response;
use EasySdk\Kernel\Traits\InteractWithCache;
use EasySdk\Kernel\Traits\InteractWithClient;
use EasySdk\Kernel\Traits\InteractWithConfig;
use EasySdk\Kernel\Traits\InteractWithHttpClient;
use EasySdk\Kernel\Traits\InteractWithServerRequest;
use Overtrue\Socialite\Contracts\ProviderInterface as SocialiteProviderInterface;
use Overtrue\Socialite\Providers\DingTalk;
use JetBrains\PhpStorm\Pure;

class Application implements ApplicationInterface
{
    use InteractWithConfig;
    use InteractWithCache;
    use InteractWithServerRequest;
    use InteractWithHttpClient;
    use InteractWithClient;

    protected ?Encryptor $encryptor = null;
    protected ?ServerInterface $server = null;
    protected ?AccountInterface $account = null;
    protected ?JsApiTicket $ticket = null;
    protected ?AccessTokenInterface $accessToken = null;

    public function getAccount(): AccountInterface
    {
        if (!$this->account) {
            $this->account = new Account(
                appKey: (string) $this->config->get('app_key'), /** @phpstan-ignore-line */
                secret: (string) $this->config->get('secret'),  /** @phpstan-ignore-line */
                token: (string) $this->config->get('token'),    /** @phpstan-ignore-line */
                aesKey: (string) $this->config->get('aes_key'), /** @phpstan-ignore-line */
                agentId: (string) $this->config->get('agent_id'), /** @phpstan-ignore-line */
            );
        }

        return $this->account;
    }

    public function setAccount(AccountInterface $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function getEncryptor(): Encryptor
    {
        if (!$this->encryptor) {
            $this->encryptor = new Encryptor(
                appKey: $this->getAccount()->getAppKey(),
                token: $this->getAccount()->getToken(),
                aesKey: $this->getAccount()->getAesKey(),
            );
        }

        return $this->encryptor;
    }

    public function setEncryptor(Encryptor $encryptor): static
    {
        $this->encryptor = $encryptor;

        return $this;
    }

    /**
     * @throws \ReflectionException
     * @throws \EasySdk\Kernel\Exceptions\InvalidArgumentException
     * @throws \Throwable
     */
    public function getServer(): Server|ServerInterface
    {
        if (!$this->server) {
            $this->server = new Server(
                request: $this->getRequest(),
                encryptor: $this->getEncryptor()
            );
        }

        return $this->server;
    }

    public function setServer(ServerInterface $server): static
    {
        $this->server = $server;

        return $this;
    }

    public function getAccessToken(): AccessTokenInterface
    {
        if (!$this->accessToken) {
            $this->accessToken = new AccessToken(
                appKey: $this->getAccount()->getAppKey(),
                secret: $this->getAccount()->getSecret(),
                cache: $this->getCache(),
                httpClient: $this->getHttpClient(),
            );
        }

        return $this->accessToken;
    }

    public function setAccessToken(AccessTokenInterface $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function createClient(): AccessTokenAwareClient
    {
        return new AccessTokenAwareClient(
            client: $this->getHttpClient(),
            accessToken: $this->getAccessToken(),
            failureJudge: fn (Response $response) => !!($response->toArray()['errcode'] ?? 0),
            throw: !!$this->config->get('http.throw', true),
        );
    }

    public function getOAuth(): SocialiteProviderInterface
    {
        return (new DingTalk(
            [
                'client_id' => $this->getAccount()->getAppKey(),
                'client_secret' => $this->getAccount()->getSecret(),
                'redirect_url' => $this->config->get('oauth.redirect_url'),
            ]
        ))->withApiAccessToken($this->getAccessToken()->getToken())
            ->scopes((array) $this->config->get('oauth.scopes', ['snsapi_login']));
    }

    public function getTicket(): JsApiTicket
    {
        if (!$this->ticket) {
            $this->ticket = new JsApiTicket(
                appKey: $this->getAccount()->getAppKey(),
                cache: $this->getCache(),
                httpClient: $this->getClient(),
            );
        }

        return $this->ticket;
    }

    public function setTicket(JsApiTicket $ticket): static
    {
        $this->ticket = $ticket;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getHttpClientDefaultOptions(): array
    {
        return \array_merge(
            ['base_uri' => 'https://oapi.dingtalk.com/',],
            (array)$this->config->get('http', [])
        );
    }

    #[Pure]
    public function getUtils(): Utils
    {
        return new Utils($this);
    }
}
