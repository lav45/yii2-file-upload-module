yii2-file-upload-module
==============================

This is a module for the Yii2 Framework which will help you upload files and access them from the browser.


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```shell
~$ composer require  lav45/yii2-file-upload-module
```

to the require section of your `composer.json` file.

Basic Usage:
------

Add path aliases and url to your file store in the main config
You need to configure your web server to the `@storageDir` directory and specify `@storageUrl`

```php
return [
    'aliases' => [
        '@storageUrl' => 'https://cdn.site.com/storage',
    ],
    'components' => [
        'fs' => [
            'class' => diecoding\flysystem\LocalComponent::className(),
            'path' => '@common/cdn',
            'secret' => 'secret'
        ]
    ],
];
```

Add action to the main controller

```php
use lav45\fileUpload\UploadAction;

class PageController extends Controller
{
    public function actions()
    {
        return [
            'upload' => [
                'class' => UploadAction::className(),
            ],
        ];
    }
}
```

Need to add to your ActiveRecord model

```php
use lav45\fileUpload\UploadTrait;
use lav45\fileUpload\UploadBehavior;
use lav45\fileUpload\UploadInterface;

class Page extends ActiveRecord implements UploadInterface
{
    use UploadTrait;

    public function rules()
    {
        return [
            [['image'], 'string'],
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => UploadBehavior::className(),
                'attribute' => 'image',
            ],
        ];
    }
    
    public function getUploadPath()
    {
        return '/page/' . $this->id;
    }
}
```

Need to add a field for uploading files

```php
/**
 * @var Page $model
 */
 
use lav45\fileUpload\widget\FileUpload;

$form = ActiveForm::begin();

echo $form->field($model, 'image')->widget(FileUpload::className());

ActiveForm::end();
```

Displays the uploaded file

```php
<?php /** @var Page $model */ ?>
 
<img src="<?= $model->getAttributeUrl('image') ?>" alt="">
```


Checking file access
--------------------

`FileExistValidator` supports an optional `accessCheck` callback in addition to
checking whether a file exists in one of the configured folders:

```php
use lav45\fileUpload\FileExistValidator;
use yii\base\Model;

return [
    [['files'], FileExistValidator::class,
        'column' => 'name',
        'folders' => ['@storageDir/page', '@storageTempDir'],
        'accessCheck' => static function (string $fileName, Model $model, string $attribute): bool {
            return Yii::$app->user->can('useFile', ['fileName' => $fileName]);
        },
    ],
];
```

The permission in this example must be supplied by the application. The callback
accepts any PHP callable and receives the filename, the validated model and the
attribute name. For array values it is called separately for each file; the
attribute identifies the item, for example `files[0][name]`.

Only a return value of `true` grants access. The callback runs before the
filesystem lookup; denial produces the validator's normal error message.
Permission does not replace the existence check. Omitting `accessCheck` or
setting it to `null` preserves the existing behavior.

This callback protects model validation when accepting a file reference.
Applications must enforce download authorization separately.
