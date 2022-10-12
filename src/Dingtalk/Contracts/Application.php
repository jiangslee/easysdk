<?php

declare(strict_types=1);

namespace EasySdk\Dingtalk\Contracts;

use EasySdk\Kernel\Contracts\AccessToken;
use EasySdk\Kernel\Contracts\Config;
use EasySdk\Kernel\Contracts\Server;
use EasySdk\Kernel\Encryptor;
use EasySdk\Kernel\HttpClient\AccessTokenAwareClient;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

interface Application
{
    public function getAccount(): Account;

    public function getEncryptor(): Encryptor;

    public function getServer(): Server;

    public function getRequest(): ServerRequestInterface;

    public function getClient(): AccessTokenAwareClient;

    public function getHttpClient(): HttpClientInterface;

    public function getConfig(): Config;

    public function getAccessToken(): AccessToken;

    public function getCache(): CacheInterface;
}
