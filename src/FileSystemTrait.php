<?php

namespace lav45\fileUpload;

use creocoder\flysystem\Filesystem;
use yii\base\InvalidConfigException;
use yii\di\Instance;

/**
 * Class FileSystemTrait
 * @package lav45\fileUpload
 */
trait FileSystemTrait
{
    /**
     * @var string|array|Filesystem
     */
    public $fs = 'fs';

    /**
     * @return Filesystem
     * @throws InvalidConfigException
     */
    protected function getFs()
    {
        if ($this->fs instanceof Filesystem === false) {
            $this->fs = Instance::ensure($this->fs, Filesystem::class);
        }
        return $this->fs;
    }
}