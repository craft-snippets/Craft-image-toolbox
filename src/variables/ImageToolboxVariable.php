<?php
/**
 * Image toolbox plugin for Craft CMS 3.x
 *
 * Image toolbox
 *
 * @link      http://craftsnippets.com/
 * @copyright Copyright (c) 2020 Piotr Pogorzelski
 */

namespace craftsnippets\imagetoolbox\variables;

use craftsnippets\imagetoolbox\ImageToolbox;

use Craft;

use craftsnippets\imagetoolbox\services\ImageToolboxService;
use craft\elements\Asset;

/**
 * @author    Piotr Pogorzelski
 * @package   ImageToolbox
 * @since     1.0.0
 */
class ImageToolboxVariable
{


    /**
     * Generate picture element from image.
     *
     * @param Asset|null $image
     * @param array $transform
     * @param array|null $attributes
     * @return \Twig\Markup|null
     */
    public function picture(?Asset $image, array|string $transform = [], array $attributes = null): ?\Twig\Markup
    {
        Craft::$app->getDeprecator()->log('image-toolbox.picture', 'The `picture()` method of Image toolbox plugin is deprecated. Use pictureMultiple() instead. Please note that pictureMultiple() uses new syntax of the parameters - check the documentation.');
        $sources = [
            [
                'transform' => $transform,
            ]
        ];
        return ImageToolbox::getInstance()->imageToolbox->getPicture($image, $sources, $attributes);
    }

    /**
     * Generate picture element with multiple breakpoints from image.
     * Breakpoints are explicitly set by media query.
     *
     * @param Asset|null $image
     * @param array $transforms
     * @param array|null $common_setings
     * @param array|null $attributes
     * @return \Twig\Markup|null
     */
    public function pictureMedia(?Asset $image, array $transforms, array $common_setings = null, array $attributes = null): ?\Twig\Markup
    {
        Craft::$app->getDeprecator()->log('image-toolbox.pictureMedia', 'The `pictureMedia()` method of Image toolbox plugin is deprecated. Use pictureMultiple() instead. Please note that pictureMultiple() uses new syntax of the parameters - check the documentation.');
        $sources = [];
        foreach ($transforms as $media => $transform) {
            $sources[] = Array(
                'media' => $media,
                'transform' => !is_null($common_setings) ? array_merge($common_setings, $transform) : $transform,
            );
        }
        return ImageToolbox::getInstance()->imageToolbox->getPicture($image, $sources, $attributes);
    }

    /**
     * Generate picture element with multiple breakpoints from image.
     * Breakpoints are set by max-width value generated from number.
     *
     * @param Asset|null $image
     * @param array $transforms
     * @param array|null $common_setings
     * @param array|null $attributes
     * @return \Twig\Markup|null
     */
    public function pictureMax(?Asset $image, array $transforms, array $common_setings = null, array $attributes = null): ?\Twig\Markup
    {
        Craft::$app->getDeprecator()->log('image-toolbox.pictureMax', 'The `pictureMax()` method of Image toolbox plugin is deprecated. Use pictureMultiple() instead. Please note that pictureMultiple() uses new syntax of the parameters - check the documentation.');
        ksort($transforms);
        $sources = [];
        foreach ($transforms as $media => $transform) {
            $sources[] = Array(
                'media' => '(max-width: ' . $media . 'px)',
                'transform' => !is_null($common_setings) ? array_merge($common_setings, $transform) : $transform,
            );
        }
        return ImageToolbox::getInstance()->imageToolbox->getPicture($image, $sources, $attributes);
    }

    /**
     * Generate picture element with multiple breakpoints from image.
     * Breakpoints are set by min-width value generated from number.
     *
     * @param Asset|null $image
     * @param array $transforms
     * @param array|null $common_setings
     * @param array|null $attributes
     * @return \Twig\Markup|null
     */
    public function pictureMin(?Asset $image, array $transforms, array $common_setings = null, array $attributes = null): ?\Twig\Markup
    {
        Craft::$app->getDeprecator()->log('image-toolbox.pictureMin', 'The `pictureMin()` method of Image toolbox plugin is deprecated. Use pictureMultiple() instead. Please note that pictureMultiple() uses new syntax of the parameters - check the documentation.');
        krsort($transforms);
        $sources = [];
        foreach ($transforms as $media => $transform) {
            $sources[] = Array(
                'media' => '(min-width: ' . $media . 'px)',
                'transform' => !is_null($common_setings) ? array_merge($common_setings, $transform) : $transform,
            );
        }
        return ImageToolbox::getInstance()->imageToolbox->getPicture($image, $sources, $attributes);
    }

    /**
     * Generate placeholder image.
     *
     * @param array|null $transform
     * @return \Twig\Markup
     */
    public function placeholder(string|array|null $transform = null): \Twig\Markup
    {
        return ImageToolbox::getInstance()->imageToolbox->getPlaceholder($transform);
    }

    /**
     * Generate picture element with multiple breakpoints from image, using transform layout set in plugin config.
     *
     * @param Asset|null $image
     * @param string $layout_handle
     * @return \Twig\Markup|null
     * @throws \Twig\Error\RuntimeError
     */
    public function layout(object|array|null $image, string $layout_handle): ?\Twig\Markup
    {
        return ImageToolbox::getInstance()->imageToolbox->getLayout($image, $layout_handle);
    }

    public function pictureMultiple(array $sources, array $htmlAttributes = [], array $commonTransformSettings = []): ?\Twig\Markup
    {
        // apply common settings to sources transforms
        if(!empty($commonTransformSettings)){
            foreach ($sources as $index => $source) {
                $sources[$index]['transform'] = array_merge(($sources[$index]['transform'] ?? []), $commonTransformSettings);
            }
        }

        return ImageToolbox::getInstance()->imageToolbox->getPictureMultiple($sources, $htmlAttributes);
    }

    public function pictureFromAsset(?Asset $image, ?string $variantsFieldHandle = null, array $htmlAttributes = []): ?\Twig\Markup
    {
        if(!ImageToolbox::getInstance()->isProEdition()){
            throw new \Exception('The functionality of defining picture element configurations in the control panel requires the PRO edition of the plugin.');
        }
        return ImageToolbox::getInstance()->imageToolbox->getPictureFromAsset($image, $variantsFieldHandle, $htmlAttributes);
    }

}
