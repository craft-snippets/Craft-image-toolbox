# Image toolbox plugin for Craft CMS 5.x

Image Toolbox is Craft CMS plugin that helps with the use of image transforms in templates.

## Features

* Automatic creation of **avif** and **webp** variant of the images, with fallback for browsers that don't support these formats.
* Automatic creation of placeholder images. Plugin outputs either transformed image or placeholder with size based on image transform, if image is missing.
* Generating responsive images with multiple variants, displayed on specific breakpoints by using `<picture>` element.
* [Imager-x](https://plugins.craftcms.com/imager-x) (or old [Imager](https://plugins.craftcms.com/imager)) support - but you can use it with native Craft image transforms as well.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require craftsnippets/craft-image-toolbox

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Image toolbox.

## Documentation

Click here for [Image toolbox documentation](http://craftsnippets.com/docs/image-toolbox)

## Roadmap

* Retina support
* Applying transform on assets within HTML strings
