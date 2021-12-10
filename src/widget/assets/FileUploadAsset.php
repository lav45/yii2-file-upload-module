<?php

namespace lav45\fileUpload\widget\assets;

use yii\web\AssetBundle;

/**
 * Class FileUploadAsset
 * @package lav45\fileUpload\widget\assets
 */
class FileUploadAsset extends AssetBundle
{
    /**
     * @var array
     */
    public $depends = [
        'lav45\fileUpload\widget\assets\BlueimpUploadJSAsset',
        'lav45\fileUpload\widget\assets\BlueimpUploadCSSAsset',
    ];
}
