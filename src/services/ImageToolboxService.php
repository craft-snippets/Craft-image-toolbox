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

use craftsnippets\imagetoolbox\fields\ImageVariantsField;
use craft\helpers\Image as ImageHelper;

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

    const SETTING_NONE = 'none';
    const SETTING_ENABLED = 'enabled';
    const SETTING_DISABLED = 'disabled';

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
        unset($transformSettings['useAvif']);
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
    public function getPlaceholder(array|string|null $transform): \Twig\Markup
    {
        $transformSettings = $this->getTransformSettings($transform);
        $src = $this->getPlaceholderUrl($transformSettings);
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

        // transform array or db transform handle
        $sources = array_map(function($single){
            $single['transform'] = $this->getTransformSettings($single['transform']);
            return $single;
        }, $sources);

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
     * Checks for Avif support.
     *
     * @return bool
     */
    private function serverSupportsAvif(): bool
    {
        return Craft::$app->getImages()->getSupportsAvif() || ImageToolbox::$plugin->getSettings()->forceAvif;
    }

    /**
     * Checks if webp version of image can be generated.
     *
     * @param Asset $image
     * @param array $transform
     * @return bool
     */
    private function canAddWebpSource(Asset $image, array $transform, $overwrite = self::SETTING_NONE): bool
    {

        // if image is webp already and we dont want to transform it into other format
        if($image->getMimeType() == 'image/webp' && !isset($transform['format'])){
            return false;
        }

        // if we already want webp transform - no need for two webp variants
        if(isset($transform['format']) && $transform['format'] == 'webp'){
            return false;
        }

        // if variant field overwrites it
        if($overwrite == self::SETTING_DISABLED){
            return false;
        }

        // if we explicitly state in transform settings that we don't want to transform it
        if(isset($transform['useWebp']) && $transform['useWebp'] == false){
            return false;
        }

        // if global settings allow it, server supports webp and image is not svg
        if(
            (ImageToolbox::$plugin->getSettings()->useWebp || $overwrite == self::SETTING_ENABLED) && 
            $this->serverSupportsWebp() && 
            $image->getMimeType() != 'image/svg+xml'
        ){
            return true;
        }

        return false;
    }

    /**
     * Checks if avif version of image can be generated.
     *
     * @param Asset $image
     * @param array $transform
     * @return bool
     */
    private function canAddAvifSource(Asset $image, array $transform, $overwrite = self::SETTING_NONE): bool
    {

        // if image is avif already and we dont want to transform it into other format
        if($image->getMimeType() == 'image/avif' && !isset($transform['format'])){
            return false;
        }

        // if we already want avif transform - no need for two avif variants
        if(isset($transform['format']) && $transform['format'] == 'avif'){
            return false;
        }

        // if variant field overwrites it
        if($overwrite == self::SETTING_DISABLED){
            return false;
        }

        // if we explicitly state in transform settings that we don't want to transform it
        if(isset($transform['useAvif']) && $transform['useAvif'] == false){
            return false;
        }

        // if global settings allow it, server supports avif and image is not svg
        if(
            (ImageToolbox::$plugin->getSettings()->useAvif || $overwrite == self::SETTING_ENABLED) &&
            $this->serverSupportsAvif() &&
            $image->getMimeType() != 'image/svg+xml'
        ){
            return true;
        }

        return false;
    }


    private static function normalizeDimensions(int|string|null &$width, int|string|null &$height, $assetWidth, $assetHeight): void
    {
        // See if $width is in "XxY" format
        if (preg_match('/^([\d]+|AUTO)x([\d]+|AUTO)/', (string)$width, $matches)) {
            $width = $matches[1] !== 'AUTO' ? (int)$matches[1] : null;
            $height = $matches[2] !== 'AUTO' ? (int)$matches[2] : null;
        }

        if (!$height || !$width) {
            [$width, $height] = ImageHelper::calculateMissingDimension($width, $height, $assetWidth, $assetHeight);
        }
    }

    public static function calculateWidthHeight(Asset $asset, \craft\models\ImageTransform $transform) {

        $scaleIfSmaller = $transform->upscale ?? Craft::$app->getConfig()->getGeneral()->upscaleImages;

        $targetWidth = $transform->width;
        $targetHeight = $transform->height;

        $assetWidth = $asset->width;
        $assetHeight = $asset->height;

        $finalWidth = null;
        $finalHeight = null;

        self::normalizeDimensions($targetWidth, $targetHeight, $assetWidth, $assetHeight);
        switch ($transform->mode) {
            
            case 'fit':
                // $image->scaleToFit($transform->width, $transform->height, $scaleIfSmaller);
                if ($scaleIfSmaller || $assetWidth > $targetWidth || $assetHeight > $targetHeight) {
                    $factor = max($assetWidth / $targetWidth, $assetHeight / $targetHeight);
                    $finalWidth = round($assetWidth / $factor);
                    $finalHeight = round($assetHeight / $factor);

                }else{
                    $finalWidth = $assetWidth;
                    $finalHeight = $assetHeight;                    
                }   
                break;
            case 'stretch':
            case 'letterbox':
                $finalWidth = $targetWidth;
                $finalHeight = $targetHeight;
                break;
            default:
                // $image->scaleAndCrop($transform->width, $transform->height, $scaleIfSmaller, $position);
                if ($scaleIfSmaller || ($assetWidth > $targetWidth && $assetHeight > $targetHeight)) {
                    // can upscale or destination size smaller than asset
                    $finalWidth = $targetWidth;
                    $finalHeight = $targetHeight;    
                } elseif (($targetWidth > $assetWidth || $targetHeight > $assetHeight) && !$scaleIfSmaller) {
                    $factor = max($targetWidth / $assetWidth, $targetHeight / $assetHeight);
                    $finalHeight = round($targetHeight / $factor);
                    $finalWidth = round($targetWidth / $factor);
                } else {
                    $finalWidth = $targetWidth;
                    $finalHeight = $targetHeight;                        
                }
        }

        return [
            'width' => $finalWidth,
            'height' => $finalHeight,
        ];
    }

    private static function getWidthHeightAttrs($asset, $transform, $overwrite = self::SETTING_NONE)
    {   

        // if disabled
        if(
            (ImageToolbox::$plugin->getSettings()->useWidthHeightAttributes == false && $overwrite == self::SETTING_NONE) ||
            $overwrite == self::SETTING_DISABLED
        ){
            return null;
        }

        // if placeholder (Asset is null) 
        if(is_null($asset)){
            // if both height width missing
            if(!isset($transform['width']) && !isset($transform['height'])){
                return [
                    'width' => 0,
                    'height' => 0,
                ];
            }
            //  if only width/height missing, placeholder will be square
            if(isset($transform['width']) && !isset($transform['height'])){
                $transform['height'] = $transform['width'];
            }
            if(!isset($transform['width']) && isset($transform['height'])){
                $transform['width'] = $transform['height'];
            }
            return [
                'width' => $transform['width'],
                'height' => $transform['height'],
            ];
        }

        // if no width height settings in transform, but image exists, get size of image
        if(!isset($transform['width']) && !isset($transform['height']) && !is_null($asset)){
            return [
                'width' => $asset->width,
                'height' => $asset->height,
            ];            
        }

        // remove additional options
        unset($transform['useWebp']);
        unset($transform['useAvif']);
        unset($transform['filenamePattern']);

        // regular transform
        $transformSettings = new \craft\models\ImageTransform($transform);
        return self::calculateWidthHeight($asset, $transformSettings);


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

                // avif version
                if($this->canAddAvifSource($image, $source['transform'])){
                    $settings_avif = array_merge($source['transform'], ['format' => 'avif']);

                    $attrsAvif = [
                        'media' => $source['media'] ?? null,
                        'srcset' => $this->getTransformUrl($image, $settings_avif),
                        'type' => 'image/avif',
                    ];

                    if(!is_null(self::getWidthHeightAttrs($image, $source['transform']))){
                        $attrsAvif['width'] = self::getWidthHeightAttrs($image, $source['transform'])['width'];
                        $attrsAvif['height'] = self::getWidthHeightAttrs($image, $source['transform'])['height'];
                    }

                    $html_string .= "\n";
                    $html_string .= Html::tag('source', '', $attrsAvif);
                }

                // webp version
                if($this->canAddWebpSource($image, $source['transform'])){
                    $settings_webp = array_merge($source['transform'], ['format' => 'webp']);

                    $attrsWebp = [
                        'media' => $source['media'] ?? null,
                        'srcset' => $this->getTransformUrl($image, $settings_webp),
                        'type' => 'image/webp',
                    ];

                    if(!is_null(self::getWidthHeightAttrs($image, $source['transform']))){
                        $attrsWebp['width'] = self::getWidthHeightAttrs($image, $source['transform'])['width'];
                        $attrsWebp['height'] = self::getWidthHeightAttrs($image, $source['transform'])['height'];
                    }

                    $html_string .= "\n";
                    $html_string .= Html::tag('source', '', $attrsWebp);
                }

                // regular version
                $html_string .= "\n";

                $attrsRegular = [
                    'media' => $source['media'] ?? null,
                    'srcset' => $this->getTransformUrl($image, $source['transform']),
                    'type' => isset($source['transform']['format']) ? 'image/'.$source['transform']['format'] : $image->getMimeType(),
                ];

                if(!is_null(self::getWidthHeightAttrs($image, $source['transform']))){
                    $attrsRegular['width'] = self::getWidthHeightAttrs($image, $source['transform'])['width'];
                    $attrsRegular['height'] = self::getWidthHeightAttrs($image, $source['transform'])['height'];
                }

                $html_string .= Html::tag('source', '', $attrsRegular); 
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

        if(!is_null(self::getWidthHeightAttrs($image, $fallback_transform))){
            $fallback_attributes['width'] = self::getWidthHeightAttrs($image, $fallback_transform)['width'];
            $fallback_attributes['height'] = self::getWidthHeightAttrs($image, $fallback_transform)['height'];
        }            

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

                $attrs = [
                    'media' => $source['media'] ?? null,
                    'srcset' => self::getPlaceholderUrl($source['transform']),
                ];

                if(!is_null(self::getWidthHeightAttrs(null, $source['transform']))){
                    $attrs['width'] = self::getWidthHeightAttrs(null, $source['transform'])['width'];
                    $attrs['height'] = self::getWidthHeightAttrs(null, $source['transform'])['height'];
                }

                $html_string .= "\n";
                $html_string .= Html::tag('source', '', $attrs);
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

            if(!is_null(self::getWidthHeightAttrs(null, $fallback_transform))){
                $fallback_attributes['width'] = self::getWidthHeightAttrs(null, $fallback_transform)['width'];
                $fallback_attributes['height'] = self::getWidthHeightAttrs(null, $fallback_transform)['height'];
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
    public function getLayout(object|array|null $images, string $layoutHandle): ?\Twig\Markup
    {

        if(!isset(ImageToolbox::$plugin->getSettings()->transformLayouts[$layoutHandle])){
            return $this->throwException(sprintf('Transform layout with handle "%s" is not defined in settings.', $layoutHandle), null);
        }

        $layout = ImageToolbox::$plugin->getSettings()->transformLayouts[$layoutHandle];

        if(!isset($layout['variants'])){
            return $this->throwException(sprintf('Transform layout with handle "%s" does not have "variants" property defined.', $layoutHandle), null);
        }

        // get images
        if(!is_array($images)){
            $images = [$images];
        }

        // add images to variants
        foreach ($layout['variants'] as $index => $singleVariant) {
            if(isset($images[$index])){
                $layout['variants'][$index]['asset'] = $images[$index];
            // if not enough images for all variants, use the last image
            }else{
                $layout['variants'][$index]['asset'] = end($images);
            }
        }

        // html attributes
        if(!isset($layout['attributes'])){
            $layout['attributes'] = [];
        }

        if(is_callable($layout['attributes'])){
            $layout['attributes'] = call_user_func($layout['attributes'], $images);
        }

        return $this->getPictureMultiple($layout['variants'], $layout['attributes']);

    }

    private function getVariantSettingsFieldFromImage(Asset $image, ?string $fieldHandle)
    {
        $fields = $image->fieldLayout->customFields;
        $variantFields = array_filter($fields, function($single){
            return get_class($single) == ImageVariantsField::class;
        });

        if(!is_null($fieldHandle)){
            $variantFields = array_filter($variantFields, function($single) use($fieldHandle){
                return $single->handle == $fieldHandle;
            });            
        }

        if(!empty($variantFields)){
            $field = reset($variantFields);
            return $field;
        }
        return null;
    }

    private function getSourcesFromSettings($variants, $image)
    {
        $sources = [];
        foreach ($variants as $variant) {
            $source = [
                'asset' => $image,
                // 'transform' => $variant,
                'transform' => $variant['transform'],
            ];
            if($variant['breakpointType'] == 'min'){
                $source['min'] = $variant['min'];
            }
            if($variant['breakpointType'] == 'max'){
                $source['max'] = $variant['max'];
            }
            if($variant['breakpointType'] == 'media'){
                $source['media'] = $variant['media'];
            }                                        
            $sources[] = $source;
        }
        return $sources;
    }

    public function getPictureFromAsset(?Asset $image, ?string $fieldHandle, array $htmlAttributes)
    {
        // if no asset and no settings field defined, return nothing
        if(is_null($image) && is_null($fieldHandle)){
            return null;
        }

        // get variants field
        if(!is_null($image)){
            $variantField = $this->getVariantSettingsFieldFromImage($image, $fieldHandle);
        }else{
            $variantField = Craft::$app->getFields()->getFieldByHandle($fieldHandle);
            if(!is_null($variantField) && get_class($variantField) != ImageVariantsField::class){
                $variantField = null;
            }
        }

        // create sources
        if(is_null($variantField)){
            // no field, dont apply transform
            $sources = [
                [
                    'asset' => $image,
                ]
            ];
        }else{
            // get from specific asset
            if(!is_null($image) && !empty($image->getFieldValue($variantField->handle)['variants'])){
                $sources = $this->getSourcesFromSettings($image->getFieldValue($variantField->handle)['variants'], $image);
            // get from field settings
            }elseif(!empty($variantField->variants)){
                $sources = $this->getSourcesFromSettings($variantField->variants, $image);
            // no variant defined, dont apply transform
            }else{
                $sources = [
                    [
                        'asset' => $image,
                    ]
                ];
            }
        }

        // if use webp
        $useWebp = self::SETTING_NONE;
        if(!is_null($variantField)){
            // field settings
            $useWebp = $variantField->useWebp;
            // specific asset
            if(!is_null($image) && $image->getFieldValue($variantField->handle)['useWebp'] != self::SETTING_NONE){
                $useWebp = $image->getFieldValue($variantField->handle)['useWebp'];
            }
        }

        // if use avif
        $useAvif = self::SETTING_NONE;
        if(!is_null($variantField)){
            // field settings
            $useAvif = $variantField->useAvif;
            // specific asset
            if(!is_null($image) && $image->getFieldValue($variantField->handle)['useAvif'] != self::SETTING_NONE){
                $useWebp = $image->getFieldValue($variantField->handle)['useAvif'];
            }
        }

        // if use width height
        $useWidthHeight = self::SETTING_NONE;
        if(!is_null($variantField)){
            // field settings
            $useWidthHeight = $variantField->useWidthHeight;
            // specific asset
            if(!is_null($image) && $image->getFieldValue($variantField->handle)['useWidthHeight'] != self::SETTING_NONE){
                $useWidthHeight = $image->getFieldValue($variantField->handle)['useWidthHeight'];
            }            
        }

        // return
        $html = $this->getPictureMultiple($sources, $htmlAttributes, $useWebp, $useAvif, $useWidthHeight);
        return $html;
    }

    public function getPictureMultiple(array $sources, array $htmlAttributes, $settingWebp = self::SETTING_NONE, $settingAvif = self::SETTING_NONE, $settingWidthHeight = self::SETTING_NONE): ?\Twig\Markup
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

        
        $sources = array_map(function($single){
            // make sure transform array exists
            $single['transform'] = $single['transform'] ?? [];
            // db based transforms
            $single['transform'] = $this->getTransformSettings($single['transform']);
            return $single;
        }, $sources);


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

            // source markup
            $transfromSettings = $singleSource['transform'];

            // avif version
            if(
                !is_null($singleSource['asset']) &&
                $this->canAddAvifSource($singleSource['asset'], $transfromSettings, $settingAvif)
            ){

                // force avif
                $transformAvif = array_merge($transfromSettings, ['format' => 'avif']);

                // transform asset or show placeholder
                $srcsetAvif = $this->getPlaceholderOrTransform($singleSource['asset'], $transformAvif);

                // html attributes for source
                $avifSourceAttributes = [
                    'media' => $mediaBreakpoint,
                    'srcset' => $srcsetAvif,
                ];

                // mime type
                if(!is_null($singleSource['asset'])){
                    $avifSourceAttributes['type'] = 'image/avif';
                }

                // width height
                $avifWidthHeight = self::getWidthHeightAttrs($singleSource['asset'], $transfromSettings, $settingWidthHeight);
                if(!is_null($avifWidthHeight)){
                    $avifSourceAttributes['width'] = $avifWidthHeight['width'];
                    $avifSourceAttributes['height'] = $avifWidthHeight['height'];
                }

                $htmlString .= $this->getHtmlTag('source', $avifSourceAttributes);
            }

            // webp version
            if(
                !is_null($singleSource['asset']) && 
                $this->canAddWebpSource($singleSource['asset'], $transfromSettings, $settingWebp)
            ){

                // force webp
                $transformWebp = array_merge($transfromSettings, ['format' => 'webp']);

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

                // width height
                $webpWidthHeight = self::getWidthHeightAttrs($singleSource['asset'], $transfromSettings, $settingWidthHeight);
                if(!is_null($webpWidthHeight)){
                    $webpSourceAttributes['width'] = $webpWidthHeight['width'];
                    $webpSourceAttributes['height'] = $webpWidthHeight['height'];
                }

                $htmlString .= $this->getHtmlTag('source', $webpSourceAttributes);
            }

            // non-webp/avif version
            $srcsetRegular = $this->getPlaceholderOrTransform($singleSource['asset'], $transfromSettings);
            $sourceAttributes = [
                'media' => $mediaBreakpoint,
                'srcset' => $srcsetRegular,
            ];
            
            // mime type
            if(!is_null($singleSource['asset'])){
                if(isset($transfromSettings['format'])){
                    $sourceAttributes['type'] = 'image/'.$transfromSettings['format'];
                }else{
                    $sourceAttributes['type'] = $singleSource['asset']->getMimeType();
                }
            }

            // width height
            $widthHeight = self::getWidthHeightAttrs($singleSource['asset'], $transfromSettings, $settingWidthHeight);
            if(!is_null($widthHeight)){
                $sourceAttributes['width'] = $widthHeight['width'];
                $sourceAttributes['height'] = $widthHeight['height'];
            }

            $htmlString .= $this->getHtmlTag('source', $sourceAttributes);
        }

        // fallback - first source
        $fallbackSource = reset($sources);
        $fallbackTransform = $fallbackSource['transform'] ?? [];
        $fallbackAsset = $fallbackSource['asset'] ?? null;
        $fallbackSrc = $this->getPlaceholderOrTransform($fallbackAsset, $fallbackTransform);

        $fallBackHtmlAttributes = [
            'src' => $fallbackSrc,
        ];

        // width height
        $fallbackWidthHeight = self::getWidthHeightAttrs($fallbackAsset, $transfromSettings, $settingWidthHeight);
        if(!is_null($fallbackWidthHeight)){
            $fallBackHtmlAttributes['width'] = $fallbackWidthHeight['width'];
            $fallBackHtmlAttributes['height'] = $fallbackWidthHeight['height'];
        }            


        // html attrs provided from template
        $fallBackHtmlAttributes = array_merge($fallBackHtmlAttributes, $htmlAttributes);

        $htmlString .= $this->getHtmlTag('img', $fallBackHtmlAttributes);
        $htmlString .= "\n";

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
        $backgroundColor = !is_null(ImageToolbox::$plugin->getSettings()->filePlaceholderBackgroundColor) ? ImageToolbox::$plugin->getSettings()->filePlaceholderBackgroundColor : self::PLACEHOLDER_DEFAULT_BACKGROUND;
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

    private function getTransformSettings(array|string|null $settings)
    {

        if(is_string($settings)){
            $transformObj = Craft::$app->imageTransforms->getTransformByHandle($settings);

            if(is_null($transformObj)){
                // returns empty array so image is not transformed is transfiorm with that handle missing
                return $this->throwException("Transform with handle \"$settings\" does not exist", []);
            }

            $transformArr = $transformObj->toArray();
            unset($transformArr['id']);
            unset($transformArr['name']);
            unset($transformArr['handle']);
            unset($transformArr['parameterChangeTime']);
            unset($transformArr['uid']);
            return $transformArr;
        }else{
            return $settings;
        }
    }

    public function svgFile(string|Asset|null $file, array $attributes, array $options)
    {
        $defaultOptions = [
            'removeFill' => false,
            'removeStroke' => false,
            'removeCss' => false,
            'sanitize' => true,
            'namespace' => true,
        ];
        $options = array_merge($defaultOptions, $options);

        if(is_null($file)){
            return null;
        }

        if($file instanceof Asset && $file->extension != 'svg'){
            return $this->throwException('Asset "' . $file->title . '" with ID ' . $file->id . ' is not svg file.', null);
        }

        // file path
        if(is_string($file)){
            $file = rtrim($file, '.svg');
            $file .= '.svg';
            $directory = ImageToolbox::$plugin->getSettings()->svgDirectory;
            if(!is_null($directory)){
                $file = $directory . DIRECTORY_SEPARATOR . $file;
                $file = Craft::getAlias($file);
            }else{
                $file = Craft::getAlias('@root') . DIRECTORY_SEPARATOR . $file;
            }
            if(!file_exists($file)){
                return $this->throwException('File with path  "' . $file . '" does not exist.', null);
            }
        }

        // sanitaze, namespace, resolve alias
        $svgHtml = \craft\helpers\Html::svg($file, $options['sanitize'], $options['namespace']);

        // remove fill atribute
        if($options['removeFill'] == true){
            $svgHtml = preg_replace('/' . 'fill' . '="[^"]*"/', '', $svgHtml);
        }
        if($options['removeStroke'] == true){
            $svgHtml = preg_replace('/' . 'style' . '="[^"]*"/', '', $svgHtml);
        }
        if($options['removeCss'] == true){
            $svgHtml = preg_replace('/' . 'stroke' . '="[^"]*"/', '', $svgHtml);
            $svgHtml = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $svgHtml);
        }

        // apply html attrs
        if(!empty($attributes)){
            $svgHtml = \craft\helpers\Html::modifyTagAttributes($svgHtml, $attributes);
        }

        $svgHtml = Template::raw($svgHtml);
        return $svgHtml;
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
