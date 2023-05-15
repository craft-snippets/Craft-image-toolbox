# Image toolbox Changelog

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
