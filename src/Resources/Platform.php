<?php

namespace Cainy\Dockhand\Resources;

use Illuminate\Support\Collection;

readonly class Platform
{
    /**
     * The operating system of this platform.
     */
    public string $os;

    /**
     * The architecture of this platform.
     */
    public string $architecture;

    /**
     * The variant of this platform.
     * @var string|null
     */
    public ?string $variant;

    /**
     * The features of this platform.
     * @var Collection<string>
     */
    public Collection $features;

    /**
     * Create a new Platform instance.
     *
     * @param string $os
     * @param string $architecture
     * @param string|null $variant
     * @param ?Collection<string> $features
     */
    public function __construct(string $os, string $architecture, ?string $variant = null, ?Collection $features = null)
    {
        $this->os = $os;
        $this->architecture = $architecture;
        $this->variant = $variant;
        $this->features = collect($features);
    }

    /**
     * Create a new Platform instance.
     *
     * @param string $os
     * @param string $architecture
     * @param string|null $variant
     * @param ?Collection<string> $features
     * @return self
     */
    public static function create(string $os, string $architecture, ?string $variant = null, ?Collection $features = null): self
    {
        return new self($os, $architecture, $variant, $features);
    }

    /**
     * Parse a platform from an array.
     *
     * @param array $data
     * @return ?self
     */
    public static function parse(array $data): ?self
    {
        $os = (string)($data['os']);
        $architecture = (string)($data['architecture']);
        $variant = (string)($data['variant'] ?? null);
        $features = collect($data['features']);

        if (empty($os) || empty($architecture)) {
            return null;
        }

        return new self($os, $architecture, $variant, $features);
    }

    /**
     * Get the full platform string as os/arch(/variant).
     */
    public function toString(): string
    {
        return $this->os . '/' . $this->architecture . (!empty($this->variant) ? '/' . $this->variant : '');
    }

    /**
     * Check if the platform is valid. Source https://go.dev/doc/install/source#environment.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if (empty($this->os) || empty($this->architecture)) return false;

        $validCombinations = collect([
            'aix' => ['ppc64'],
            'android' => ['386', 'amd64', 'arm', 'arm64'],
            'darwin' => ['amd64', 'arm64'],
            'dragonfly' => ['amd64'],
            'freebsd' => ['386', 'amd64', 'arm'],
            'illumos' => ['amd64'],
            'ios' => ['arm64'],
            'js' => ['wasm'],
            'linux' => ['386', 'amd64', 'arm', 'arm64', 'loong64', 'mips', 'mipsle', 'mips64', 'mips64le', 'ppc64', 'ppc64le', 'riscv64', 's390x'],
            'netbsd' => ['386', 'amd64', 'arm'],
            'openbsd' => ['386', 'amd64', 'arm', 'arm64'],
            'plan9' => ['386', 'amd64', 'arm'],
            'solaris' => ['amd64'],
            'wasip1' => ['wasm'],
            'windows' => ['386', 'amd64', 'arm', 'arm64'],
        ]);

        if ($validCombinations->contains($this->os)) {
            return in_array($this->architecture, $validCombinations->get($this->os));
        } else {
            return false;
        }
    }
}
