<?php

use Cainy\Dockhand\Exceptions\ExtensionNotEnabledException;

it('has correct message format', function () {
    $exception = new ExtensionNotEnabledException('search');
    expect($exception->getMessage())->toBe("The Zot extension 'search' is not enabled on this server.");
});
