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

use craft\elements\Asset;


/**
 * @author    Piotr Pogorzelski
 * @package   ImageToolbox
 * @since     1.0.0
 */
class ImageToolboxService extends Component
{


    /**
     * If image can be transformed using Imager.
     *
     * @param Asset $image
     * @return bool
     */
    private static function canTransformImager(Asset $image): bool
    {
        // imager has problems with svg
        if($image->getMimeType() == 'image/svg+xml' && ImageToolbox::$plugin->getSettings()->useImagerForSvg == false){
            return false;
        }
        if(ImageToolbox::$plugin->getSettings()->useImager == false){
            return false;
        }
        return true;
    }

    /**
     * Returns URL of transformed image.
     *
     * @param Asset $image
     * @param array $transformSettings
     * @return string
     * @throws \spacecatninja\imagerx\exceptions\ImagerException
     * @throws \yii\base\InvalidConfigException
     */
    private static function getTransformUrl(Asset $image, array $transformSettings): string
    {
        
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
            if(Craft::$app->getPlugins()->isPluginEnabled('imager') && self::canTransformImager($image)){
                $url = \aelvan\imager\Imager::$plugin->imager->transformImage($image, $transformSettings, [], $imager_settings);
            }elseif(Craft::$app->getPlugins()->isPluginEnabled('imager-x') && self::canTransformImager($image)){
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


    /**
     * Returns URL of placeholder.
     *
     * @param array $transform
     * @return \Twig\Markup
     */
    private static function getPlaceholderUrl(array $transform): \Twig\Markup
    {   

        if(isset($transform['width']) || isset($transform['height'])){

            // if only width or height provided, create square
            if(!isset($transform['width'])){
                $transform['width'] = $transform['height'];
            }
            if(!isset($transform['height'])){
                $transform['height'] = $transform['width'];
            }        

            $placeholder_url = ImageToolbox::$plugin->getSettings()->placeholderUrl;
            if(is_string($placeholder_url) && !empty($placeholder_url)){
                $placeholder_url = str_replace('{width}', $transform['width'], $placeholder_url);
                $placeholder_url = str_replace('{height}', $transform['height'], $placeholder_url);
            }else{
                $placeholder_url = 'data:image/svg+xml;charset=utf-8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="'.$transform['width'].'" height="'.$transform['height'].'"/>');
            }
            $return_url = Template::raw($placeholder_url);
            return $return_url;
        }
    }

    /**
     * Returns markup of placeholder img tag to be returned into template.
     *
     * @param array|null $transform
     * @return \Twig\Markup
     */
    public static function getPlaceholder(?array $transform): \Twig\Markup
    {

        if($transform == null){
            $transform = ['width' => 0, 'height' => 0];
        }

        $src = self::getPlaceholderUrl($transform);
        $html = Html::tag('img', '', [
                        'src' => $src,
                        'class' => ImageToolbox::$plugin->getSettings()->placeholderClass,
        ]);
        return Template::raw($html); 
    }


    /**
     * Returns whole markup of picture to be outputted into template.
     *
     * @param Asset|null $image
     * @param array $sources
     * @param array|null $attributes
     * @return \Twig\Markup|null
     */
    public static function getPicture(?Asset $image = null, array $sources = [], ?array $attributes): ?\Twig\Markup
    {

        // if using non image asset
        if($image->kind != 'image'){
            throw new RuntimeError('Asset must be image.');
        }

        if(is_null($image) && ImageToolbox::$plugin->getSettings()->usePlaceholders){
            // placeholder
            $html = self::getPlaceholderSourcesMarkup($sources, $attributes);
        }elseif(!is_null($image)){
            // picture element
            $html = self::getSourcesMarkup($image, $sources, $attributes);
        }

        // return markup
        if(!empty($html)){
            $picture = Html::tag('picture', $html);
            $raw_html = Template::raw($picture);
            return $raw_html;
        }else{
            return null;
        }

    }

    /**
     * Checks of server webp support.
     *
     * @return bool
     */
    private static function serverSupportsWebp(): bool
    {
        return Craft::$app->getImages()->getSupportsWebP() || ImageToolbox::$plugin->getSettings()->forceWebp;
    }

    /**
     * Checks if webp version of image can be generated.
     *
     * @param Asset $image
     * @param array $transform
     * @return bool
     */
    private static function canAddWebpSource(Asset $image, array $transform): bool
    {

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
        if(ImageToolbox::$plugin->getSettings()->useWebp && self::serverSupportsWebp() && $image->getMimeType() != 'image/svg+xml'){
            return true;
        }

        return false;
    }


    /**
     * Returns markup of picture sources.
     *
     * @param Asset $image
     * @param array $sources
     * @param $attributes
     * @return \Twig\Markup
     * @throws \spacecatninja\imagerx\exceptions\ImagerException
     * @throws \yii\base\InvalidConfigException
     */
    private static function getSourcesMarkup(Asset $image, array $sources = [], $attributes): \Twig\Markup
    {

        $html_string = '';

        foreach($sources as $source){

            // if we dont want source empty
            if(!is_null($source['transform'])){
                // webp version
                if(self::canAddWebpSource($image, $source['transform'])){
                    $settings_webp = array_merge($source['transform'], ['format' => 'webp']);
                    $html_string .= "\n";
                    $html_string .= Html::tag('source', '', [
                        'media' => $source['media'] ?? null,
                        'srcset' => self::getTransformUrl($image, $settings_webp),
                        'type' => 'image/webp',
                    ]);
                }

                // regular version
                $html_string .= "\n";
                $html_string .= Html::tag('source', '', [
                    'media' => $source['media'] ?? null,
                    'srcset' => self::getTransformUrl($image, $source['transform']),
                    'type' => isset($source['transform']['format']) ? 'image/'.$source['transform']['format'] : $image->getMimeType(),
                ]); 
            // if empty source
            }else{
                $html_string .= "\n";
                $html_string .= Html::tag('source', '', [
                    'media' => $source['media'] ?? null,
                    'srcset' => self::getPlaceholderUrl(['width' => 0, 'height' => 0]),
                ]);                     
            }
        }

        // fallback - first transform
        $fallback_transform = reset($sources)['transform'];

        if(!is_null($fallback_transform)){
            $fallback_src = self::getTransformUrl($image, $fallback_transform);
         }else{
            $fallback_src = self::getPlaceholderUrl(['width' => 0, 'height' => 0]);
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

        $raw_html = Template::raw($html_string);
        return $raw_html;

    }


    /**
     * Returns markup of picture (that is placeholder) sources.
     *
     * @param array $sources
     * @param array|null $attributes
     * @return \Twig\Markup
     */
    private static function getPlaceholderSourcesMarkup(array $sources = [], ?array $attributes): \Twig\Markup
    {

            $html_string = '';

            // sources
            foreach($sources as $source){
                $html_string .= "\n";
                $html_string .= Html::tag('source', '', [
                    'media' => $source['media'] ?? null,
                    'srcset' => self::getPlaceholderUrl($source['transform']),
                ]);
            }

            // fallback - first transform
            $fallback_transform = reset($sources)['transform'];

            // add provided attributes
            $fallback_attributes = [
                'srcset' => self::getPlaceholderUrl($fallback_transform),
            ];
            if(!is_null($attributes)){
                $fallback_attributes = array_merge($fallback_attributes, $attributes);
            }
            // add placeholder class
            $placeholder_class = ImageToolbox::$plugin->getSettings()->placeholderClass;
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

    /**
     * Returns whole markup of picture generated from transform layout to be outputted into template.
     *
     * @param Asset|null $image
     * @param $layout_handle
     * @return \Twig\Markup|null
     * @throws RuntimeError
     */
    public static function getLayout(?Asset $image, $layout_handle): ?\Twig\Markup
    {

        if(!isset(ImageToolbox::$plugin->getSettings()->transformLayouts[$layout_handle])){
            throw new RuntimeError(sprintf('Transform layout with handle "%s" is not defined in settings.', $layout_handle));
        }

        $layout = ImageToolbox::$plugin->getSettings()->transformLayouts[$layout_handle];

        if(!isset($layout['variants'])){
            throw new RuntimeError(sprintf('Transform layout with handle "%s" does not have "variants" property defined.', $layout_handle));
        }

        foreach ($layout['variants'] as $single_variant) {
            if(!isset($single_variant['transform'])){
                throw new RuntimeError(sprintf('Transform layout with handle "%s" - one of variants does not have transform defined.', $layout_handle));
            }
        }

        return self::getPicture($image, $layout['variants'], $layout['attributes'] ?? null);

    }

}
