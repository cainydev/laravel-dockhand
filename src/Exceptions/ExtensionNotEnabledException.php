<?php

namespace Cainy\Dockhand\Exceptions;

use Exception;

class ExtensionNotEnabledException extends Exception
{
    public function __construct(string $extension)
    {
        parent::__construct("The Zot extension '{$extension}' is not enabled on this server.");
    }
}
