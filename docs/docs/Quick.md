# Quick start

To output `<picture>` element with [webp](https://css-tricks.com/using-webp-images/) variant in your template, use `picture()` method. If the asset is missing (it equals `null`), placeholder with dimensions based on transform settings will be outputted instead.

```twig
{# asset object containing image #}
{% set someAsset = entry.someAssetField.one() %}

{# transform settings, same syntax as used by regular Craft image transforms #}
{% set transformSettings = {
    width: 100,
    height: 200,
    mode: 'stretch'
} %}

{# array of HTML attributes used in generated picture - optional parameter #}
{# same syntax as used by Craft tag() function #}
{% set htmlAttributes = {
	class: 'some-class',
} %}

{{craft.images.picture(someAsset, transformSettings, htmlAttributes)}}
```

Here's the example HTML output. `<picture>` has two sources. One in the webp format and one in the jpg format. Depending on [webp support](https://caniuse.com/webp), browser will pick proper one. If browser does not support `<picture>` element, fallback `<img>` element will be used.

```html
<picture>
	<source type="image/webp" srcset="http://website.com/uploads/_100x200_stretch_center-center_none/3/something.webp">
	<source type="image/jpeg" srcset="http://website.com/uploads/_100x200_stretch_center-center_none/something.jpg">
	<img src="http://website.com/uploads/_100x200_stretch_center-center_none/something.jpg" class="some-class">
</picture>
```

For the detailed information about the plugin functionality, including generating `<picture>` elements with multiple variants, visit other pages of documentation.