# Method list

For detailed information about each method, please visit [Plugin functionality](Basic) page.

* `craft.images.pictureMultiple(imageVariants, htmlAttributes)` - generates `<picture>` element with multiple variants.

	* `variants` param is array of objects where each object can have these properties:
		* `asset` - asset object for variant, can be also set to `null` to generate placeholder
		* `transform` - asset transform settings for variant. Can be array of transform settings or handle of control panel defined transform.
		* `media` - breakpoint value for variant, for example: `(min-width: 1024px)`
		* `max` - alternative to `media` - numeric value which will be converted to `max-width` breakpoint
		* `min` - alternative to `media` - numeric value which will be converted to `min-width` breakpoint

	* `htmlAttributes` param is array of HTML attributes that will be applied to `<img>` tag within a `<picture>`. This array uses the same attribute definitions supported by using [renderTagAttributes](yii\helpers\BaseHtml::renderTagAttributes()).

* `craft.images.layout(handle, htmlAttributes)` - generates `<picture>` element based on configuration within `transformLayouts` plugin config setting.

* `craft.images.placeholder(transform)` - generates image placeholder based `width` and `height` settings.

Deprecated methods:

* `craft.images.picture(image, transform, htmlAttributes)` - generates `<picture>` element.

* `craft.images.pictureMedia(image, transforms, commonSettings, htmlAttributes)` - generates `<picture>` element from multiple image transforms. Breakpoints are set in format like `(max-width: 600px)`.

* `craft.images.pictureMin(image, transforms, commonSettings, htmlAttributes)` - generates `<picture>` element from multiple image transforms. Breakpoints are created from number that creates `min-width`.

* `craft.images.pictureMax(image, transforms, commonSettings, htmlAttributes)` - generates `<picture>` element from multiple image transforms. Breakpoints are created from number that creates `max-width`.