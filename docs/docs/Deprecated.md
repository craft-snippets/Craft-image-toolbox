# Deprecated methods

This methods were used before `pictureMultiple()` was introduced. They are kept for the sake of backwards compatibility. They do not allow using separate assets on multiple breakpoints - all breakpoints share same asset. 

## picture() method

This method can be used for generating `<picture>` with single variant (which will be generatet as two sources - webp and one in original format).

```twig
In the example below, `someAsset` is asset object containing image, and `transformSettings` is array of [image transform settings](https://craftcms.com/docs/3.x/image-transforms.html).

```twig
{% set someAsset = entry.imageField.one() %}
{% set transformSettings = {
    width: 100,
    height: 200,
    mode: 'stretch'
} %}
{% set htmlAttributes = {
    class: 'some-class',
} %}
{{craft.images.picture(someAsset, transformSettings, htmlAttributes)}}
```

## pictureMedia() method

Here's how it is used:

```
{% set someAsset = entry.imageField.one() %}
{% set transforms = {
    '(max-width: 600px)': {
        width: 100,
        height: 200,
        mode: 'crop',
    },
    '(max-width: 999px)': {
        width: 400,
        height: 500,
        mode: 'fit',
    },
} %}
{{craft.images.pictureMedia(someAsset, transforms)}}
```

As you can see, `sources` object contains multiple image transforms. Key of a single element of object is a string containing breakpoint on which specific transform should be used. Corresponding value is an array of image transform settings.

If your multiple tranform settings used for breakpoints have many identical values (for example same `mode`, `format`, `position` or `quality`), you can pass to `pictureMedia` third parameter containing these common values. For example, this...

```twig
{% set transforms = {
    '(max-width: 600px)': {
        width: 100,
    },
    '(max-width: 999px)': {
        width: 400,
    },
} %}
{% set common = {
    mode: 'fit',
    quality: 80,
    position: 'top-center',
    format: 'png',
} %}
{{craft.images.pictureMedia(someAsset, transforms, common)}}
```

...is same as this:

```twig
{% set transforms = {
    '(max-width: 600px)': {
        width: 100,
        mode: 'fit',
        quality: 80,
        position: 'top-center',
        format: 'png',      
    },
    '(max-width: 999px)': {
        width: 400,
        mode: 'fit',
        quality: 80,
        position: 'top-center',
        format: 'png',      
    },
} %}
{{craft.images.pictureMedia(someAsset, transforms)}}
```

Note that if you use `null` as transform value, source for this transform will be generated as transparent pixel. This can be used if we dont want to display image at all on the specific breakpoint.

## pictureMax() and pictureMin() methods

Instead of explictly setting `media` values, these methods use numbers. For example, you can use `pictureMin()` like this:

```twig
{% set transforms = {
    1: {
        width: 100,
        height: 200,
        mode: 'crop',
    },
    600: {
        width: 400,
        height: 500,
        mode: 'fit',
    }
} %}
{{craft.images.pictureMin(someAsset, transforms)}}
```

This will generate same results as using `pictureMedia()` like this:

```twig
{% set transforms = {
    '(min-width: 1px)': {
        width: 100,
        height: 200,
        mode: 'crop',
    },
    '(min-width: 600px)': {
        width: 400,
        height: 500,
        mode: 'fit',
    }
} %}
{{craft.images.pictureMedia(someAsset, transforms)}}
```

Note, that browser always uses first `<source>` with media query that fits. So if you are on screen of width 1024px, and first source is one with media query `(min-width: 300px)`, it would be used - even if there is other, with breakpoint `(min-width: 600px)`. That's why sources in `pictureMin()` are automatically sorted from ones with the largest min-width, to smallest. Correspondingly, for `pictureMax()`, sources are sorted from smallest `max-width` value to one with largest.