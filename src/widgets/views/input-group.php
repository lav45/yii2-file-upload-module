<?php
/**
 * @var yii\web\View $this
 * @var boolean $deletable
 */

use yii\helpers\Html;

/** @var \lav45\fileUpload\widgets\FileUpload $widget */
$widget = $this->context;

$widget->options['id'] .= '-uploader';

$targetId = $widget->hasModel() ? Html::getInputId($widget->model, $widget->attribute) : $widget->getId();

if ($deletable) {
    $deletable = !$widget->model->isAttributeRequired($widget->attribute);
}

$widget->addClientEvents('fileuploaddone', <<<JS
function(e, data) {
    if (data.result.error) {
        alert(data.result.error);
    } else {
        var el = jQuery('#{$targetId}'),
            fileName = data.result.name,
            fileUrl = data.result.url;
         
        el.val(fileName);
        
        var link = jQuery('<a>')
            .text(fileName)
            .attr({
                href: fileUrl,
                target: '_blank'
            });

        el.parent().find('.input-result').html(link);

        var deletable = '{$deletable}';

        if (deletable.length) {

            var checkbox = jQuery('<input />', {
                type: 'checkbox',
                name: 'delete[{$widget->attribute}]',
                value: 1,
                id: 'cb-{$widget->attribute}'
            }).css('vertical-align', 'middle');

            el.parent().find('.input-delete').html(checkbox);

            var label = jQuery('<label />', { for: 'cb-{$widget->attribute}' })
                .css('vertical-align', 'middle')
                .css('margin-left', '5px')
                .css('margin-bottom', '0px')
                .html('<span class="glyphicon glyphicon-trash"></span>');

            label.insertAfter(checkbox);

        }
    }
}
JS
);

Html::addCssClass($widget->options, 'form-control');

$input = $widget->hasModel() ?
    Html::activeHiddenInput($widget->model, $widget->attribute) :
    Html::hiddenInput($widget->name, $widget->value, ['id' => $widget->getId()]);

$input .= Html::fileInput('file', null, $widget->options);

$value = $widget->hasModel() ? Html::getAttributeValue($widget->model, $widget->attribute) : $widget->value;

$url = $widget->hasModel() ? $widget->model->getAttributeUrl($widget->attribute) : null;

?>

<div class="input-group">
    <?= $input ?>
    <span class="input-group-addon input-result">
        <?php if ($url): ?>
            <a href="<?= $url ?>" target="_blank"><?= $value ?></a>
        <?php else: ?>
            <?= $value ?>
        <?php endif; ?>
    </span>
    <?php if ($deletable): ?>
        <span class="input-group-addon input-delete">
            <?php if (!empty($value)): ?>
                <?= Html::checkbox('delete[' . $widget->attribute . ']', false, ['id' => 'cb-' . $widget->attribute, 'style' => 'vertical-align: middle']) ?>
                <label style="vertical-align: middle; margin-left: 5px; margin-bottom: 0px;"
                       for="cb-<?= $widget->attribute ?>"><span class="glyphicon glyphicon-trash"></span></label>
            <?php endif; ?>
        </span>
    <?php endif; ?>
</div>
