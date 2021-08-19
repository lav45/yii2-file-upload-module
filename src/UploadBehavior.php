<?php

namespace lav45\fileUpload;

use Closure;
use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;

/**
 * Class UploadBehavior
 * Uploading file behavior.
 *
 * Usage:
 * ```
 * 'uploadBehavior' => [
 *     'class' => UploadBehavior::class,
 *     'attribute' => 'image',
 * ]
 * ```
 *
 * @property ActiveRecord $owner
 */
class UploadBehavior extends Behavior
{
    use FileSystemTrait;

    /**
     * @var string
     */
    public $attribute;
    /**
     * Directory for moving files after model saving
     * For deferred call use as callback function
     * @var string|callable|null
     */
    public $uploadDir;
    /**
     * @var string Directory for temporary file saving
     */
    public $tempDir = '/temp';
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
        if (is_array($file)) {
            foreach ($file as $item) {
                $this->moveFile($item);
            }
            return;
        }
        $this->moveFile($file);
    }

    /**
     * @param string $file
     * @throws Exception
     */
    protected function moveFile($file)
    {
        if (empty($file)) {
            return;
        }
        $fs = $this->getFs();
        $tempFile = Yii::getAlias($this->tempDir) . '/' . $file;
        $uploadFile = $this->getUploadDir() . '/' . $file;

        if ($fs->has($uploadFile)) {
            return;
        }
        if ($fs->has($tempFile) === false) {
            throw new Exception("File '{$tempFile}' not found!");
        }

        if ($this->moveFile === true) {
            $fs->rename($tempFile, $uploadFile);
        } else {
            $fs->copy($tempFile, $uploadFile);
        }
    }

    /**
     * Delete specified file.
     * @param string|string[] $file File name
     * @throws InvalidConfigException
     */
    private function deleteFile($file)
    {
        if (is_array($file)) {
            foreach ($file as $item) {
                $this->unlinkFile($item);
            }
        } else {
            $this->unlinkFile($file);
        }
    }

    /**
     * @param string $file
     * @throws InvalidConfigException
     */
    protected function unlinkFile($file)
    {
        if ($file) {
            $this->getFs()->delete($this->getUploadDir() . '/' . $file);
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
     * @return string|null
     */
    protected function getUploadDir()
    {
        $path = $this->uploadDir;
        if ($path === null && $this->owner instanceof UploadInterface) {
            return $this->owner->getUploadDir();
        }
        if ($path instanceof Closure || (is_array($path) && is_callable($path))) {
            return $path();
        }
        return $path;
    }
}
