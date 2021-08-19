<?php

namespace lav45\fileUpload;

/**
 * Class UploadInterface
 * @package lav45\fileUpload
 */
interface UploadInterface
{
    /**
     * @param string $attribute image, image[0]
     * @return string
     */
    public function getAttributeUrl($attribute);

    /**
     * @return string
     */
    public function getUploadDir();
}