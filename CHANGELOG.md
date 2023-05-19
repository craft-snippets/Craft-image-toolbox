# Image toolbox Changelog

## 2.1.0 - 2023.05.15

### Added
* Added pictureMultiple() method, allowing for generating picture element with each source using different asset.
* Added file placeholder mode, which generates placeholders based on source image file.
* Transform layouts can now define HTML attributes using anonymous function. This allows defining attributes using asset object attributes.
* Transform layouts and pictureMultiple() methods can use control panel defined image transforms.

### Fixed
* Fixed bug with placeholder not generating correctly when showing width and height is enabled.
* Fixed bug with of throwing error whan trying to get width and height of svg file.

### Deprecated
* `picture()`, `pictureMedia()`, `pictureMax()` and `pictureMin()` are now deprecated.

## 2.0.2 - 2023.05.15
### Fixed
* Width and height attributes are now properly calculated when width or height is missing from the transform.
* Width and height attributes are now added to the fallback img tag of picture element.
* Fixed the bug with width being used for height attribute if no transform settings were used for generating picture element.

## 2.0.1 - 2023.04.13
### Added
* Added support for displaying width and height attributes on image sources when "useWidthHeightAttributes" setting is set to true

## 2.0.0 - 2022.05.25
### Added
* Added Craft CMS 4 support

### Fixed
* Fixed bug with `pictureMedia()` throwing error if asset was null and one of breakpoints transform was also also null.
* Fixed bug with `picture()` function throwing error if asset was null and no transform array was given.

## 1.1.0 - 2021.03.11
### Added
* Added `forceWebp` config setting.
* Added `forcePlaceholders` config setting.

### Fixed
* Code refactoring

## 1.0.0 - 2020-10-18
### Added
- Initial release
