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
    public $useAvif = true;
    public $forceAvif = false;
    public $useImager = true;
    public $usePlaceholders = true;
    public $placeholderClass = 'is-placeholder';
    public $useImagerForSvg = false;
    public $placeholderUrl = '';
    public $transformLayouts = [];
    public $forcePlaceholders = false;

    public $placeholderMode = 'file';
    public $filePlaceholderPath = null;
    public $filePlaceholderBackgroundColor = null;
    public $filePlaceholderBackgroundOpacity = null;
    public $filePlaceholderDirectory = 'placeholders';

    public $suppressExceptions = false;
    public $useWidthHeightAttributes = false;
    public $svgDirectory = null;
}
