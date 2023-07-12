<?php
/**
 * Image toolbox plugin for Craft CMS 3.x
 *
 * Image toolbox
 *
 * @link      http://craftsnippets.com/
 * @copyright Copyright (c) 2020 Piotr Pogorzelski
 */

namespace craftsnippets\imagetoolbox;

use craftsnippets\imagetoolbox\services\ImageToolboxService as ImageToolboxServiceService;
use craftsnippets\imagetoolbox\variables\ImageToolboxVariable;
use craftsnippets\imagetoolbox\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;

/**
 * Class ImageToolbox
 *
 * @author    Piotr Pogorzelski
 * @package   ImageToolbox
 * @since     1.0.0
 *
 * @property  ImageToolboxServiceService $imageToolboxService
 */
class ImageToolbox extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var ImageToolbox
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    /**
     * @var bool
     */
    public bool $hasCpSettings = false;

    /**
     * @var bool
     */
    public bool $hasCpSection = false;

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                $variable = $event->sender;
                $variable->set('images', ImageToolboxVariable::class);
            }
        );

        // components
        $this->setComponents([
            'imageToolbox' => \craftsnippets\imagetoolbox\services\ImageToolboxService::class,
        ]);

        // register field
        if($this->isProEdition()){
            $this->registerFields();
        }

    }

    private function registerFields()
    {
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = \craftsnippets\imagetoolbox\fields\ImageVariantsField::class;
            }
        );           
    }

    protected function createSettingsModel(): ?craft\base\Model
    {
        return new Settings();
    }


    const EDITION_LITE = 'lite';
    const EDITION_PRO = 'pro';

    public static function editions(): array
    {
        return [
            self::EDITION_LITE,
            self::EDITION_PRO,
        ];
    }

    public function isProEdition()
    {
        // return $this->is(self::EDITION_PRO);
        return true;
    }


}
