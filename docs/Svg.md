# Svg images

Image toolbox provides `svgFile()` method that wrapped on Craft [svg()](https://craftcms.com/docs/4.x/dev/functions.html#svg) function. It can be used with Asset object or file path string. Just like regular `svg()`, it outputs markup of SVG image.

This method accepts three parameters:

* Asset object, path of SVG file within a project (aliases can be used) or null (nothing will be returned). In case of asset object, if asset is not SVG, exception will be thrown (unless `suppressExceptions` is set to `true` in the plugin settings).
* Object containing HTML attributes that should be applied to the SVG element. This array uses the same attribute definitions supported by using [renderTagAttributes](yii\helpers\BaseHtml::renderTagAttributes()).
* Array containing additional options. 

Additional options can be used to remove styles from outputted SVG, so we can style it using our own CSS. 
* `removeFill` - if fill attributes should be removed from SVG. Default: `false`.
* `removeStroke` - if stroke attributes should be removed from SVG. Default: `false`.
* `removeCss` - if `<style>` tags should be removed from SVG. Default: `false`.
* `sanitize` - if `svg()` function should sanitize SVG. Default: `true`.
* `namespace` - if `svg()` function should namespace SVG. Default: `true`.

Here's the example usage of `svgFile()` method, with asset object and file path:

```twig
{% set someAsset = entry.imageField.one() %}
{{ craft.images.svgFile(someAsset, {class: 'abc'}, {
    removeCss: true,
}) }}

{% svgPath = 'someFile' %}
{{ craft.images.svgFile(svgPath, {class: 'xyz'}, {
    removeCss: true,
}) }}
```

If we use `svgFile()`, with file path, we can skip `.svg` extension from it. We can also use plugin setting `svgDirectory` to specify directory from where SVG files should be loaded. So, if `svgDirectory` is set to `@webroot/svg`, instead of using `web/svg/someFile.svg` path, we can just use `someFile`.

```twig
{{ craft.images.svgFile('someFile') }}
```