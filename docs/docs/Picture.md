# Generating picture element

All images outputted by plugin are using `<picture>` HTML element instead of just `<img>`. On the surface, `<picture>` works same as standard `<img>`. Image Toolbox gives it however some very useful properties, such as generating webp version of images, or automatic placeholder generation.

## picture() method

`picture()` method can be used to generate simple `<picture>` which contains webp and non-webp variant of image.

In example below, `someAsset` is asset object containing image and `transformSettings` is array of [image transform settings](https://craftcms.com/docs/3.x/image-transforms.html).

```twig
{% set someAsset = entry.imageField.one() %}
{% set transformSettings = {
    width: 100,
    height: 200,
    mode: 'stretch'
} %}
{{craft.images.picture(someAsset, transformSettings)}}
```

Here's the generated HTML:

```html
<picture>
<source type="image/webp" srcset="http://website.com/uploads/_100x200_stretch_center-center_none/3/something.webp">
<source type="image/jpeg" srcset="http://website.com/uploads/_100x200_stretch_center-center_none/something.jpg">
<img src="http://website.com/uploads/_100x200_stretch_center-center_none/something.jpg">
</picture>
```

As you can see, `<picture>` has two `<source>` elements inside - one with **webp** version of the image and one with original image format. Browsers will choose the proper version depending on their [webp support](https://caniuse.com/#feat=webp). For the browsers that don't [support picture element](https://caniuse.com/#feat=picture) - there is also fallback `<img>` tag inside. Using webp format can save you 30% to 50% of file size compared to jpg.

Transform settings can be identical to ones used by native Craft image transforms. Providing them is actually optional - you can use `picture()` method like this: 

```twig
{{craft.images.picture(someAsset)}}
```

This would make sense if you just wanted to make use of webp variant creation functionality, without modyfying image in any other way.

If [imager-x](https://plugins.craftcms.com/imager-x) (or [imager](https://plugins.craftcms.com/imager)) plugin is installed, it will be used for image transforms. Thanks to that, you can easily switch your transform generation method without modifying your Twig templates. If you decide to use Imager, SVG images will still use native Craft transforms (unless you decide otherwise in plugin settings). This is because Imager [can cause problems if used with SVG](https://github.com/aelvan/Imager-Craft/issues/136).

## pictureMultiple() method

`pictureMultiple()` method can be used to generate `<picture>` with multiple variants displayed on separate breakpoints. These variants can all use same asset or different ones, as shown below:

```
{% set someAsset1 = entry.imageField1.one() %}
{% set someAsset2 = entry.imageField2.one() %}

{% set settings = 
    [
        {
            asset: someAsset1,
            transform: {
                width: 200,
                height: 500,
                mode: 'crop',
            },
            media: '(min-width: 1024px)',
        },
        {
            asset: someAsset2,
            transform: {
                width: 100,
                height: 100,
                mode: 'crop',
            },
            media: '(max-width: 1023px)',
        }        
    ]
 %}
{{ craft.images.pictureMultiple(settings) }}
```

Here's the generated HTML:

```html
<picture>
<source type="image/jpeg" srcset="http://website.com/uploads/_200x500_crop_center-center_none/image1.jpg" media="(min-width: 1024px)">
<source type="image/jpeg" srcset="http://website.com/uploads/_400x500_fit_center-center_none/image2.jpg" media="(max-width: 1023px)">
<img src="http://website.com/uploads/_200x500_crop_center-center_none/image1.jpg">
</picture>
```

Just like `picture()`, `pictureMultiple()` also generates webp versions of images, but these were omitted from this example for simplicity's sake. `<img>` fallback was created from first source in `settings` array automatically.

Instead of setting breakpoint explictly by using  `media` and setting it to value like `(max-width: 1023px)`, you may also use `min` and `max` for each source:

```
{% set someAsset1 = entry.imageField1.one() %}
{% set someAsset2 = entry.imageField2.one() %}

{% set settings = 
    [
        {
            asset: someAsset1,
            transform: {
                width: 200,
                height: 500,
                mode: 'crop',
            },
            min: 1024,
        },
        {
            asset: someAsset2,
            transform: {
                width: 100,
                height: 100,
                mode: 'crop',
            },
            max: 1023,
        }        
    ]
 %}
{{ craft.images.pictureMultiple(settings) }}
```

This will generate identical HTML as with using `media` setting.

## Webp variants of images

Generating **webp** version of image actually depends on a few things. Webp variant will be outputted along with image in original format if:

* Provided image is not in SVG format. It would not make much sense to transform SVG which is a vector graphic format into webp which is used for raster images.
* Our server supports webp image transforms. Webp support can be tested by using Craft `craft.app.images.supportsWebP()` method in your Twig templates - same method that Image toolbox uses internally. If Craft somehow wrongly detects lack of webp support, while server actually does suport it, webp generation can be forced by setting `forceWebp` to `true` in plugin config.
* Our source image is not already webp - no need to create second webp variant of image that is already webp. If however we want to transform webp to other format, both webp and other format variants will be generated.
* We didn't disabled webp generation for this specific picture by adding `useWebp` set to `false` in transform setting. 
* We didn't disabled webp generation globally in plugin settings file using `useWebp` setting.

## Deprecated methods

This methods were used before `pictureMultiple()` was introduced. They are kept for the sake of backwards compatibility. They do not allow using separate assets on multiple breakpoints - all breakpoints share same asset. 

### pictureMedia() method

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

### pictureMax() and pictureMin() methods

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