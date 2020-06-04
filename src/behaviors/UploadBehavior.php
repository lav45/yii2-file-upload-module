<?php

namespace lav45\fileUpload\behaviors;

use Yii;
use yii\base\Behavior;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
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
     * For deffered call use as callback function
     *
     * @var string|callable
     */
    public $uploadDir;

    /**
     * Directory for temporary file saving
     *
     * @var string
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
     * Init behavior
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if (empty($this->tempDir)) {
            throw new InvalidConfigException("`tempDir` must be set.");
        } else {
            $this->tempDir = Yii::getAlias($this->tempDir);
        }

        if (empty($this->uploadDir)) {
            throw new InvalidConfigException("`uploadDir` must be set.");
        }
    }

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
            $tempFile = $this->tempDir . '/' . $file;
            $uploadFile = $this->uploadDir . '/' . $file;

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
            $file = $this->uploadDir . '/' . $file;

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
     * Create dir for upload
     */
    private function createUploadDir()
    {
        if (is_callable($this->uploadDir)) {
            $this->uploadDir = call_user_func($this->uploadDir);
        }

        $this->uploadDir = Yii::getAlias($this->uploadDir);

        if (!FileHelper::createDirectory($this->uploadDir)) {
            throw new InvalidCallException("Directory specified cannot be created.");
        }
    }
}