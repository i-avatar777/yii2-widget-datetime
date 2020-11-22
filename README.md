# yii2-widget-datetime

Виджет для загрузки файла

Используется для `\cs\base\FormActiveRecord` или как самостоятельно в форме. Если первый вариант то можно еще указать событие для удаления файла при удалении строки таблицы.

Загружает на сервер AvatarCloud или на локальный сервер если сформировано окружение.

## Окружение для загрузки файла на локальный сервер


## Установка



## Пример использования

```php
<?= \iAvatar777\widgets\DateTime\DateTime::widget([
    'model'      => $model,
    'attribute'  => 'file',
]) ?>
```

```php
<?= $form->field($model, 'user_foto')->widget('\iAvatar777\widgets\FileUpload7\FileUpload',
[
    'update'    => [
        [
            'function' => 'crop',
            'index'    => 'crop',
            'options'  => [
                'width'  => '300',
                'height' => '300',
                'mode'   => 'MODE_THUMBNAIL_CUT',
            ],
        ],
    ],
    'settings' => [
        'maxSize'           => 20 * 1000,
        'server'            => Yii::$app->AvatarCloud->url,
        'controller'        => 'upload',
        'functionSuccess'   => new \yii\web\JsExpression(<<<JS
function (response) {
    // Вызываю AJAX для записи в school_file
    ajaxJson({
        url: '/site/file-upload7-save',
        data: {
            file: response.url,
            school_id: 71,
            type_id: 25,
            size: response.size, // Размер файла в байтах
            update: response.update
        },
        success: function (ret) {
            
        }
    });
}

JS

        ),
    ],
    'events'    => [
        'onDelete' => function ($item) {
            $r = new \cs\services\Url($item['image']);
            $d = pathinfo($r->path);
            $start = $d['dirname'] . '/' . $d['filename'];

            \common\models\school\File::deleteAll(['like', 'file', $start]);
        },
    ],
]
)
?>
```

## Параметры

FileUpload7.init(options)
или параметр `settings`

- `controller` - идентификатор контроллера который направлен на \iAvatar777\assets\JqueryUpload1\Upload2Controller
- `selector` - запрос JQuery идентифицирующий элемент (input) загрузки. по умолчанию `'.FileUpload7'`
- `maxSize` - макс размер файла в KB
- `server` - путь к внешнему серверу, по умолчанию '' (Например 'https://cloud1.i-am-avatar.com')
- `allowedExtensions` - массив расширений которые возможны для загрузки. По умолчанию `['jpg', 'jpeg', 'png']`
- `accept` - mime тип который можно загрузить. По умолчанию `'image/*'`
- `data` - Массив данных которые надо передать на действие сохранения файла
- `button_label` - текст на кнопке

## Как работает

Определить сервер для загрузки

## ?
Если при удалении изобрадении надо передавать ключ то как
Какой концепт работы с виджетом?
Если работает с AvatarCloud то значит надо передавать ключ для удаления файла для облака.