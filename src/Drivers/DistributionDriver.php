<?php

namespace Cainy\Dockhand\Drivers;

class DistributionDriver extends AbstractRegistryDriver
{
    public function getReferrers(string $repository, string $digest, ?string $artifactType = null): array
    {
        $url = "/{$repository}/referrers/{$digest}";

        if ($artifactType !== null) {
            $url .= '?'.http_build_query(['artifactType' => $artifactType]);
        }

        try {
            $response = $this->authenticatedRequest('read', $repository)
                ->get($url);
        } catch (\Exception $e) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        /** @var array<int, mixed> $manifests */
        $manifests = $response->json('manifests', []);

        return $manifests;
    }
}
