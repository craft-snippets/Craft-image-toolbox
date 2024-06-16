# Generating picture element

## Picture HTML element

All images outputted by the plugin are using `<picture>` HTML element instead of just regular `<img>`. On the surface, `<picture>` works same as standard `<img>` - but its main feature is displaying multiple variants of image, in the separate `<source>` tags. One of these sources can then be selected by brower based on breakpoint values set im `media` attribute or specific image format support. 

Image Toolbox uses this feature of `<picture>` for automatic generation of [webp](https://css-tricks.com/using-webp-images/) and avif versions of the images or using different image transforms for specific breakpoints.

## pictureMultiple() method

`craft.images.pictureMultiple()` method can be used to generate `<picture>` element. Here is a simple example:

```twig
{% set someAsset1 = entry.imageField1.one() %}

{% set settings = 
    [
        {
            asset: someAsset1,
            transform: {
                width: 200,
                height: 500,
                mode: 'crop',
            },
        }       
    ]
 %}

{% set htmlAttributes = {
    class: 'some-class',
} %}

{{ craft.images.pictureMultiple(settings, htmlAttributes) }}
```

First parameter of function takes in array of variants of image (in this case we have only one variant). Array contains: 
* `asset` object (if it equals `null`, placeholder image with size based on transform settings will be used instead).
* `transform` settings - either array of [image transform settings](https://craftcms.com/docs/3.x/image-transforms.html), or handle of control panel defined transform.

Second parameter of function is optional array of HTML attributes. This array uses the same attribute definitions supported by using [renderTagAttributes](yii\helpers\BaseHtml::renderTagAttributes()).

This is the generated HTML code. 

```html
<picture>
<source type="image/avif" srcset="http://website.com/uploads/_200x500_crop_center-center_none/image1.avif">
<source type="image/webp" srcset="http://website.com/uploads/_200x500_crop_center-center_none/image1.webp">
<source type="image/jpeg" srcset="http://website.com/uploads/_200x500_crop_center-center_none/image1.jpg">
<img src="http://website.com/uploads/_200x500_crop_center-center_none/image1.jpg" class="some-class">
</picture>
```

As you can see, `<picture>` has three sources - webp source, avif source and jpg source. Browsers will choose the proper version depending on their [webp support](https://caniuse.com/#feat=webp) and ignore other one, so you don't have to worry about downloading redundant versions of image. 

For the browsers that don't [support picture element](https://caniuse.com/#feat=picture) - there is also fallback `<img>` tag inside. This tag is also important because we need to use it to apply HTMl attributes such as class to our image. We cannot do that directly on the `<picture>`.

Note that you can omit `transform` settings, if you want to only use webp/avif variant generation functionality of the plugin, without transforming source image in any other way.

## Picture with multiple breakpoint variants

`craft.images.pictureMultiple()` method can be used to generate `<picture>` with multiple variants, displayed in specific breakpoints. These variants can all use same asset or different ones, as shown below.

```twig
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

{% set htmlAttributes = {
    class: 'some-class',
} %}

{% set commonTransformSettings = {
    quality: 90,
} %}

{{ craft.images.pictureMultiple(settings, htmlAttributes, commonTransformSettings) }}
```

As you can see, each variant contains:

* asset object - each source can have different asset or we can use one same one for all of them.
* transform settings.
* media query value defining when this source should be shown. We can use `media` key for explicit media query.

We also added the third param to the `pictureMultiple()` method - `commonTransformSettings`. These are transform settings that will be applied to all variants. This parameter is optional and can be used to avoid repeating specific settings for each variant. They can be stored in the single array instead.

Here's the generated HTML. While we defined two variants, this `<picture>` has six sources, because each variant will have avif, webp and regular format `<source>`.

```html
<picture>
    <source type="image/avif" srcset="http://website.com/uploads/_200x500_crop_center-center_none/image1.avif" media="(min-width: 1024px)">
<source type="image/jpeg" srcset="http://website.com/uploads/_200x500_crop_center-center_none/image1.webp" media="(min-width: 1024px)">
<source type="image/jpeg" srcset="http://website.com/uploads/_200x500_crop_center-center_none/image1.jpg" media="(min-width: 1024px)">
<source type="image/avif" srcset="http://website.com/uploads/_400x500_fit_center-center_none/image2.avif" media="(max-width: 1023px)">
<source type="image/jpeg" srcset="http://website.com/uploads/_400x500_fit_center-center_none/image2.webp" media="(max-width: 1023px)">
<source type="image/jpeg" srcset="http://website.com/uploads/_400x500_fit_center-center_none/image2.jpg" media="(max-width: 1023px)">
<img src="http://website.com/uploads/_200x500_crop_center-center_none/image1.jpg" class="some-class">
</picture>
```

Instead of setting breakpoint explictly by using `media` and setting it to value like `(max-width: 1023px)`, you may also use `min` and `max` for each source:

```twig
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

If you want your image to not display anything on specific media query, omit `transform` from this variant and set `asset` to null or also omit it. This `<source>` will be generated as the transparent pixel.

## Width and height attributes

Each picture source can have `width` and `height` attribute based on dimensions of transformed image for this source. This can be useful for some lazy loading solutions, althought by default this functionality is disabled. You can enable it by settings `useWidthHeightAttributes` to `true` in plugin settings.

## Avif and webp variants of images

Why do we go through the hassle of generating separate avif and webp version of the image? Webp format can save you 30% to 50% of file size compared to jpg, while avif can decrease file size even 30% more compared to webp. This can mean large increases in the page load time.


Generating avif and webp version of the image by the plugin actually depends on a few things. Avif/webp variants will be outputted along with image in original format if:

* Provided image is not in SVG format. It would not make much sense to transform SVG which is a vector graphic format into webp which is used for raster images.
* Server supports avif/webp image transforms. Avif support can be tested by using Craft `craft.app.images.supportsAvif()` method in your Twig templates, while webp with `craft.app.images.supportsWebP()`. These are the methods that Image toolbox uses internally. If Craft somehow wrongly detects lack of avif/webp support while server actually does support it, avif/webp generation can be forced by setting `forceAvif` to `true` or `forceWebp` to `true` in the plugin config.
* Our source image is not already avif/webp - no need to create duplicate avif/webp variant of image that is already avif/webp. If however we want to transform avif/webp to other format, both avif/webp and other format variants will be generated.
* We didn't disable avif/webp generation for this specific picture by adding `useAvif` set to `false` or `useWebp` set to `false` in the transform setting. 
* We didn't disable avif/webp generation globally in the plugin settings using `useAvif` or `useWebp` setting. **Please note that Avif variant generation is disabled by default, while Webp is enabled by default**.

## Deprecated methods

These methods were used before `pictureMultiple()` was introduced. They are kept for the sake of backwards compatibility. They do not allow using separate assets on multiple breakpoints - all breakpoints share the same asset. 

Please note that in case of the missing asset, when placeholder is generated, `<picture>` outputted by these methods will have `is-placeholder` CSS class applied. This behaviour is missing from `pictureMultuple()` method, since it can use multiple assets, some of which are missing and some of which are not. Placeholder CSS class can be modified using `placeholderClass` config setting.

### picture() method

This method can be used for generating `<picture>` with single variant (which will be generated as two sources - webp and one in original format).

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

If your transform settings used for multiple breakpoints have many identical values (for example same `mode`, `format`, `position` or `quality`), you can pass third parameter containing these common values to `pictureMedia()`. For example, this...

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

...is the same as this:

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

Note that if you use `null` as transform value, source for this transform will be generated as transparent pixel. This can be used if we don't want to display image at all on the specific breakpoint.

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