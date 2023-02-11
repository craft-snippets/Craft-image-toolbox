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

use Imagine\Gd\Imagine as GdImagine;
use Imagine\Imagick\Imagine as ImagickImagine;


/**
 * @author    Piotr Pogorzelski
 * @package   ImageToolbox
 * @since     1.0.0
 */
class ImageToolboxService extends Component
{

    const PLACEHOLDER_MODE_FILE = 'file';
    const PLACEHOLDER_MODE_URL = 'url';
    const PLACEHOLDER_MODE_SVG = 'svg';

    /**
     * If image can be transformed using Imager.
     *
     * @param Asset $image
     * @return bool
     */
    private function canTransformImager(Asset $image): bool
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
    private function getTransformUrl(Asset $image, array $transformSettings): string
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


    /**
     * Returns URL of placeholder.
     *
     * @param array $transform
     * @return \Twig\Markup
     */
    private function getPlaceholderUrl(?array $transform): \Twig\Markup
    {   
        // if no width or height, empty pixel
        if(is_null($transform) || 
            (
                is_array($transform) && !isset($transform['width']) && !isset($transform['height'])
            ) 
        ){
            // if no width or height only svg placeholder can be used
            $transform = ['width' => 0, 'height' => 0];
            $placeholderUrl = $this->getPlaceholderUrlTypeSvg($transform);
            $placeholderUrl = Template::raw($placeholderUrl);
            return $placeholderUrl;
        }

        // if only width or height provided, create square
        if(!isset($transform['width'])){
            $transform['width'] = $transform['height'];
        }
        if(!isset($transform['height'])){
            $transform['height'] = $transform['width'];
        }

        // select type based on settings
        $placeholderMode = ImageToolbox::$plugin->getSettings()->placeholderMode;
        $placeholderUrl = '';
        if($placeholderMode == self::PLACEHOLDER_MODE_FILE){
            $placeholderUrl = $this->getPlaceholderUrlTypeFile($transform);
        }
        if($placeholderMode == self::PLACEHOLDER_MODE_SVG){
            $placeholderUrl = $this->getPlaceholderUrlTypeSvg($transform);
        }
        if($placeholderMode == self::PLACEHOLDER_MODE_URL){
            $placeholderUrl = $this->getPlaceholderUrlTypeUrl($transform);
        }        

        // return
        $placeholderUrl = Template::raw($placeholderUrl);
        return $placeholderUrl;
    }

    private function getPlaceholderUrlTypeUrl($transform)
    {
        $placeholderUrl = ImageToolbox::$plugin->getSettings()->placeholderUrl;
        $placeholderUrl = str_replace('{width}', $transform['width'], $placeholderUrl);
        $placeholderUrl = str_replace('{height}', $transform['height'], $placeholderUrl); 
        return $placeholderUrl;       
    }

    private function getPlaceholderUrlTypeSvg($transform)
    {
        $string = 'data:image/svg+xml;charset=utf-8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="'.$transform['width'].'" height="'.$transform['height'].'"/>');
        return $string;
    }

    /**
     * Returns markup of placeholder img tag to be returned into template.
     *
     * @param array|null $transform
     * @return \Twig\Markup
     */
    public function getPlaceholder(?array $transform): \Twig\Markup
    {
        $src = $this->getPlaceholderUrl($transform);
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
    public function getPicture(?Asset $image = null, array $sources = [], ?array $attributes): ?\Twig\Markup
    {

        if(ImageToolbox::$plugin->getSettings()->forcePlaceholders == true || (is_null($image) && ImageToolbox::$plugin->getSettings()->usePlaceholders)){
            // placeholder
            $html = $this->getPlaceholderSourcesMarkup($sources, $attributes);
        }elseif(!is_null($image)){

            // if using non image asset
            if($image->kind != 'image'){
                return $this->throwException('Asset must be image.', null);
            }
            
            // picture element
            $html = $this->getSourcesMarkup($image, $sources, $attributes);
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
    private function serverSupportsWebp(): bool
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
    private function canAddWebpSource(Asset $image, array $transform): bool
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
        if(ImageToolbox::$plugin->getSettings()->useWebp && $this->serverSupportsWebp() && $image->getMimeType() != 'image/svg+xml'){
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
    private function getSourcesMarkup(Asset $image, array $sources = [], $attributes): \Twig\Markup
    {

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
                    'srcset' => $this->getPlaceholderUrl(null),
                ]);                     
            }
        }

        // fallback - first transform
        $fallback_transform = reset($sources)['transform'];

        if(!is_null($fallback_transform)){
            $fallback_src = $this->getTransformUrl($image, $fallback_transform);
         }else{
            $fallback_src = $this->getPlaceholderUrl(null);
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
    private function getPlaceholderSourcesMarkup(array $sources = [], ?array $attributes): \Twig\Markup
    {

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
    public function getLayout(?Asset $image, $layout_handle): ?\Twig\Markup
    {

        if(!isset(ImageToolbox::$plugin->getSettings()->transformLayouts[$layout_handle])){
            return $this->throwException(sprintf('Transform layout with handle "%s" is not defined in settings.', $layout_handle), null);
        }

        $layout = ImageToolbox::$plugin->getSettings()->transformLayouts[$layout_handle];

        if(!isset($layout['variants'])){
            return $this->throwException(sprintf('Transform layout with handle "%s" does not have "variants" property defined.', $layout_handle), null);
        }

        foreach ($layout['variants'] as $single_variant) {
            if(!isset($single_variant['transform'])){
                return $this->throwException(sprintf('Transform layout with handle "%s" - one of variants does not have transform defined.', $layout_handle), null);
            }
        }

        return $this->getPicture($image, $layout['variants'], $layout['attributes'] ?? null);

    }

    public function getPictureSources(array $sources, array $htmlAttributes): ?\Twig\Markup
    {
        $htmlString = '';

        // if no sources
        if(empty($sources)){
            return null;
        }

        // force placeholders
        if(ImageToolbox::$plugin->getSettings()->forcePlaceholders == true){
            foreach ($sources as $key => $singleSourceChange) {
                $singleSourceChange['asset'] = null;
                $sources[$key] = $singleSourceChange;
            }
        }


        foreach ($sources as $singleSource) {

            // if asset not defined, it is set to null so placeholder shows up
            if(!isset($singleSource['asset'])){
                $singleSource['asset'] = null;
            }

            // if asset is missing and we dont use placeholders, dont show this source at all
            if(is_null($singleSource['asset']) && ImageToolbox::$plugin->getSettings()->usePlaceholders == false){
                continue;
            }

            // if using non image asset
            if(!is_null($singleSource['asset']) && $singleSource['asset']->kind != 'image'){
                return $this->throwException('One of the assets passed to getImage() function is not an image.', null);
            }

            // if transform not set, use image without transform
            if(!isset($singleSource['transform'])){
                $singleSource['transform'] = [];
            }

            // media breakpoint
            $mediaBreakpoint = null;
            if(isset($singleSource['media'])){
                $mediaBreakpoint = $singleSource['media'];
            }
            if(isset($singleSource['max'])){
                $mediaBreakpoint = '(max-width: ' . $singleSource['max'] . 'px)';
            }
            if(isset($singleSource['min'])){
                $mediaBreakpoint = '(min-width: ' . $singleSource['min'] . 'px)';
            }

            // if transform set to null, show transparent pixel
            if(is_null($singleSource['transform'])){
                $sourceAttributes = [
                    'media' => $mediaBreakpoint,
                    'srcset' => $this->getPlaceholderUrl(null),
                ];                
                $sourcesHtml .= $this->getHtmlTag('source', $sourceAttributes);
            }

            // regular tranform
            if(!is_null($singleSource['transform'])){

                // webp version
                if(!is_null($singleSource['asset']) && $this->canAddWebpSource($singleSource['asset'], $singleSource['transform'])){

                    // force webp
                    $transformWebp = array_merge($singleSource['transform'], ['format' => 'webp']);

                    // transform asset or show placeholder
                    $srcsetWebp = $this->getPlaceholderOrTransform($singleSource['asset'], $transformWebp);

                    // html attributes for source
                    $webpSourceAttributes = [
                        'media' => $mediaBreakpoint,
                        'srcset' => $srcsetWebp,
                    ];

                    // mime type
                    if(!is_null($singleSource['asset'])){
                        $webpSourceAttributes['type'] = 'image/webp';
                    }

                    $htmlString .= $this->getHtmlTag('source', $webpSourceAttributes);
                }

                // regular version

                // transform asset or show placeholder
                $srcsetRegular = $this->getPlaceholderOrTransform($singleSource['asset'], $singleSource['transform']);
                $sourceAttributes = [
                    'media' => $mediaBreakpoint,
                    'srcset' => $srcsetRegular,
                ];
                
                // mime type
                if(!is_null($singleSource['asset'])){
                    if(isset($singleSource['transform']['format'])){
                        $sourceAttributes['type'] = 'image/'.$singleSource['transform']['format'];
                    }else{
                        $sourceAttributes['type'] = $singleSource['asset']->getMimeType();
                    }
                }

                $htmlString .= $this->getHtmlTag('source', $sourceAttributes);
            }
        }

        // fallback - first source
        $fallbackSource = reset($sources);
        $fallbackTransform = $fallbackSource['transform'] ?? [];
        $fallbackAsset = $fallbackSource['asset'] ?? null;
        $fallbackSrc = $this->getPlaceholderOrTransform($fallbackAsset, $fallbackTransform);

        $fallBackHtmlAttributes = [
            'src' => $fallbackSrc,
        ];
        $fallBackHtmlAttributes = array_merge($fallBackHtmlAttributes, $htmlAttributes);
        $htmlString .= $this->getHtmlTag('img', $fallBackHtmlAttributes);

        // wrap in picture tag
        $pictureTag = Html::tag('picture', $htmlString);

        // return
        $rawHtml = Template::raw($pictureTag);
        return $rawHtml;
    }

    // only used in getImage() function
    private function getPlaceholderOrTransform($asset, $transform)
    {
        if(!is_null($asset)){
            $srcset = $this->getTransformUrl($asset, $transform);
        }else{
            $srcset = $this->getPlaceholderUrl($transform);
        }
        return $srcset;
    }   

    // only used in getImage() function
    private function getHtmlTag(string $tag, array $attributes): string
    {
        $html = "\n";
        $html .= Html::tag($tag, '', $attributes);
        return $html;
    }

    const PLACEHOLDER_DEFAULT_FILE = 'default-placeholder.png';
    const PLACEHOLDER_DEFAULT_BACKGROUND = '#d7d7d7';
    const PLACEHOLDER_DEFAULT_OPACITY = 100;

    private function getPlaceholderUrlTypeFile($transform)
    {

        // source file path
        if(!is_null(ImageToolbox::$plugin->getSettings()->filePlaceholderPath)){
            $placeholderSourcePath = Craft::getAlias('@root') . DIRECTORY_SEPARATOR . ImageToolbox::$plugin->getSettings()->filePlaceholderPath;
        }else{
            $placeholderSourcePath = Craft::getAlias('@craftsnippets/imagetoolbox') . DIRECTORY_SEPARATOR . self::PLACEHOLDER_DEFAULT_FILE;            
        }

        // if source image does not exist
        // checked first, so even if there is no need to generate new placeholder file, we are still informed that source file is missing and no suprised later when we try to gen new placeholder size
        if(!file_exists($placeholderSourcePath)){
            return $this->throwException('Placeholder file source "' . $placeholderSourcePath .  '" does not exist', '');
        }

        // placeholder directory
        $placeholderDirectory = ImageToolbox::$plugin->getSettings()->filePlaceholderDirectory;
        $placeholderDirectoryPath = Craft::getAlias('@webroot') . '/' . $placeholderDirectory;
        if(!file_exists($placeholderDirectoryPath)){
            \craft\helpers\FileHelper::createDirectory($placeholderDirectoryPath);
        }
        
        // return file if exists
        $filename = $transform['width'] . 'x' . $transform['height'];
        $placeholderFilePath = $placeholderDirectoryPath . DIRECTORY_SEPARATOR . $filename . '.png';
        $placeholderUrl = Craft::getAlias('@web') . '/' . $placeholderDirectory . '/' . $filename . '.png';
        if(file_exists($placeholderFilePath)){
            return $placeholderUrl;
        }

        // placeholder background-color and opacity
        $backgroundColor = !is_null(ImageToolbox::$plugin->getSettings()->filePlaceholderBackground) ? ImageToolbox::$plugin->getSettings()->filePlaceholderBackground : self::PLACEHOLDER_DEFAULT_BACKGROUND;
        $backgroundOpacity = !is_null(ImageToolbox::$plugin->getSettings()->filePlaceholderBackgroundOpacity) ? ImageToolbox::$plugin->getSettings()->filePlaceholderBackgroundOpacity : self::PLACEHOLDER_DEFAULT_OPACITY;

        // image object
        $images = Craft::$app->getImages();
        $rasterObj = $images->loadImage($placeholderSourcePath, true);
        $rasterObj->scaleToFit($transform['width'], $transform['height'], false); // craft function
        $imageInstance = $rasterObj->getImagineImage(); // imagine function

        // position
        $size = new \Imagine\Image\Box($transform['width'], $transform['height']);
        $pointX = (int)floor(($transform['width'] - $imageInstance->getSize()->getWidth()) / 2);
        $pointY = (int)floor(($transform['height'] - $imageInstance->getSize()->getHeight()) / 2);
        $position = new \Imagine\Image\Point($pointX, $pointY);

        // create background image
        $palette = new \Imagine\Image\Palette\RGB();
        $color = $palette->color($backgroundColor, $backgroundOpacity);
        $imagineInstance = $this->getImagineInstance();
        $backgroundImage = $imagineInstance->create($size, $color);

        // add source image to background
        $backgroundImage->paste($imageInstance, $position);

        // save image
        $backgroundImage->save($placeholderFilePath);
        return $placeholderUrl;
    }

    private $_imagineInstance;

    private function getImagineInstance()
    {

        if($this->_imagineInstance != null){
            return $this->_imagineInstance;
        }

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $extension = strtolower($generalConfig->imageDriver);

        // If it's explicitly set, take their word for it.
        if ($extension === 'gd') {
            $nstance = new GdImagine();
        } else {
            if ($extension === 'imagick') {
                $instance = new ImagickImagine();
            } else {
                // Let's try to auto-detect.
                if (Craft::$app->getImages()->getIsGd()) {
                    $instance = new GdImagine();
                } else {
                    $instance = new ImagickImagine();
                }
            }
        }
        $this->_imagineInstance = $instance;
        return $instance;
    }

    private function throwException($text, $return)
    {
        if(ImageToolbox::$plugin->getSettings()->suppressExceptions == true){
            return $return;
        }else{
            throw new RuntimeError($text);
        }  
    }

}
