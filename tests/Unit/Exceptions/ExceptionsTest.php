<?php

use Cainy\Dockhand\Exceptions\BlobUnknownException;
use Cainy\Dockhand\Exceptions\DeniedException;
use Cainy\Dockhand\Exceptions\ManifestUnknownException;
use Cainy\Dockhand\Exceptions\ManifestUnverifiedException;
use Cainy\Dockhand\Exceptions\NameInvalidException;
use Cainy\Dockhand\Exceptions\NameUnknownException;
use Cainy\Dockhand\Exceptions\ParseException;
use Cainy\Dockhand\Exceptions\SizeInvalidException;
use Cainy\Dockhand\Exceptions\TagInvalidException;
use Cainy\Dockhand\Exceptions\TimeoutException;
use Cainy\Dockhand\Exceptions\TooManyRequestsException;
use Cainy\Dockhand\Exceptions\UnknownException;

it('creates BlobUnknownException with message', function () {
    $e = new BlobUnknownException('blob not found');
    expect($e)->toBeInstanceOf(Exception::class)
        ->and($e->getMessage())->toBe('blob not found');
});

it('creates DeniedException with message', function () {
    $e = new DeniedException('access denied');
    expect($e)->toBeInstanceOf(Exception::class)
        ->and($e->getMessage())->toBe('access denied');
});

it('creates ManifestUnknownException with message', function () {
    $e = new ManifestUnknownException('manifest not found');
    expect($e)->toBeInstanceOf(Exception::class)
        ->and($e->getMessage())->toBe('manifest not found');
});

it('creates ManifestUnverifiedException with message', function () {
    $e = new ManifestUnverifiedException('unverified');
    expect($e)->toBeInstanceOf(Exception::class)
        ->and($e->getMessage())->toBe('unverified');
});

it('creates NameInvalidException with message', function () {
    $e = new NameInvalidException('invalid name');
    expect($e)->toBeInstanceOf(Exception::class)
        ->and($e->getMessage())->toBe('invalid name');
});

it('creates NameUnknownException with message', function () {
    $e = new NameUnknownException('unknown name');
    expect($e)->toBeInstanceOf(Exception::class)
        ->and($e->getMessage())->toBe('unknown name');
});

it('creates ParseException with message', function () {
    $e = new ParseException('parse error');
    expect($e)->toBeInstanceOf(Exception::class)
        ->and($e->getMessage())->toBe('parse error');
});

it('creates SizeInvalidException with message', function () {
    $e = new SizeInvalidException('invalid size');
    expect($e)->toBeInstanceOf(Exception::class)
        ->and($e->getMessage())->toBe('invalid size');
});

it('creates TagInvalidException with message', function () {
    $e = new TagInvalidException('invalid tag');
    expect($e)->toBeInstanceOf(Exception::class)
        ->and($e->getMessage())->toBe('invalid tag');
});

it('creates TimeoutException with message', function () {
    $e = new TimeoutException('timed out');
    expect($e)->toBeInstanceOf(Exception::class)
        ->and($e->getMessage())->toBe('timed out');
});

it('creates TooManyRequestsException with message', function () {
    $e = new TooManyRequestsException('rate limited');
    expect($e)->toBeInstanceOf(Exception::class)
        ->and($e->getMessage())->toBe('rate limited');
});

it('creates UnknownException with code and message', function () {
    $e = new UnknownException('ERR_CODE', 'something went wrong');
    expect($e)->toBeInstanceOf(Exception::class)
        ->and($e->getMessage())->toBe("Unknown Exception 'ERR_CODE': something went wrong");
});
