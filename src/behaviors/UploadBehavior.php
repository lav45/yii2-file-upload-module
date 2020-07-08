<?php

namespace lav45\fileUpload\behaviors;

use Yii;
use yii\base\Behavior;
use yii\base\InvalidArgumentException;
use yii\base\InvalidCallException;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;

/**
 * Class UploadBehavior
 * Uploading file behavior.
 *
 * Usage:
 * ```
 * 'uploadBehavior' => [
 *     'class' => UploadBehavior::class,
 *     'uploadDir' => '@storagePath/upload/dir',
 *     'tempDir' => '@runtime/upload',
 *     'attribute' => 'image',
 * ]
 * ```
 *
 * @property ActiveRecord $owner
 */
class UploadBehavior extends Behavior
{
    /**
     * @var string
     */
    public $attribute;
    /**
     * Directory for moving files after model saving
     * For deferred call use as callback function
     * @var string|callable
     */
    public $uploadDir;
    /**
     * Directory for temporary file saving
     * @var string|callable
     */
    public $tempDir;
    /**
     * @var boolean If `true` current attribute file will be deleted
     */
    public $unlinkOldFile = true;
    /**
     * @var boolean If `true` current attribute file will be deleted after model deletion
     */
    public $unlinkOnDelete = true;

    /**
     * List of events
     * @var array
     */
    public $events = [
        ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
        ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
        ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
    ];

    /**
     * @inheritdoc
     */
    public function events()
    {
        return $this->events;
    }

    /**
     * Function will be called after inserting the new record.
     */
    public function afterInsert()
    {
        $this->createUploadDir();
        $this->saveFile($this->getAttribute());
    }

    /**
     * Function will be called before updating the record.
     */
    public function beforeUpdate()
    {
        $this->createUploadDir();

        $old = $this->getOldAttribute();
        $new = $this->getAttribute();
        $updateFiles = array_intersect($old, $new);
        $deleteFiles = array_diff($old, $updateFiles);
        $createFiles = array_diff($new, $updateFiles);

        if ($this->isAttributeChanged() && empty($createFiles) === false) {
            $this->saveFile($createFiles);
        }
        if ($this->unlinkOldFile === true && empty($deleteFiles) === false) {
            $this->deleteFile($deleteFiles);
        }
    }

    /**
     * Function will be called before deleting the record.
     */
    public function beforeDelete()
    {
        if ($this->unlinkOnDelete === true) {
            $this->createUploadDir();
            $this->deleteFile($this->getAttribute());
        }
    }

    /**
     * Create specified file.
     * @param string|string[] $file name
     */
    private function saveFile($file)
    {
        if (empty($file)) {
            return;
        }

        if (is_array($file)) {
            foreach ($file as $item) {
                $this->saveFile($item);
            }
        } else {
            $tempFile = $this->getTempDir() . '/' . $file;
            $uploadFile = $this->getUploadDir() . '/' . $file;

            if (is_file($tempFile)) {
                rename($tempFile, $uploadFile);
            }
        }
    }

    /**
     * Delete specified file.
     * @param string|string[] $file File name
     */
    private function deleteFile($file)
    {
        if (empty($file)) {
            return;
        }

        if (is_array($file)) {
            foreach ($file as $item) {
                $this->deleteFile($item);
            }
        } else {
            $file = $this->getUploadDir() . '/' . $file;

            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * @return string[]
     */
    private function getAttribute()
    {
        return (array) $this->owner[$this->attribute];
    }

    /**
     * @return string[]
     */
    private function getOldAttribute()
    {
        return (array) $this->owner->getOldAttribute($this->attribute);
    }

    /**
     * @return bool
     */
    private function isAttributeChanged()
    {
        return $this->owner->isAttributeChanged($this->attribute);
    }

    /**
     * @return string
     */
    public function getUploadDir()
    {
        return $this->normalizePath($this->uploadDir);
    }

    /**
     * @return string
     */
    public function getTempDir()
    {
        return $this->normalizePath($this->tempDir);
    }

    /**
     * @param string $path
     * @return string
     */
    private function normalizePath($path)
    {
        if (is_callable($path)) {
            $dir = $path();
        } else {
            $dir = Yii::getAlias($path, false);
        }
        if (empty($dir)) {
            throw new InvalidArgumentException("Invalid path: {$dir}");
        }
        return $dir;
    }

    /**
     * Create dir for upload
     */
    private function createUploadDir()
    {
        if (FileHelper::createDirectory($this->getUploadDir()) === false) {
            throw new InvalidCallException("Directory specified cannot be created.");
        }
    }
}