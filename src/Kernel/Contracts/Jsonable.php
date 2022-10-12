<?php

declare(strict_types=1);

namespace EasySdk\Kernel\Contracts;

interface Jsonable
{
    public function toJson(): string|false;
}
