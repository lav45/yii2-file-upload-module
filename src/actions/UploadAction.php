<?php

namespace lav45\fileUpload\actions;

use Yii;
use yii\base\Action;
use yii\base\DynamicModel;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * Class UploadAction
 * @package vova07\imperavi\actions
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
 *             'class' => 'lav45\fileUpload\actions\UploadAction',
 *             'url' => '@web/assets/upload',
 *             'path' => '@webroot/assets/upload',
 *             'validatorOptions' => [
 *                 'maxWidth' => 1000,
 *                 'maxHeight' => 1000
 *             ]
 *         ],
 *         'file-upload' => [
 *             'class' => 'lav45\fileUpload\actions\UploadAction',
 *             'url' => '/statics',
 *             'path' => '@webroot/statics',
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
    /**
     * @var string Variable's name that Imperavi Redactor sent upon image/file upload.
     */
    public $uploadParam = 'file';
    /**
     * @var array validator options for \yii\validators\FileValidator or \yii\validators\ImageValidator
     */
    public $validatorOptions = [];
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
    private $path;
    /**
     * @var string URL path to directory where files will be uploaded
     */
    private $url = '@web/assets/upload';
    /**
     * @var string Model validator name
     */
    private $validator = 'image';

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     * @throws InvalidConfigException
     */
    public function getPath()
    {
        $path = Yii::getAlias($this->path);
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        if (empty($path)) {
            throw new InvalidConfigException('The "path" attribute must be set.');
        }
        if (!FileHelper::createDirectory($path)) {
            throw new InvalidCallException("Directory specified in 'path' attribute doesn't exist or cannot be created.");
        }
        return $path;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     * @throws InvalidConfigException
     */
    public function getUrl()
    {
        $url = Yii::getAlias($this->url);
        $url = rtrim($url, '/');

        if (empty($url)) {
            throw new InvalidConfigException('The "url" attribute must be set.');
        }
        return $url;
    }

    /**
     * @param boolean $flag
     */
    public function setUploadOnlyImage($flag)
    {
        $this->validator = $flag === true ? 'image' : 'file';
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;

        if (Yii::$app->getRequest()->getIsPost() === false) {
            throw new BadRequestHttpException('Only POST is allowed');
        }

        $file = UploadedFile::getInstanceByName($this->uploadParam);

        $model = new DynamicModel(['file' => $file]);
        $model->addRule('file', $this->validator, $this->validatorOptions);

        if ($model->validate() === false) {
            $result = ['error' => $model->getFirstError('file')];
        } else {
            $response->getHeaders()->set('Vary', 'Accept');

            $result = [
                'original_name' => $file->name,
                'name' => $file->name = $this->createFileName($file),
                'type' => $file->type,
                'size' => $file->size,
                'url' => $this->getUrl() . '/' . $file->name,
            ];

            if ($file->saveAs($this->getPath() . '/' . $file->name) === false) {
                $result = ['error' => 'Failed to load file'];
                @unlink($file->tempName);
            }
        }

        if (is_callable($this->afterRun)) {
            $result = call_user_func($this->afterRun, $result);
        }

        return $result;
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    protected function createFileName(UploadedFile $file)
    {
        $fileExtension = ($file->getExtension() ? '.' . $file->getExtension() : '');
        if ($this->createFileName === null) {
            do {
                $file_name = uniqid() . $fileExtension;
            } while (file_exists($this->path . '/' . $file_name));
        } else {
            $file_name = call_user_func($this->createFileName, $fileExtension, $this->path);
        }

        return $file_name;
    }
}
