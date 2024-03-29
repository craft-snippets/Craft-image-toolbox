<?php

namespace craftsnippets\imagetoolbox\fields;

use Craft;
use craft\base\Field;
use craft\helpers\Json;
use craft\base\ElementInterface;
use yii\db\Schema;

use \craftsnippets\imagetoolbox\models\ImageVariantsFieldModel;

class ImageVariantsField extends Field
{

    const SETTING_NONE = 'none';
    const SETTING_ENABLED = 'enabled';
    const SETTING_DISABLED = 'disabled';

    // field settings
    public array $variants = [];
    public string $useWebp = self::SETTING_NONE;
    public string $useAvif = self::SETTING_NONE;
    public string $useWidthHeight = self::SETTING_NONE;

    // field values

    public static function displayName(): string
    {
        return Craft::t('image-toolbox', 'Image variants');
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value instanceof ImageVariantsFieldModel) {
            return $value;
        }
        
        if(!is_array($value)){
            $value = json_decode($value, true);
        }
        
        return new ImageVariantsFieldModel(
            [
                'variants' => $value['variants'] ?? null,
                'useWebp' => $value['useWebp'] ?? null,
                'useAvif' => $value['useAvif'] ?? null,
                'useWidthHeight' => $value['useWidthHeight'] ?? null,
            ]
        );
    }

    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        return $value;
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $view = Craft::$app->getView();

        // DEPRECATED
        $id = $view->formatInputId($this->handle);
        $namespace = Craft::$app->view->namespaceInputId($id);

        if(get_class($element) != 'craft\elements\Asset'){
            return Craft::t('image-toolbox', 'Image toolbox variant field should be only assigned to assets.');
        }

        Craft::$app->view->registerAssetBundle(\craftsnippets\imagetoolbox\assetbundles\AlpineAsset::class);
        $template = 'image-toolbox/image-variants-field-input.twig';

        return $view->renderTemplate($template, [
            'element' => $element,
            'field' => $this,
            'id' => $id,
            'name' => $this->handle,
            'value' => $value,
            'variantsNamePrefix' => 'fields['.$this->handle.']',
        ]);
    }

    public function getSettingsHtml(): ?string
    {
        Craft::$app->view->registerAssetBundle(\craftsnippets\imagetoolbox\assetbundles\AlpineAsset::class);
        
        $template = 'image-toolbox/image-variants-field-settings.twig';
        return Craft::$app->getView()->renderTemplate($template, [
            'value' => $this, // "value" instead of "field"
            'variantsNamePrefix' => 'types[craftsnippets\\\imagetoolbox\\\fields\\\ImageVariantsField]', // need striple slash to get rendered with double slash so js reads with single shasl
        ]);
    }

    public static function isRequirable(): bool
    {
        return false;
    }

    // doesnt work ???
    public function getIsTranslatable(?ElementInterface $element = null): bool
    {
        return false;
    } 

    public static function supportedTranslationMethods(): array
    {
        return [
            self::TRANSLATION_METHOD_NONE,
        ];
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        return $rules;
    }

    public function getContentColumnType(): array|string
    {
        return Schema::TYPE_TEXT;
    }

}