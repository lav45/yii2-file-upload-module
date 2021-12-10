<?php

namespace lav45\fileUpload\widget\assets;

use yii\web\AssetBundle;

/**
 * Class BlueimpUploadCSSAsset
 * @package lav45\fileUpload\widget\assets
 */
class BlueimpUploadCSSAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@bower/blueimp-file-upload/css';
    /**
     * @var array
     */
    public $css = [
        'jquery.fileupload.css'
    ];
}