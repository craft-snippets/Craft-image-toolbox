# Method list

For detailed information about each method, please visit [Plugin functionality](Basic) page.

* `craft.images.pictureMultiple(imageVariants, htmlAttributes)` - generates `<picture>` element with multiple variants. `variants` param is array of objects where each object can have these properties:
	* `asset` - asset object for variant
	* `transform` - asset transform settings for variant. Can be array of transform settings or handle of control panel defined transform.
	* `media` - breakpoint value for variant, for example: `(min-width: 1024px)`
	* `max` - alternative to `media` - numeric value which will be converted to `max-width` breakpoint
	* `min` - alternative to `media` - numeric value which will be converted to `min-width` breakpoint

* `craft.images.layout(handle)` - generates `<picture>` element from multiple image transforms. Breakpoints and transform settings are defined in plugin configuration file.

* `craft.images.placeholder(transform)` - generates image placeholder based `width` and `height` settings.

Deprecated methods:

* `craft.images.picture(image, transform, htmlAttributes)` - generates `<picture>` element.

* `craft.images.pictureMedia(image, transforms, commonSettings, htmlAttributes)` - generates `<picture>` element from multiple image transforms. Breakpoints are set in format like `(max-width: 600px)`.

* `craft.images.pictureMin(image, transforms, commonSettings, htmlAttributes)` - generates `<picture>` element from multiple image transforms. Breakpoints are created from number that creates `min-width`.

* `craft.images.pictureMax(image, transforms, commonSettings, htmlAttributes)` - generates `<picture>` element from multiple image transforms. Breakpoints are created from number that creates `max-width`.