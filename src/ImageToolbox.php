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

        $this->setComponents([
            'imageToolbox' => \craftsnippets\imagetoolbox\services\ImageToolboxService::class,
        ]);


    }

    protected function createSettingsModel(): ?craft\base\Model
    {
        return new Settings();
    }
}
