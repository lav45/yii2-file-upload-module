<?php

namespace lav45\fileUpload\widgets\assets;

use yii\web\AssetBundle;

/**
 * Class FileUploadAsset
 * @package lav45\fileUpload\widgets\assets
 */
class FileUploadAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@bower/blueimp-file-upload';
    /**
     * @var array
     */
    public $css = [
        'css/jquery.fileupload.css'
    ];
    /**
     * @var array
     */
    public $js = [
        'js/vendor/jquery.ui.widget.js',
        'js/jquery.iframe-transport.js',
        'js/jquery.fileupload.js'
    ];
    /**
     * @var array
     */
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
