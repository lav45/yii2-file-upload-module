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
     * For deferred call use as callback function
     * @var string|callable
     */
    public $uploadDir;
    /**
     * Directory for temporary file saving
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
     * @var boolean move or copy source file to storage directory
     */
    public $moveFile = true;

    private $deleteFiles = [];
    private $createFiles = [];

    /**
     * Init behavior
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function init()
    {
        parent::init();

        if (empty($this->tempDir)) {
            throw new InvalidConfigException("`tempDir` must be set.");
        }
        if (empty($this->uploadDir)) {
            throw new InvalidConfigException("`uploadDir` must be set.");
        }
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
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
        $old = $this->getOldAttribute();
        $new = $this->getAttribute();

        $updateFiles = array_intersect($old, $new);
        $this->deleteFiles = array_diff($old, $updateFiles);
        $this->createFiles = array_diff($new, $updateFiles);
    }

    /**
     * Function will be called after updating the record.
     */
    public function afterUpdate()
    {
        if (empty($this->createFiles) === false) {
            $this->createUploadDir();
            $this->saveFile($this->createFiles);
        }
        if ($this->unlinkOldFile === true && empty($this->deleteFiles) === false) {
            $this->deleteFile($this->deleteFiles);
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
     * Create specified file.
     * @param string|string[] $file name
     * @throws Exception
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
            return;
        }
        $this->moveFile($file);
    }

    /**
     * @param string $file_name
     * @throws Exception
     */
    protected function moveFile($file_name)
    {
        $tempFile = $this->getTempDir() . '/' . $file_name;
        $uploadFile = $this->getUploadDir() . '/' . $file_name;

        if (file_exists($tempFile) === false) {
            throw new Exception("File '{$file_name}' not found!");
        }
        if ($this->moveFile === true) {
            rename($tempFile, $uploadFile);
        } elseif (file_exists($uploadFile) === false) {
            copy($tempFile, $uploadFile);
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
            return;
        }
        $this->unlinkFile($file);
    }

    /**
     * @param string $file_name
     */
    protected function unlinkFile($file_name)
    {
        $file = $this->getUploadDir() . '/' . $file_name;
        if (is_file($file)) {
            unlink($file);
        }
    }

    /**
     * @return string[]
     */
    private function getAttribute()
    {
        return (array)$this->owner[$this->attribute];
    }

    /**
     * @return string[]
     */
    private function getOldAttribute()
    {
        return (array)$this->owner->getOldAttribute($this->attribute);
    }

    /**
     * @return string
     */
    protected function getUploadDir()
    {
        $path = $this->uploadDir;
        if (is_callable($path)) {
            $path = call_user_func($path);
        }
        return Yii::getAlias($path);
    }

    /**
     * @return string
     */
    protected function getTempDir()
    {
        return Yii::getAlias($this->tempDir);
    }

    /**
     * Create dir for upload
     * @throws \yii\base\Exception
     */
    protected function createUploadDir()
    {
        $path = $this->getUploadDir();
        if (FileHelper::createDirectory($path) === false) {
            throw new InvalidCallException("Directory specified cannot be created.");
        }
    }
}