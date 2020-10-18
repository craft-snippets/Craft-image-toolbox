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


/**
 * @author    Piotr Pogorzelski
 * @package   ImageToolbox
 * @since     1.0.0
 */
class ImageToolboxVariable
{

    // public function init(){
    //     exit();
    // }

    public $service;
    public function __construct(){
        $this->service = new ImageToolboxService;
    }

    public function picture($image, $transform = [], $attributes = null){
        $sources = [
            [
                'transform' => is_null($transform) ? [] : $transform,
            ]
        ];
        return $this->service->getPicture($image, $sources, $attributes);
    }

    public function pictureMedia($image, $transforms, $common_setings = null, $attributes = null){
        $sources = [];
        foreach ($transforms as $media => $transform) {
            $sources[] = Array(
                'media' => $media,
                'transform' => !is_null($common_setings) ? array_merge($common_setings, $transform) : $transform,
            );
        }
        return $this->service->getPicture($image, $sources, $attributes);
    }

    public function pictureMax($image, $transforms, $common_setings = null, $attributes = null){
        ksort($transforms);
        $sources = [];
        foreach ($transforms as $media => $transform) {
            $sources[] = Array(
                'media' => '(max-width: ' . $media . 'px)',
                'transform' => !is_null($common_setings) ? array_merge($common_setings, $transform) : $transform,
            );
        }
        return $this->service->getPicture($image, $sources, $attributes);
    }

    public function pictureMin($image, $transforms, $common_setings = null, $attributes = null){
        krsort($transforms);
        $sources = [];
        foreach ($transforms as $media => $transform) {
            $sources[] = Array(
                'media' => '(min-width: ' . $media . 'px)',
                'transform' => !is_null($common_setings) ? array_merge($common_setings, $transform) : $transform,
            );
        }
        return $this->service->getPicture($image, $sources, $attributes);
    }

    public function placeholder(array $transform){
        return $this->service->getPlaceholder($transform);
    }

    public function layout($image, $layout_handle){
        return $this->service->getLayout($image, $layout_handle);
    }

}
