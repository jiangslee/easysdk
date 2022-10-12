<?php

namespace EasySdk\Kernel\Contracts;

interface RefreshableAccessToken extends AccessToken
{
    public function refresh(): string;
}
