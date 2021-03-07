# Settings

Place these settings in `config/image-toolbox` file.

* `useWebp` - if webp version of image should automatically be generated. Default: `true`.
* `useImager` - if Imager-x or Imager should be used for transforms (assuming one of these plugins is installed). Default: `true`.
* `usePlaceholders` - if placeholder should be generated if image is missing (asset object equals `null`). Default: `true`.
* `placeholderClass` - CSS class added to `<img>` inside `<picture>` element if placeholder image is displayed. Default: `is-placeholder`.
* `useImagerForSvg` - if imager should be used also for SVG images. Default: `false`.
* `placeholderUrl` - URL of placeholder image. `{width}` and `{height}` in URL will be replaced with width and height of placeholder. If this settings is empty, SVG placeholder will be used.
* `transformLayouts` - pre-defined transform settings. Read more in "Transform layouts" section of documentation.