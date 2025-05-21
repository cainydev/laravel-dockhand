<?php

namespace Cainy\Dockhand\Enums;
// Or your preferred namespace for enums

enum Platform: string
{
    case AIX_PPC64 = 'aix/ppc64';
    case ANDROID_386 = 'android/386';
    case ANDROID_AMD64 = 'android/amd64';
    case ANDROID_ARM = 'android/arm';
    case ANDROID_ARM64 = 'android/arm64';
    case DARWIN_AMD64 = 'darwin/amd64';
    case DARWIN_ARM64 = 'darwin/arm64';
    case DRAGONFLY_AMD64 = 'dragonfly/amd64';
    case FREEBSD_386 = 'freebsd/386';
    case FREEBSD_AMD64 = 'freebsd/amd64';
    case FREEBSD_ARM = 'freebsd/arm';
    case ILLUMOS_AMD64 = 'illumos/amd64';
    case IOS_ARM64 = 'ios/arm64';
    case JS_WASM = 'js/wasm';
    case LINUX_386 = 'linux/386';
    case LINUX_AMD64 = 'linux/amd64';
    case LINUX_ARM = 'linux/arm';
    case LINUX_ARM64 = 'linux/arm64';
    case LINUX_LOONG64 = 'linux/loong64';
    case LINUX_MIPS = 'linux/mips';
    case LINUX_MIPSLE = 'linux/mipsle';
    case LINUX_MIPS64 = 'linux/mips64';
    case LINUX_MIPS64LE = 'linux/mips64le';
    case LINUX_PPC64 = 'linux/ppc64';
    case LINUX_PPC64LE = 'linux/ppc64le';
    case LINUX_RISCV64 = 'linux/riscv64';
    case LINUX_S390X = 'linux/s390x';
    case NETBSD_386 = 'netbsd/386';
    case NETBSD_AMD64 = 'netbsd/amd64';
    case NETBSD_ARM = 'netbsd/arm';
    case OPENBSD_386 = 'openbsd/386';
    case OPENBSD_AMD64 = 'openbsd/amd64';
    case OPENBSD_ARM = 'openbsd/arm';
    case OPENBSD_ARM64 = 'openbsd/arm64';
    case PLAN9_386 = 'plan9/386';
    case PLAN9_AMD64 = 'plan9/amd64';
    case PLAN9_ARM = 'plan9/arm';
    case SOLARIS_AMD64 = 'solaris/amd64';
    case WASIP1_WASM = 'wasip1/wasm';
    case WINDOWS_386 = 'windows/386';
    case WINDOWS_AMD64 = 'windows/amd64';
    case WINDOWS_ARM = 'windows/arm';
    case WINDOWS_ARM64 = 'windows/arm64';

    /**
     * Create a Platform enum instance from OS and architecture strings.
     *
     * @param string $os
     * @param string $architecture
     * @return self|null Returns the matching enum case or null if no match is found.
     */
    public static function fromOsArch(string $os, string $architecture): ?self
    {
        $platformString = strtolower(trim($os) . '/' . trim($architecture));
        return self::tryFrom($platformString);
    }

    /**
     * Get an array of all platform strings (os/arch).
     * @return string[]
     */
    public static function allValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the Operating System part of the platform string.
     */
    public function os(): string
    {
        return explode('/', $this->value)[0];
    }

    /**
     * Get the Architecture part of the platform string.
     */
    public function architecture(): string
    {
        // The second part is always the architecture in the provided list
        return explode('/', $this->value)[1];
    }

    /**
     * Get the full platform string (os/arch).
     */
    public function toString(): string
    {
        return $this->value;
    }
}
