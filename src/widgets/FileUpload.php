<?php

namespace lav45\fileUpload\widgets;

use yii\base\Model;
use yii\helpers\Url;
use yii\helpers\Json;
use yii\widgets\InputWidget;

/**
 * Class FileUpload
 * @package lav45\fileUpload\widgets
 */
class FileUpload extends InputWidget
{
    /**
     * @var string|array upload route
     */
    public $url;
    /**
     * @var \yii\base\Model|\lav45\fileUpload\traits\UploadTrait
     */
    public $model;
    /**
     * @var string
     */
    public $template = 'input-group';
    /**
     * @var array the plugin options. For more information see the jQuery File Upload options documentation.
     * @see https://github.com/blueimp/jQuery-File-Upload/wiki/Options
     */
    public $clientOptions = [];
    /**
     * @var array the event handlers for the jQuery File Upload plugin.
     * Please refer to the jQuery File Upload plugin web page for possible options.
     * @see https://github.com/blueimp/jQuery-File-Upload/wiki/Options#callback-options
     */
    public $clientEvents = [];

    public function init()
    {
        parent::init();

        $this->clientOptions['url'] = Url::to($this->url);
    }

    public function run()
    {
        $input = $this->renderInput();

        $this->registerClientScript();
        $this->registerAssets();

        return $input;
    }
    
    protected function renderInput()
    {
        return $this->render($this->template);
    }

    protected function registerAssets()
    {
        assets\FileUploadAsset::register($this->getView());
    }

    protected function registerClientScript()
    {
        $options = Json::encode($this->clientOptions);
        $id = $this->options['id'];

        $js[] = "jQuery('#{$id}').fileupload({$options});";

        foreach ($this->clientEvents as $event => $handler) {
            $js[] = "jQuery('#{$id}').on('{$event}', {$handler});";
        }

        $this->getView()->registerJs(implode("\n", $js));
    }

    public function hasModel()
    {
        $result = parent::hasModel();

        if ($result === false && $this->model instanceof Model) {
            preg_match('~\w+\[(\w+)\](.+)~i', $this->name, $matches);
            $this->attribute = $matches[1] . $matches[2];
            $result = true;
        }

        return $result;
    }
}
