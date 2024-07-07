<?php

namespace iAvatar777\widgets\DateTime;

use cs\Application;
use cs\services\File;
use cs\services\SitePath;

use Imagine\Image\Box;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\VarDumper;
use yii\httpclient\Client;
use yii\widgets\InputWidget;
use yii\web\UploadedFile;
use yii\imagine\Image;
use Imagine\Image\ManipulatorInterface;
use cs\base\BaseForm;
use cs\services\UploadFolderDispatcher;
use \yii\helpers\FormatConverter;
use \yii\jui\JuiAsset;

/**
 * Используется для загрузки картинок в облако, обрезки, маркировки
 */
class DateTime extends \yii\jui\InputWidget
{
    /**
     * @var string the locale ID (e.g. 'fr', 'de', 'en-GB') for the language to be used by the date picker.
     * If this property is empty, then the current application language will be used.
     *
     * Since version 2.0.2 a fallback is used if the application language includes a locale part (e.g. `de-DE`) and the language
     * file does not exist, it will fall back to using `de`.
     */
    public $language;
    /**
     * @var boolean If true, shows the widget as an inline calendar and the input as a hidden field.
     */
    public $inline = false;
    /**
     * @var array the HTML attributes for the container tag. This is only used when [[inline]] is true.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $containerOptions = [];
    /**
     * @var string the format string to be used for formatting the date value. This option will be used
     * to populate the [[clientOptions|clientOption]] `dateFormat`.
     * The value can be one of "short", "medium", "long", or "full", which represents a preset format of different lengths.
     *
     * It can also be a custom format as specified in the [ICU manual](http://userguide.icu-project.org/formatparse/datetime#TOC-Date-Time-Format-Syntax).
     * Alternatively this can be a string prefixed with `php:` representing a format that can be recognized by the
     * PHP [date()](http://php.net/manual/de/function.date.php)-function.
     *
     * For example:
     *
     * ```php
     * 'MM/dd/yyyy' // date in ICU format
     * 'php:m/d/Y' // the same date in PHP format
     * ```
     *
     * If not set the default value will be taken from `Yii::$app->formatter->dateFormat`.
     */
    public $dateFormat;
    /**
     * @var string the model attribute that this widget is associated with.
     * The value of the attribute will be converted using [[\yii\i18n\Formatter::asDate()|`Yii::$app->formatter->asDate()`]]
     * with the [[dateFormat]] if it is not null.
     */
    public $attribute;
    /**
     * @var string the input value.
     * This value will be converted using [[\yii\i18n\Formatter::asDate()|`Yii::$app->formatter->asDate()`]]
     * with the [[dateFormat]] if it is not null.
     */
    public $value;

    public $options = ['class' => 'form-control'];


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->inline && !isset($this->containerOptions['id'])) {
            $this->containerOptions['id'] = $this->options['id'] . '-container';
        }
        if ($this->dateFormat === null) {
            $this->dateFormat = Yii::$app->formatter->dateFormat;
        }
    }

    /**
     * Renders the widget.
     */
    public function run()
    {
        echo $this->renderWidget() . "\n";

        $containerID = $this->inline ? $this->containerOptions['id'] : $this->options['id'];
        $language = $this->language ? $this->language : Yii::$app->language;

        $this->clientOptions = ArrayHelper::merge($this->getClientOptions(), $this->clientOptions);
        if (strncmp($this->dateFormat, 'php:', 4) === 0) {
            $this->clientOptions['dateFormat'] = FormatConverter::convertDatePhpToJui(substr($this->dateFormat, 4), 'date', $language);
        }
        else {
            $this->clientOptions['dateFormat'] = FormatConverter::convertDateIcuToJui($this->dateFormat, 'date', $language);
        }

        if ($language != 'en-US') {
            $view = $this->getView();
            $bundle = DatePickerLanguageAsset::register($view);
            if ($bundle->autoGenerate) {
                $fallbackLanguage = substr($language, 0, 2);
                if ($fallbackLanguage !== $language && !file_exists(Yii::getAlias($bundle->sourcePath . "/ui/i18n/datepicker-$language.js"))) {
                    $language = $fallbackLanguage;
                }
                $view->registerJsFile($bundle->baseUrl . "/ui/i18n/datepicker-$language.js", [
                    'depends' => [JuiAsset::className()],
                ]);
            }
            $options = Json::encode($this->clientOptions);
            $view->registerJs("$('#{$containerID}').datepicker($.extend({}, $.datepicker.regional['{$language}'], $options));");
        }
        else {
            $this->registerClientOptions('datepicker', $containerID);
        }

        $this->registerClientEvents('datepicker', $containerID);
        JuiAsset::register($this->getView());
    }


    /**
     * Returns the options for the captcha JS widget.
     *
     * @return array the options
     */
    protected function getClientOptions()
    {
        return [
            "changeMonth"      => true,
            "hideIfNoPrevNext" => true,
            "changeYear"       => true,
            "yearRange"        => "1915:+0",
        ];
    }

    /**
     * Renders the DatePicker widget.
     * @return string the rendering result.
     */
    protected function renderWidget()
    {
        $contents = [];

        // get formatted date value
        if ($this->hasModel()) {
            $value = Html::getAttributeValue($this->model, $this->attribute);
        }
        else {
            $value = $this->value;
        }
        if ($value !== null && $value !== '') {
            // format value according to dateFormat
            try {
                $value = Yii::$app->formatter->asDate($value, $this->dateFormat);
            } catch (InvalidParamException $e) {
                // ignore exception and keep original value if it is not a valid date
            }
        }
        $options = $this->options;
        $options['value'] = $value;

        if ($this->inline === false) {
            // render a text input
            if ($this->hasModel()) {
                $contents[] = Html::activeTextInput($this->model, $this->attribute, $options);
            }
            else {
                $contents[] = Html::textInput($this->name, $value, $options);
            }
        }
        else {
            // render an inline date picker with hidden input
            if ($this->hasModel()) {
                $contents[] = Html::activeHiddenInput($this->model, $this->attribute, $options);
            }
            else {
                $contents[] = Html::hiddenInput($this->name, $value, $options);
            }
            $this->clientOptions['defaultDate'] = $value;
            $this->clientOptions['altField'] = '#' . $this->options['id'];
            $contents[] = Html::tag('div', null, $this->containerOptions);
        }

        $div = Html::tag('div', implode("\n", $contents) . '<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>', ['class' => 'input-group' ]);

        return $div;
    }

    public function onAfterLoad($field)
    {
        $dateFormat = ArrayHelper::getValue($field, 'dateFormat', 'php:d.m.Y');
        if (strncmp($dateFormat, 'php:', 4) === 0) {
            $dateFormat = substr($dateFormat, 4);
        } else {
            $dateFormat = FormatConverter::convertDateIcuToPhp($dateFormat);
        }

        $model = $this->model;
        $attribute = $this->attribute;
        $v = \DateTime::createFromFormat($dateFormat, $model->$attribute);
        if ($v === false) {
            $f = FormatConverter::convertDatePhpToIcu($dateFormat);
            $model->addError($attribute, 'Не верный формат. Должен быть: ' . $f);
        } else {
            $model->$attribute = $v;
        }
    }

    public function onAfterLoadDb($field)
    {
        $model = $this->model;
        $attribute = $this->attribute;
        if ($model->$attribute) {
            $model->$attribute = new \DateTime($model->$attribute);
        }
    }

    public function onBeforeInsert($field)
    {
        $model = $this->model;
        $attribute = $this->attribute;
        $value = $model->$attribute;
        if ($value) {
            $model->$attribute = $value->format('Y-m-d');
        }
    }

    public function onBeforeUpdate($field)
    {
        $model = $this->model;
        $attribute = $this->attribute;
        $value = $model->$attribute;
        if ($value) {
            $model->$attribute = $value->format('Y-m-d');
        }
    }
}
