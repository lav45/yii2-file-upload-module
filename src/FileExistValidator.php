<?php

namespace lav45\fileUpload;

use Closure;
use yii\helpers\ArrayHelper;
use yii\validators\Validator;

/**
 * Class FileExistValidator
 * @package lav45\fileUpload
 */
class FileExistValidator extends Validator
{
    use FileSystemTrait;

    /** @var string column name if attribute is array  */
    public $column;
    /** @var string[]|Closure[] for check file exist in storage path */
    public $folders = [];
    /** @var bool */
    public $enableClientValidation = false;
    /** @var string */
    public $message = "File '{value}' must be loaded.";

    /**
     * @param \yii\base\Model $model
     * @param string $attribute
     * @throws \Exception
     */
    public function validateAttribute($model, $attribute)
    {
        $value = ArrayHelper::getValue($model, $attribute);
        if (empty($value)) {
            return;
        }
        if (is_array($value)) {
            $this->validateArrayAttribute($model, $value, $this->column, $attribute);
        } else {
            $this->validateSimpleAttribute($model, $value, $attribute);
        }
    }

    /**
     * @param \yii\base\Model $model
     * @param array $data
     * @param string $column
     * @param string $attribute
     * @throws \yii\base\InvalidConfigException
     */
    protected function validateArrayAttribute($model, $data, $column, $attribute)
    {
        foreach ($data as $index => $file) {
            if (empty($file[$column])) {
                continue;
            }
            $file_name = $file[$column];
            if ($this->fileExist($file_name)) {
                continue;
            }
            $this->addError($model, "{$attribute}[{$index}][{$column}]", $this->message, [
                'value' => $file_name,
            ]);
        }
    }

    /**
     * @param \yii\base\Model $model
     * @param string $file_name
     * @param string $attribute
     * @throws \yii\base\InvalidConfigException
     */
    protected function validateSimpleAttribute($model, $file_name, $attribute)
    {
        if (empty($file_name)) {
            return;
        }
        if ($this->fileExist($file_name)) {
            return;
        }
        $this->addError($model, $attribute, $this->message, [
            'value' => $file_name,
        ]);
    }

    /**
     * @param string $file
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    protected function fileExist($file)
    {
        foreach ($this->folders as $path) {
            if ($path instanceof Closure || (is_array($path) && is_callable($path))) {
                $path = $path();
            }
            if ($this->getFs()->has("{$path}/{$file}")) {
                return true;
            }
        }
        return false;
    }
}