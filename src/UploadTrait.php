<?php

namespace lav45\fileUpload;

use Yii;
use yii\helpers\Html;

/**
 * Class UploadTrait
 * @package lav45\fileUpload
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
        /** @var \yii\base\Model $this */
        if ($value = Html::getAttributeValue($this, $attribute)) {
            return $this->getUploadUrl() . '/' . $value;
        }
        return null;
    }

    /**
     * @return string
     */
    public function getUploadDir()
    {
        return $this->getUploadDirPrefix() . $this->getUploadPath();
    }

    /**
     * @return string
     */
    public function getUploadDirPrefix()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getUploadPath()
    {
        return '';
    }
}
