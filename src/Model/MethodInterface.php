<?php

declare(strict_types = 1);

namespace PHPModelGenerator\Model;

interface MethodInterface
{
    /**
     * Returns the code of the method including the function signature
     */
    public function getCode(): string;
}
