# Placeholders

If the image is missing (asset object equals `null`), plugin methods will output placeholder image with size based on the provided image transform settings. This is very useful for various listing pages where layout may depend on existance of image with specific dimensions. If transform settings have only height or width set, placeholder will be generated as the square - with both width and height set to same value, taken from dimenstion that was provided.

## Placeholder modes

There are three methods of placeholder generation - you can select one using `placeholderMode` plugin setting.

### File mode

This is the default method. To use it, set `placeholderMode` to `file`. This method will generate image placeholders, based on source image. To make image fit transform settings, space will be added either from sides or top and bottom.

Plugin provides default source image for the placeholder, but you can change it to your own. Here are the plugins settings that can be used for that:

* `filePlaceholderPath` - file path to source image file, relative to root directory of your project.
* `filePlaceholderBackgroundColor` - hex value of background color that will be applied to empty space in the placeholder image.
* `filePlaceholderBackgroundOpacity` - opacity of background color, with value from 0 to 100.

Placeholder images are outputted into `@web\placeholders` directory by default. This location can be changed with `filePlaceholderDirectory` plugin config setting. Remember that when you are changing placeholder settings, you need to remove old placeholder files so they can be generated again.

### SVG mode

To use it, set `placeholderMode` to `svg`. This method will generate transparent SVG placeholders.

### URL mode

To use it, set `placeholderMode` to `url`. This method will use exteral URLs for placeholders, so you also need to set `placeholderUrl` setting to address of placeholder generation service. Address needs do include image width and height, for example like this: `https://via.placeholder.com/{width}x{height}`. `{height}` and `{width}` in URL be replaced by values taken from transform settings.


## Forcing placeholders

If you want to force all plugin methods use placeholder images, you can set `forcePlaceholders` setting to `true`. This might be useful if you want to create some kind of development copy of the website and transferring all uploaded assets to the other server is too much hassle.

## Placeholder method

Placeholder can be also generated by `placeholder()` method:

```twig
{{craft.images.placeholder({
    with: 200,
    height: 300,
}) }}
```

## Placeholder CSS class

Deprecated `picture()`, `pictureMedia()`, `pictureMax()` and `pictureMin()` methods, when outputting placeholder, apply `is-placeholder` CSS class to `<picture>` element. This behaviour is missing from `pictureMultuple()` method, since it can use multiple assets, some of which are missing and some of which are not. Placeholder CSS class can be modified using `placeholderClass` config setting.