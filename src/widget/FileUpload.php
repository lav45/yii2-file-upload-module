<?php

namespace lav45\fileUpload\widget;

use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\InputWidget;

/**
 * Class FileUpload
 * @package lav45\fileUpload\widget
 */
class FileUpload extends InputWidget
{
    /**
     * @var string|array upload route
     */
    public $url = ['upload'];
    /**
     * @var \yii\base\Model|\lav45\fileUpload\UploadInterface
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
     * @var string
     */
    private $js;

    /**
     * @return string
     */
    public function run()
    {
        $input = $this->renderInput();

        $this->registerClientScript();
        $this->registerAssets();

        return $input;
    }

    /**
     * @return string
     */
    protected function renderInput()
    {
        return $this->render($this->template);
    }

    protected function registerAssets()
    {
        assets\BlueimpUploadJSAsset::register($this->getView());
    }

    /**
     * @param array $data
     */
    public function setClientEvents(array $data)
    {
        foreach ($data as $event => $handler) {
            $this->addClientEvents($event, $handler);
        }
    }

    /**
     * @param string $event
     * @param string $handler
     * @see https://github.com/blueimp/jQuery-File-Upload/wiki/Options#callback-options
     */
    public function addClientEvents($event, $handler)
    {
        $this->js .= "jQuery('#{id}').on('{$event}', {$handler});\n";
    }

    protected function registerClientScript()
    {
        $this->view->registerJs("$(document).on('drop dragover', (e) => e.preventDefault())");

        $options = $this->clientOptions;
        if (!isset($options['dropZone'])) {
            $options['dropZone'] = new JsExpression(sprintf('$("#%s").parent()', $this->options['id']));
        }
        $options['url'] = Url::to($this->url);
        $options = Json::encode($options);

        $this->js .= "jQuery('#{id}').fileupload({$options});\n";
        $this->js = str_replace('{id}', $this->options['id'], $this->js);

        $this->getView()->registerJs($this->js);
    }
}
