<?php

use Cainy\Dockhand\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

uses()->group('unit')->in('Unit');
uses()->group('feature')->in('Feature');

function generateEcdsaKeyPair(): array
{
    $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
    $privPath = tempnam(sys_get_temp_dir(), 'dockhand_priv_');
    $pubPath = tempnam(sys_get_temp_dir(), 'dockhand_pub_');
    openssl_pkey_export_to_file($key, $privPath);
    file_put_contents($pubPath, openssl_pkey_get_details($key)['key']);

    return ['private' => $privPath, 'public' => $pubPath];
}

function cleanupKeyPair(array $keys): void
{
    @unlink($keys['private']);
    @unlink($keys['public']);
}

function sampleManifestData(): array
{
    return [
        'mediaType' => 'application/vnd.docker.distribution.manifest.v2+json',
        'schemaVersion' => 2,
        'config' => [
            'mediaType' => 'application/vnd.docker.container.image.v1+json',
            'digest' => 'sha256:configabc123',
            'size' => 1470,
        ],
        'layers' => [
            [
                'mediaType' => 'application/vnd.docker.image.rootfs.diff.tar.gzip',
                'digest' => 'sha256:layer1abc123',
                'size' => 32654,
            ],
            [
                'mediaType' => 'application/vnd.docker.image.rootfs.diff.tar.gzip',
                'digest' => 'sha256:layer2abc123',
                'size' => 16724,
            ],
        ],
    ];
}

function sampleManifestListData(): array
{
    return [
        'mediaType' => 'application/vnd.docker.distribution.manifest.list.v2+json',
        'schemaVersion' => 2,
        'manifests' => [
            [
                'mediaType' => 'application/vnd.docker.distribution.manifest.v2+json',
                'digest' => 'sha256:manifest1abc',
                'size' => 528,
                'platform' => [
                    'os' => 'linux',
                    'architecture' => 'amd64',
                ],
            ],
            [
                'mediaType' => 'application/vnd.docker.distribution.manifest.v2+json',
                'digest' => 'sha256:manifest2abc',
                'size' => 528,
                'platform' => [
                    'os' => 'linux',
                    'architecture' => 'arm64',
                    'variant' => 'v8',
                ],
            ],
        ],
    ];
}

function sampleImageConfigData(): array
{
    return [
        'os' => 'linux',
        'architecture' => 'amd64',
        'created' => '2024-01-15T10:30:00Z',
    ];
}
