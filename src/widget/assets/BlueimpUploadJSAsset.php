<?php

namespace lav45\fileUpload\widget\assets;

use yii\web\AssetBundle;

/**
 * Class BlueimpUploadJSAsset
 * @package lav45\fileUpload\widget\assets
 */
class BlueimpUploadJSAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@bower/blueimp-file-upload/js';
    /**
     * @var array
     */
    public $js = [
        'vendor/jquery.ui.widget.js',
        'jquery.iframe-transport.js',
        'jquery.fileupload.js'
    ];
    /**
     * @var array
     */
    public $depends = [
        'yii\web\JqueryAsset',

    ];
}