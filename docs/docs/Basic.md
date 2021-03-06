# Plugin functionality

## Generating picture element

To output the transformed image, you can use `picture()` method. It will generate `<picture>` element which on the surface works same as standard `<img>`. Image Toolbox gives it however some very useful properties.

Here's an example usage of `picture()` method. `someAsset` is asset object containing image and `transformSettings` is array of [image transform settings](https://craftcms.com/docs/3.x/image-transforms.html).

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

Transform settings passed to  `picture()` method can be identical to ones used by native Craft image transforms. Providing them is actually optional - you can use `picture()` method like this: 

```twig
{{craft.images.picture(someAsset)}}
```

This would make sense if you just wanted to make use of webp variant creation functionality, without modyfying image in any other way.

If [imager-x](https://plugins.craftcms.com/imager-x) (or [imager](https://plugins.craftcms.com/imager)) plugin is installed, it will be used for image transforms. Thanks to that, you can easily switch your transform generation method without modifying your Twig templates. If you decide to use Imager, SVG images will still use native Craft transforms (unless you decide otherwise in plugin settings). This is because Imager [can cause problems if used with SVG](https://github.com/aelvan/Imager-Craft/issues/136).

## Placeholders

If the image is missing (image object equals `null`), `picture()` will output placeholder image with size based on provided image transform settings. This is very useful for various listing pages where layout depends on existance of image with specific dimensions. By default placeholder is inline SVG transparent image, with `is-plceholder` CSS class applied. You can use this class to style it - for example by giving it nice background image. You can also use placeholder generation service like `https://placeholder.com/`, by setting `placeholderUrl` in plugin setting to something like `https://via.placeholder.com/{width}x{height}`. Width and height in URL will be replaced by values provided in transform settings.

Placeholder can be also generated by `placeholder()` method:

```twig
{{craft.images.placeholder({
    with: 200,
    height: 300,
}) }}
```

If we want to have placeholders outputted by every method regardless if asset exists or not, we can set `forcePlaceholders` config settings to `true`. 

## Applying HTML attributes

You can also apply HTML attributes to `<img>` within `<picture>` - for example CSS Class (applying attributes to `<picture>` directly will cause browser not recognize some of them and it is recommended to use `<img>` inside). This can be done by passing third argument to `picture()` method that contains array of attributes in same format as accepted by [tag()](https://docs.craftcms.com/v3/dev/functions.html#tag) or [attr()](https://docs.craftcms.com/v3/dev/functions.html#attr) Twig functions. More info about these functions can be found in [this article](http://craftsnippets.com/articles/using-attr-function-to-render-html-attributes-in-craft-cms).

## Webp variants of images

Generating **webp** version of image actually depends on a few things. Webp variant will be outputted along with image in original format if:

* Provided image is not in SVG format. It would not make much sense to transform SVG which is a vector graphic format into webp which is used for raster images.
* Our server supports webp image transforms. Webp support can be tested by using Craft `craft.app.images.supportsWebP()` method in your Twig templates - same method that Image toolbox uses internally. If Craft somehow wrongly detects lack of webp support, while server actually does suport it, webp generation can be forced by setting `forceWebp` to `true` in plugin config.
* Our source image is not already webp - no need to create second webp variant of image that is already webp. If however we want to transform webp to other format, both webp and other format variants will be generated.
* We didn't disabled webp generation for this specific picture by adding `useWebp` set to `false` in transform setting. 
* We didn't disabled webp generation globally in plugin settings file using `useWebp` setting.

## Generating picture element with multiple variants

Image Toolbox allows you to create `<picture>` elements which contain variants of image created from **multiuple** image transforms, displayed on specific breakpoints - similarly to how CSS breakpoints work. Here's an example use of `pictureMedia()` method:

```twig
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
    '(min-width: 1000px)': null,
} %}
{{craft.images.pictureMedia(someAsset, transforms)}}
```

As you can see, `sources` object contains multiple image transforms. Key of a single element of object is a string containing breakpoint on which specific transform should be used. Corresponding value is an array of image transform settings. Last element in our example contains `null` instead of transform settings - that's because in this particular case we don't want to actually display any image on this breakpoint.

Here's generated markup. `pictureMin` also generates webp versions of image (and has all other features of `picture()`), but these were omitted from code example for simplicity's sake. 

```html
<picture>
<source type="image/jpeg" srcset="http://website.com/uploads/_100x200_crop_center-center_none/x.jpg" media="(max-width: 600px)">
<source type="image/jpeg" srcset="http://website.com/uploads/_400x500_fit_center-center_none/x.jpg" media="(max-width: 999px)">
<source srcset="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%220%22%20height%3D%220%22%2F%3E" media="(min-width: 1000px)">
<img src="http://website.com/uploads/_100x200_crop_center-center_none/x.jpg">
</picture>
```

Each `<source>` has `media` attribute which contains breakpoint value. Two first sources contain transformed image, but third source which had `null` instead of transform array, contains a transparent pixel. Transform for fallback `<img>` element was taken from **first** transform in `transforms` array.

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

## Simplified breakpoints

Writing breakpoint values like `(max-width: 999px)` can be a bit tedious. Thats why there are also additional `pictureMin()` and `pictureMax()` methods. With them, you can set breakpoints using numeric values which will be transformed into media query string - containing `min-width` for `pictureMin()` and `max-width` for `pictureMax()`. 

For example, using `pictureMin()` like this:

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

Will generate same results as using `pictureMedia()` like this:

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

## Transform layouts

If you use specific image transforms in multiple parts of your template, you can define them in plugin config file and then reference them in templates by their handle. 

Here's an example. Make sure that file `config/image-toolbox.php` exists and put there such settings:

```php
'transformLayouts' => [
    'someHandle' => [
        'variants' => [
            [
                'media' => '(max-width: 999px)',
                'transform' => [
                    'width' => 300,
                    'mode' => 'crop',
                ]
            ],
            [
                'media' => '(min-width: 1000px)',
                'transform' => [
                    'width' => 600,
                    'mode' => 'stretch',
                ]
            ]               
        ],
        'attributes' => [
            'class' => 'some-class'
        ]
    ]
]
```

Our transform layout handle is `someHandle`. Here's how to use it in template:


```twig
{{craft.images.layout('someHandle')}}
```

We could achive the same result by using `pictureMedia()` like this:

```twig
{% set transforms = {
    '(max-width: 999px)': {
        width: 300,
        mode: 'crop',
    },
    '(min-width: 1000px)': {
        width: 600,
        mode: 'stretch',
    }
} %}
{% set attributes ={
    class: 'some-class'
} %}
{{craft.images.pictureMedia(someAsset, transforms, null, attributes)}}
```

Transform layouts can also be used to output `<picture>` with single image transform. Just add only single variant and ommit `media`: 

```php
'transformLayouts' => [
    'someOtherHandle' => [
        'variants' => [
            [
                'transform' => [
                    'width' => 300,
                    'mode' => 'crop',
                ]
            ]           
        ],
        'attributes' => [
            'class' => 'some-class'
        ]
    ]
]
```