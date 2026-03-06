<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('contracts are interfaces')
    ->expect('Cainy\Dockhand\Contracts')
    ->toBeInterfaces();

arch('enums are enums')
    ->expect('Cainy\Dockhand\Enums')
    ->toBeEnums();

arch('actions are traits')
    ->expect('Cainy\Dockhand\Actions')
    ->toBeTraits();

arch('facades extend Facade')
    ->expect('Cainy\Dockhand\Facades')
    ->toExtend('Illuminate\Support\Facades\Facade');

arch('resources implement Arrayable and JsonSerializable')
    ->expect('Cainy\Dockhand\Resources')
    ->toImplement('Illuminate\Contracts\Support\Arrayable')
    ->toImplement('JsonSerializable');
