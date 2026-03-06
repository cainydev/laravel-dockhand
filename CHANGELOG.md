# Changelog

All notable changes to `laravel-dockhand` will be documented in this file.

## v0.3.0 - 2026-03-06

### Added
- Multi-connection architecture with `DockhandManager` supporting named connections
- Driver system: `distribution` (standard OCI) and `zot` (with extensions)
- Five authentication drivers: `jwt`, `basic`, `bearer`, `apikey`, `null`
- Typed exception classes for all OCI Distribution Specification error codes
- `Authenticator`, `RegistryDriver`, and `ZotCapabilities` contracts
- Zot driver extensions: search (GraphQL), CVE search, star/unstar, bookmark/unbookmark
- Blob operations: `getBlob()`, `getBlobSize()`, `getImageConfigFromDescriptor()`
- `Dockhand::connection()`, `Dockhand::zot()`, `Dockhand::distribution()` typed accessors
- `Dockhand::disconnect()` for releasing connections in long-running workers
- `getReferrers()` support on all drivers
- Comprehensive unit and feature test suites (263 tests, 441 assertions)

### Changed
- **Breaking:** Config structure changed from single-connection to multi-connection (`dockhand.connections.*`)
- **Breaking:** Removed `Dockhand` class in favor of `DockhandManager` with driver pattern
- **Breaking:** Resources are now `readonly` classes — no longer active record style
- All resource classes now implement `Arrayable` and `JsonSerializable`
- Events use PHP 8.4 property hooks with asymmetric visibility
- `Scope` helper uses PHP 8.4 property hooks for `$actions`
- `TokenService` singleton now resolves from the default connection's JWT authenticator
- Updated Dockhand facade with full PHPDoc method annotations
- Updated CI workflows for PHP 8.4

### Removed
- `Dockhand` monolithic class (replaced by driver architecture)
- `getRepository()` and `getManifestOfTag()` methods
- `spatie/laravel-ray` dependency

## v0.2.1 - Previous Release

## v0.2.0 - Previous Release

## v0.1.0 - Initial Release
