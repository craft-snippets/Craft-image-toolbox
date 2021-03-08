# Quick start

To output `<picture>` element with webp variant in your template, use `picture` method. If asset is missing (it is `null`), placeholder with dimensions based on transform settings will be outputted instead.

```twig
{# asset object containing image #}
{% set someAsset = entry.someAssetField.one() %}

{# transform settings, same syntax as used by regular Craft image transforms #}
{% set transformSettings = {
    width: 100,
    height: 200,
    mode: 'stretch'
} %}

{# array of HTML attributes used in generated picture #}
{# same syntax as used by Craft tag() function #}
{% set htmlAttributes = {
	class: 'some-class',
} %}

{{craft.images.picture(someAsset, transformSettings, htmlAttributes)}}
```

Here's example HTML output:

```html
<picture>
<source type="image/webp" srcset="http://website.com/uploads/_100x200_stretch_center-center_none/3/something.webp">
<source type="image/jpeg" srcset="http://website.com/uploads/_100x200_stretch_center-center_none/something.jpg">
<img src="http://website.com/uploads/_100x200_stretch_center-center_none/something.jpg" class="some-class">
</picture>
```

For detailed information about plugin functionality, including generating `<picture>` elements with multiple breakpoints, visit [Plugin functionality](Basic.md) documentation page.