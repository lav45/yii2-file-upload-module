<?php

namespace lav45\fileUpload\traits;

use Yii;
use yii\helpers\Html;

/**
 * Class UploadTrait
 * @package lav45\fileUpload\traits
 */
trait UploadTrait
{
    /**
     * @return string
     */
    public function getUploadUrl()
    {
        return Yii::getAlias('@storageUrl') . $this->getUploadPath();
    }

    /**
     * @param string $attribute image, image[0]
     * @return string
     */
    public function getAttributeUrl($attribute)
    {
        /** @var $this \yii\base\Model */
        return $this->getUploadUrl() . '/' . Html::getAttributeValue($this, $attribute);
    }

    /**
     * @return string
     */
    public function getUploadDir()
    {
        return Yii::getAlias('@storagePath') . $this->getUploadPath();
    }

    /**
     * @return string
     */
    public static function getTempDir()
    {
        return Yii::getAlias('@webroot/assets/upload');
    }

    /**
     * @return string
     */
    public function getUploadPath()
    {
        return '';
    }
}