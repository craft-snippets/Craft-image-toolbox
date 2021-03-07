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


    public function picture(?Asset $image, array $transform = [], array $attributes = null): ?\Twig\Markup
    {
        $sources = [
            [
                'transform' => is_null($transform) ? [] : $transform,
            ]
        ];
        return ImageToolboxService::getPicture($image, $sources, $attributes);
    }

    public function pictureMedia(?Asset $image, array $transforms, array $common_setings = null, array $attributes = null): ?\Twig\Markup
    {
        $sources = [];
        foreach ($transforms as $media => $transform) {
            $sources[] = Array(
                'media' => $media,
                'transform' => !is_null($common_setings) ? array_merge($common_setings, $transform) : $transform,
            );
        }
        return ImageToolboxService::getPicture($image, $sources, $attributes);
    }

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
        return ImageToolboxService::getPicture($image, $sources, $attributes);
    }

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
        return ImageToolboxService::getPicture($image, $sources, $attributes);
    }

    public function placeholder(?array $transform = null): \Twig\Markup
    {
        return ImageToolboxService::getPlaceholder($transform);
    }

    public function layout(?Asset $image, string $layout_handle): ?\Twig\Markup
    {
        return ImageToolboxService::getLayout($image, $layout_handle);
    }

}
