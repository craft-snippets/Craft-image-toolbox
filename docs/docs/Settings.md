# Settings

Place these settings in `config/image-toolbox.php` file.

* `useWebp` - if webp version of image should automatically be generated. Default: `true`.
* `forceWebp` - if webp version of image should be generated even if Craft detects lack of webp support on server. Useful if Craft somehow wrongly detects lack of webp support. Default: `false`.
* `useImager` - if Imager-x or Imager should be used for transforms (assuming one of these plugins is installed). Default: `true`.
* `useImagerForSvg` - if imager should be used also for SVG images. Default: `false`.
* `transformLayouts` - pre-defined transform settings. Read more in "Transform layouts" section of documentation.
* `useWidthHeightAttributes` - if width and height attributes should be added to source (and fallback img) elements. Default: `false`.
* `usePlaceholders` - if placeholder should be generated if image is missing (asset object equals `null`). Default: `true`.
* `forcePlaceholders` - if placeholders should be outputted by every method instead of transformed images.
* `placeholderMode` - mode used when generating placeholders. Possible values: `file`, `svg`, `url`. Default value: `file`.
* `filePlaceholderPath` - location (relative to the project root) of the source file used with placeholder **file mode**.
* `filePlaceholderBackgroundColor` - hex value of background color used when generating placeholder files with placeholder **file mode**.
* `filePlaceholderBackgroundOpacity` - opacity of background color used when generating placeholder files with placeholder **file mode**. Possible values: `1` - `100`.
* `filePlaceholderDirectory` - path where generated placeholder files will be outputted, relative to the project root. 
* `placeholderUrl` - URL of placeholder image, used in **url mode**. `{width}` and `{height}` in URL will be replaced with width and height of placeholder.
* `suppressExceptions` - if errors should be thrown when passing incorrect parameters to the functions or if methods should fail siliently, not outputting anything at all.

Deprecated:

* `placeholderClass` - CSS class added to `<img>` inside `<picture>` element if placeholder image is displayed. Default: `is-placeholder`. Used unly with deprecated `picture()`, `pictureMedia()`, `pictureMax()` and `pictureMin()` methods.
