<?php

namespace craftsnippets\imagetoolbox\assetbundles;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;


class AlpineAsset extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = "@craftsnippets/imagetoolbox/assetbundles";

        $this->js = [
            'alpine.min.js',
        ];

        parent::init();
    }
}
