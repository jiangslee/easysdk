<?php

declare(strict_types=1);

namespace EasySdk\Kernel\Contracts;

use EasySdk\Kernel\Contracts\AccessToken as AccessTokenInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

interface AccessTokenAwareHttpClient extends HttpClientInterface
{
    public function withAccessToken(AccessTokenInterface $accessToken): static;
}
