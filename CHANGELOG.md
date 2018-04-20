# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [1.0.3] - 2018-04-20

### Changed

- Cleanup composer.json (remove test entry)
- Be more relaxed with php-ffmpeg version constraint (allow recent versions)

## [1.0.2] - 2018-04-20

### Added

- Allow using symfony/process 4.x (requires workaround until underlying dependencies catch up, see https://github.com/emgag/video-thumbnail-sprite/issues/5)

## [1.0.1] - 2018-04-18

### Added

- PHP 7.2 support (Fix version constraints in composer.json)

## [1.0.0] - 2017-08-05

### Added

- Method chaining.
- Support for different thumbnailing tools (ffmpeg and ffmpegthumbnailer).
- Option to keep generated temporary images instead of deleting after sprite assembly (setOutputImageDirectory). 
- Support for Symfony Process >=3.
- Docker images for unit testing.

### Changed

- Version numbers adhere to Semantic Versioning starting with this release.

### Removed
- PHP 5.x is no longer supported. 

## 0.2 - 2015-09-28
Start of recorded history.

[Unreleased]: https://github.com/emgag/video-thumbnail-sprite/compare/v1.0.3...HEAD
[1.0.3]: https://github.com/emgag/video-thumbnail-sprite/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/emgag/video-thumbnail-sprite/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/emgag/video-thumbnail-sprite/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/emgag/video-thumbnail-sprite/compare/v0.2...v1.0.0
