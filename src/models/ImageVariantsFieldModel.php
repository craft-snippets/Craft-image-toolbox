<?php
namespace craftsnippets\imagetoolbox\models;

use craftsnippets\imagetoolbox\ImageToolbox;
use Craft;
use craft\base\Model;

class ImageVariantsFieldModel extends Model
{

    const SETTING_NONE = 'none';
    const SETTING_ENABLED = 'enabled';
    const SETTING_DISABLED = 'disabled';
    
    public array $variants = [];
    public string $useWebp = self::SETTING_NONE;
    public string $useAvif = self::SETTING_NONE;
    public string $useWidthHeight = self::SETTING_NONE;    
}
