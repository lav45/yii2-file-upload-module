<?php

namespace lav45\fileUpload\behaviors;

use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\base\Behavior;
use yii\base\InvalidCallException;

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
     *
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
     *
     * @var array
     */
    public $events = [
        ActiveRecord::EVENT_AFTER_INSERT  => 'afterInsert',
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
        $this->saveFile($this->getAttribute());
    }

    /**
     * Function will be called before updating the record.
     */
    public function beforeUpdate()
    {
        if (
            $this->isAttributeChanged() &&
            $this->saveFile($this->getAttribute()) &&
            $this->unlinkOldFile === true
        ) {
            $this->deleteFile($this->getOldAttribute());
        }
    }

    /**
     * Function will be called before deleting the record.
     */
    public function beforeDelete()
    {
        if ($this->unlinkOnDelete === true) {
            $this->deleteFile($this->getAttribute());
        }
    }

    /**
     * @param string|string[] $file name
     * @return bool
     */
    protected function saveFile($file)
    {
        $this->createUploadDir();

        if (empty($file)) {
            return false;
        }
        if (is_array($file)) {
            $result = true;
            foreach ($file as $item) {
                if (!$this->saveFile($item)) {
                    $result = false;
                }
            }
            return $result;
        }

        $tempFile = $this->tempDir . '/' . $file;
        $uploadFile = $this->uploadDir . '/' . $file;

        if (is_file($tempFile)) {
            return rename($tempFile, $uploadFile);
        }

        return false;
    }

    /**
     * Delete specified file.
     *
     * @param string $file File name
     * @return bool `true` if file was successfully deleted
     */
    protected function deleteFile($file)
    {
        $this->createUploadDir();
        
        if (empty($file)) {
            return false;
        }
        if (is_array($file)) {
            $result = true;
            foreach ($file as $item) {
                if (!$this->deleteFile($item)) {
                    $result = false;
                }
            }
            return $result;
        }

        $file = $this->uploadDir . '/' . $file;

        return is_file($file) ? unlink($file) : false;
    }

    /**
     * @return string|null
     */
    protected function getAttribute()
    {
        return $this->owner[$this->attribute];
    }

    /**
     * @return string|null
     */
    protected function getOldAttribute()
    {
        return $this->owner->getOldAttribute($this->attribute);
    }

    /**
     * @return bool
     */
    protected function isAttributeChanged()
    {
        return $this->owner->isAttributeChanged($this->attribute);
    }

    /**
     * Create dir for upload
     */
    protected function createUploadDir()
    {
        if(is_callable($this->uploadDir)) {
            $this->uploadDir = call_user_func($this->uploadDir);
        }

        $this->uploadDir = Yii::getAlias($this->uploadDir);

        if (!FileHelper::createDirectory($this->uploadDir)) {
            throw new InvalidCallException("Directory specified cannot be created.");
        }
    }
}
