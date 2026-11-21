# Changelog

Notable changes are recorded here. This project follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

---

## [2.0.0] - 2026-11-21

### Added

- Compatibility with Triangle Engine 9 and Localzet Server 7.
- Runtime compatibility tests for Engine bootstrap, Server session handlers, and file-backed session round trips.
- A MongoDB integration test for session round trips and persistence behavior.

### Changed

- MongoDB session writes disable PHP object instantiation and reject malformed or non-array payloads.
- MongoDB session reads restore BSON documents and arrays as PHP arrays.
