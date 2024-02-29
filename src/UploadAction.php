<?php

namespace lav45\fileUpload;

use Yii;
use yii\base\Action;
use yii\base\DynamicModel;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * Class UploadAction
 * @package lav45\fileUpload
 *
 * UploadAction for images and files.
 *
 * Usage:
 *
 * ```php
 * public function actions()
 * {
 *     return [
 *         'upload-image' => [
 *             'class' => 'lav45\fileUpload\UploadAction',
 *             'validatorOptions' => [
 *                 'maxWidth' => 1000,
 *                 'maxHeight' => 1000
 *             ]
 *         ],
 *         'file-upload' => [
 *             'class' => 'lav45\fileUpload\UploadAction',
 *             'url' => '/statics',
 *             'path' => '/statics',
 *             'uploadOnlyImage' => false,
 *             'validatorOptions' => [
 *                 'maxSize' => 40000
 *             ]
 *         ]
 *     ];
 * }
 * ```
 */
class UploadAction extends Action
{
    use FileSystemTrait;

    /**
     * @var string The name of the variable that the form submits when the image/file is upload.
     */
    public $uploadParam = 'file';
    /**
     * @var array validator options for \yii\validators\FileValidator or \yii\validators\ImageValidator
     */
    public $validatorOptions = [];
    /**
     * @var array|\Closure
     */
    public $beforeStorage;
    /**
     * @var array|\Closure
     */
    public $afterRun;
    /**
     * @var array|\Closure
     */
    public $createFileName;
    /**
     * @var string Path to directory where files will be uploaded
     */
    public $path = '/temp';
    /**
     * @var string URL path to directory where files will be uploaded
     */
    public $url = '@storageUrl/temp';
    /**
     * @var string Model validator name
     */
    private $validator = 'image';

    /**
     * @param boolean $flag
     */
    public function setUploadOnlyImage($flag)
    {
        $this->validator = $flag ? 'image' : 'file';
    }

    public function run()
    {
        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;

        $file = UploadedFile::getInstanceByName($this->uploadParam);

        if ($file === null) {
            throw new BadRequestHttpException('Incorrect upload file');
        }

        $model = new DynamicModel(['file' => $file]);
        $model->addRule('file', $this->validator, $this->validatorOptions);

        if ($model->validate() === false) {
            $result = ['error' => $model->getFirstError('file')];
        } else {
            $response->getHeaders()->set('Vary', 'Accept');
            $file_name = $this->createFileName($file);
            $file_url = Yii::getAlias($this->url) . '/' . $file_name;
            $result = [
                'original_name' => $file->name,
                'extension' => $file->extension,
                'name' => $file_name,
                'type' => $file->type,
                'size' => $file->size,
                'url' => $file_url,
            ];

            $tempName = $file->tempName;
            if ($this->beforeStorage) {
                [$tempName, $error] = call_user_func($this->beforeStorage, $tempName, $result);
                if ($error) {
                    return ['error' => $error];
                }
            }

            if ($this->moveFile($tempName, $file_name) === false) {
                $result = ['error' => 'Failed to load file'];
            }
        }

        if (is_callable($this->afterRun)) {
            $result = call_user_func($this->afterRun, $result);
        }
        return $result;
    }

    /**
     * @param string $source
     * @param string $file_name
     * @return bool
     * @throws Exception
     * @throws InvalidConfigException
     */
    protected function moveFile($source, $file_name)
    {
        $stream = fopen($source, 'rb+');
        if ($stream === false) {
            if (YII_DEBUG) {
                throw new Exception("File '{$source}' not found!");
            }
            return false;
        }
        $file_path = Yii::getAlias($this->path) . '/' . $file_name;

        unlink($source);

        return $this->getFs()->writeStream($file_path, $stream);
    }

    /**
     * @param UploadedFile $file
     * @return string
     * @throws InvalidConfigException
     */
    protected function createFileName(UploadedFile $file)
    {
        $fileExtension = ($file->getExtension() ? '.' . $file->getExtension() : '');
        if ($this->createFileName === null) {
            do {
                $file_name = uniqid('', false) . $fileExtension;
                $file_path = Yii::getAlias($this->path) . '/' . $file_name;
            } while ($this->getFs()->has($file_path));
        } else {
            $file_name = call_user_func($this->createFileName, $fileExtension);
        }
        return $file_name;
    }
}
