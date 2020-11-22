# yii2-widget-datetime

Виджет 

Я могу сделать конвертацию в `onAfterLoadDb` из YYYY-mm-dd в dd.mm.YYYY
а в событии `onBeforeUpdate` из dd.mm.YYYY в YYYY-mm-dd
то при валидации в поле будет формат dd.mm.YYYY, при выводе формы будет dd.mm.YYYY
При загрузке формы значение будет загружено в формате dd.mm.YYYY поэтому ничего менять не нужно
