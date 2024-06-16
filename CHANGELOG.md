# Image toolbox Changelog


## 2.3.1 - 2024.06.16
### Fixed
* Avif generation is now disabled by default.
* svgFile() function - HTML attributes array parameter is now optional.

## 2.3.0 - 2024.04.04
### Added
* Added the `svgFile` method.
* Added the automatic avif image variants generation.

## 2.2.2 - 2023.07.18
### Added
* Added the PRO edition of the plugin. This edition adds "Image variants" field which allows admins to define picture configuration in the control panel. Field is assigned to the asset source and picture configuration can be set for the whole asset source (using the field settings), or for the specific asset (using the field values).
* Added the third parameter to `pictureMultiple` function - "common settings". It allows to define transform settings that will be used in all sources of picture element.

### Fixed
* Updated deprecation message for deprecated functions, so developers are aware that new functions have different syntaxt compared to deprecated ones.
* Fixed the issue with adding width and height attributes to sources causing performance issues.

## 2.1.2 - 2023.05.19
### Changed
* Default placeholder mode now is now **file** - compared to pre 2.1.0 **svg** mode. If you want to keep using SVG mode, set `placeholderMode` to `svg` in `image-toolbox.php` config file.
* Before 2.1.0, placeholder **url** mode was enabled just by entering URL value into `placeholderUrl` plugin setting. Now it also needs to be enabled by setting `placeholderMode` to `url` in `image-toolbox.php` config file.

## 2.1.0 - 2023.05.19
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
