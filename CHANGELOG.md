# Changelog

Notable changes are recorded here. This project follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - Unreleased

This dependency change requires the next major package release: Engine 9 uses the Server 7 session API, which is not available in the Engine 8.1 / Server 4 dependency graph.

### Added

- Compatibility with Triangle Engine 9 and Localzet Server 7.
- Runtime compatibility tests for Engine bootstrap, Server session handlers, and file-backed session round trips.
- A MongoDB integration test for session round trips and persistence behavior.

### Changed

- MongoDB session writes disable PHP object instantiation and reject malformed or non-array payloads.
- MongoDB session reads restore BSON documents and arrays as PHP arrays.
