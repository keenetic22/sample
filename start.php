<?php
require_once('News.php');
/* Пример кода использования модели */
/* Создание */
$news = new News();
$news->title = 'Новость';
$news->description = 'Описание 1';
$news->save();
echo '<br> Новая новость <br>';
var_dump($news);

/* Обновление */
$news = News::findByPk($news->getId());
$news->description = 'Описание 2';
$news->save();
echo '<br> Обновленная новость <br>';
var_dump($news);
/* Удаление */
$news->delete();
echo '<br> Удаленная новость <br>';
var_dump($news);
/* Получение страниц */
$news = News::getNewsByPage(1);
$news = News::getNewsByPage(2);
echo '<br> Вторая страница <br>';
var_dump($news);
/* Получение полной новости*/
$news = News::getNewsDetails(24);
$news = News::getNewsDetails(23);
$news = News::getNewsDetails(25);
echo '<br> Новость №25 <br>';
var_dump($news);
?>

