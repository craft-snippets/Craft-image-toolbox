<?php
/**
 * Image toolbox plugin for Craft CMS 3.x
 *
 * Image toolbox
 *
 * @link      http://craftsnippets.com/
 * @copyright Copyright (c) 2020 Piotr Pogorzelski
 */

namespace craftsnippets\imagetoolbox\models;

use craftsnippets\imagetoolbox\ImageToolbox;

use Craft;
use craft\base\Model;

/**
 * @author    Piotr Pogorzelski
 * @package   ImageToolbox
 * @since     1.0.0
 */
class Settings extends Model
{

    public $useWebp = true;
    public $forceWebp = false;
    public $useImager = true;
    public $usePlaceholders = true;
    public $placeholderClass = 'is-placeholder';
    public $useImagerForSvg = false;
    public $placeholderUrl = null;
    public $transformLayouts = [];
    public $forcePlaceholders = false;
    public $useWidthHeightAttributes = false;

}
