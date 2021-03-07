# Method list

* `craft.images.picture(image, transform, htmlAttributes)` - generates `<picture>` element.

* `craft.images.pictureMedia(image, transforms, commonSettings, htmlAttributes)` - generates `<picture>` element from multiple image transforms. Breakpoints are set in format like `(max-width: 600px)`.

* `craft.images.pictureMin(image, transforms, commonSettings, htmlAttributes)` - generates `<picture>` element from multiple image transforms. Breakpoints are created from number that creates `min-width`.

* `craft.images.pictureMax(image, transforms, commonSettings, htmlAttributes)` - generates `<picture>` element from multiple image transforms. Breakpoints are created from number that creates `max-width`.

* `craft.images.layout(handle)` - generates `<picture>` element from multiple image transforms. Breakpoints and transform settings are defined in plugin configuration file.

* `craft.images.placeholder(transform)` - generates image placeholder based `width` and `height` settings.
