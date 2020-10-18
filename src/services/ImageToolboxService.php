<?php
/**
 * Image toolbox plugin for Craft CMS 3.x
 *
 * Image toolbox
 *
 * @link      http://craftsnippets.com/
 * @copyright Copyright (c) 2020 Piotr Pogorzelski
 */

namespace craftsnippets\imagetoolbox\services;

use craftsnippets\imagetoolbox\ImageToolbox;

use Craft;
use craft\base\Component;

use craft\helpers\Template;
use craft\helpers\Html;
use Twig\Error\RuntimeError;



/**
 * @author    Piotr Pogorzelski
 * @package   ImageToolbox
 * @since     1.0.0
 */
class ImageToolboxService extends Component
{


    private $settings;

    public function init(){
        $this->settings = ImageToolbox::$plugin->getSettings();
        parent::init();
    }

    private function canTransformImager($image){
        // imager has problems with svg
        if($image->getMimeType() == 'image/svg+xml' && $this->settings->useImagerForSvg == false){
            return false;
        }
        if($this->settings->useImager == false){
            return false;
        }
        return true;
    }

    private function getTransformUrl($image, $transformSettings){
        
        // imager settings kept in transform settings
        $imager_settings = [];
        if(isset($transformSettings['filenamePattern'])){
            $imager_settings['filenamePattern'] = $transformSettings['filenamePattern'];
        }

        // remove not-standard settings
        unset($transformSettings['useWebp']);
        unset($transformSettings['filenamePattern']);

        // choose transform method
        if(!empty($transformSettings)){
            if(Craft::$app->getPlugins()->isPluginEnabled('imager') && $this->canTransformImager($image)){
                $url = \aelvan\imager\Imager::$plugin->imager->transformImage($image, $transformSettings, [], $imager_settings);
            }elseif(Craft::$app->getPlugins()->isPluginEnabled('imager-x') && $this->canTransformImager($image)){
                $url = \spacecatninja\imagerx\Imagerx::$plugin->imagerx->transformImage($image, $transformSettings, [], $imager_settings);
            }else{
                $url = $image->getUrl($transformSettings);
            }
        // if no transform settings, show image directly without transform
        }else{
            $url = $image->url;
        }
        return $url;
    }


    public function getPlaceholderUrl($transform)
    {   

        if(isset($transform['width']) || isset($transform['height'])){

            // if only width or height provided, create square
            if(!isset($transform['width'])){
                $transform['width'] = $transform['height'];
            }
            if(!isset($transform['height'])){
                $transform['height'] = $transform['width'];
            }        

            $placeholder_url = $this->settings->placeholderUrl;
            if(is_string($placeholder_url) && !empty($placeholder_url)){
                $placeholder_url = str_replace('{width}', $transform['width'], $placeholder_url);
                $placeholder_url = str_replace('{height}', $transform['height'], $placeholder_url);
            }else{
                $placeholder_url = 'data:image/svg+xml;charset=utf-8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="'.$transform['width'].'" height="'.$transform['height'].'"/>');
            }

            return Template::raw($placeholder_url);
        }
    }

    public function getPlaceholder($transform){
        $src = $this->getPlaceholderUrl($transform);
        $html = Html::tag('img', '', [
                        'src' => $src,
                        'class' => $this->settings->placeholderClass,
        ]);
        return Template::raw($html); 
    }



    public function getPicture($image = null, $sources = [], $attributes)
    {

        if(is_null($image) && $this->settings->usePlaceholders){
            // placeholder
            $html = $this->getPlaceholderSourcesMarkup($sources, $attributes);
        }elseif(!is_null($image)){
            // picture element
            $html = $this->getSourcesMarkup($image, $sources, $attributes);
        }

        // return markup
        if(!empty($html)){
            $picture = Html::tag('picture', $html); 
            return Template::raw($picture);
        }

    }

    protected function canAddWebpSource($image, $transform){

        // if image is webp already and we dont want to transform it into other format
        if($image->getMimeType() == 'image/webp' && !isset($transform['format'])){
            return false;
        }

        // if we already want webp transform - no need for two webp variants
        if(isset($transform['format']) && $transform['format'] == 'webp'){
            return false;
        }

        // if we explictly state in trsnaform settings that we dont want to transform it
        if(isset($transform['useWebp']) && $transform['useWebp'] == false){
            return false;
        }

        // if global settings allow it, server supports webp and iamge is not svg 
        if($this->settings->useWebp && Craft::$app->getImages()->getSupportsWebP() && $image->getMimeType() != 'image/svg+xml'){
            return true;
        }

        return false;
    }


    protected function getSourcesMarkup($image, $sources = [], $attributes){

        $html_string = '';

        foreach($sources as $source){

            // if we dont want source empty
            if(!is_null($source['transform'])){
                // webp version
                if($this->canAddWebpSource($image, $source['transform'])){
                    $settings_webp = array_merge($source['transform'], ['format' => 'webp']);
                    $html_string .= "\n";
                    $html_string .= Html::tag('source', '', [
                        'media' => $source['media'] ?? null,
                        'srcset' => $this->getTransformUrl($image, $settings_webp),
                        'type' => 'image/webp',
                    ]);
                }

                // regular version
                $html_string .= "\n";
                $html_string .= Html::tag('source', '', [
                    'media' => $source['media'] ?? null,
                    'srcset' => $this->getTransformUrl($image, $source['transform']),
                    'type' => isset($source['transform']['format']) ? 'image/'.$source['transform']['format'] : $image->getMimeType(),
                ]); 
            // if empty source
            }else{
                $html_string .= "\n";
                $html_string .= Html::tag('source', '', [
                    'media' => $source['media'] ?? null,
                    'srcset' => $this->getPlaceholderUrl(['width' => 0, 'height' => 0]),
                ]);                     
            }
        }

        // fallback - first transform
        $fallback_transform = reset($sources)['transform'];

        if(!is_null($fallback_transform)){
            $fallback_src = $this->getTransformUrl($image, $fallback_transform);
         }else{
            $fallback_src = $this->getPlaceholderUrl(['width' => 0, 'height' => 0]);
         }

        $fallback_attributes = [
            'src' => $fallback_src,
        ];

        // add provided attributes
        if(!is_null($attributes)){
            $fallback_attributes = array_merge($fallback_attributes, $attributes);
        }
        $html_string .= "\n";
        $html_string .= Html::tag('img', '', $fallback_attributes); 
        $html_string .= "\n";

        return $html_string;

    }


    protected function getPlaceholderSourcesMarkup($sources = [], $attributes){

            $html_string = '';

            // sources
            foreach($sources as $source){
                $html_string .= "\n";
                $html_string .= Html::tag('source', '', [
                    'media' => $source['media'] ?? null,
                    'srcset' => $this->getPlaceholderUrl($source['transform']),
                ]);
            }

            // fallback - first transform
            $fallback_transform = reset($sources)['transform'];

            // add provided attributes
            $fallback_attributes = [
                'srcset' => $this->getPlaceholderUrl($fallback_transform),
            ];
            if(!is_null($attributes)){
                $fallback_attributes = array_merge($fallback_attributes, $attributes);
            }
            // add placeholder class
            $placeholder_class = $this->settings->placeholderClass;
            if(isset($fallback_attributes['class'])){
                if(is_array($fallback_attributes['class'])){
                    $fallback_attributes['class'][] = $placeholder_class;
                }elseif(is_string($fallback_attributes['class'])){
                    $fallback_attributes['class'] = [$fallback_attributes['class'], $placeholder_class];
                }
            }else{
                $fallback_attributes['class'] = $placeholder_class;
            }

            $html_string .= "\n";
            $html_string .= Html::tag('img', '', $fallback_attributes);
            $html_string .= "\n";

            return Template::raw($html_string);
    }

    public function getLayout($image, $layout_handle){

        if(!isset($this->settings->transformLayouts[$layout_handle])){
            throw new RuntimeError(sprintf('Transform layout with handle "%s" is not defined in settings.', $layout_handle));
        }

        $layout = $this->settings->transformLayouts[$layout_handle];

        if(!isset($layout['variants'])){
            throw new RuntimeError(sprintf('Transform layout with handle "%s" does not have "variants" property defined.', $layout_handle));
        }

        foreach ($layout['variants'] as $single_variant) {
            if(!isset($single_variant['transform'])){
                throw new RuntimeError(sprintf('Transform layout with handle "%s" - one of variants does not have transform defined.', $layout_handle));
            }
        }

        return $this->getPicture($image, $layout['variants'], $layout['attributes'] ?? null);

    }

}
