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
    public function picture(?Asset $image, array $transform = [], array $attributes = null): ?\Twig\Markup
    {
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
    public function placeholder(?array $transform = null): \Twig\Markup
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
    public function layout(?Asset $image, string $layout_handle): ?\Twig\Markup
    {
        return ImageToolbox::getInstance()->imageToolbox->getLayout($image, $layout_handle);
    }

    public function pictureSources(array $sources, array $htmlAttributes = []): ?\Twig\Markup
    {
        return ImageToolbox::getInstance()->imageToolbox->getPictureSources($sources, $htmlAttributes);
    }

}
