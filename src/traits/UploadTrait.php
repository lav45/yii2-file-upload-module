<?php

namespace lav45\fileUpload\traits;

use Yii;

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
     * @param string $attribute
     * @return string
     */
    public function getAttributeUrl($attribute)
    {
        return $this->getUploadUrl() . '/' . $this->{$attribute};
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
        return Yii::getAlias('@runtime/upload');
    }

    /**
     * @return string
     */
    abstract public function getUploadPath();
}