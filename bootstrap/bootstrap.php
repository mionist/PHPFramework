<?php
// Подавляем отображение ошибок
ini_set('display_errors', 1);
error_reporting( E_ALL );

// Определяем кодировку
mb_internal_encoding('UTF-8');

// Устанавливаем таймзону
date_default_timezone_set( Configuration::TIME_ZONE );

// Подключаем основные ошибки
require Configuration::PATH_FRAMEWORK.'bootstrap/CoreExceptions.php';

// Расширяем ошибки PHP
set_error_handler( 'my_error_handler' );
// Поддержка старой версии PHP
function my_error_handler( $errno, $errstr, $errfile, $errline ){
	throw new PHPStandartException($errno, $errstr, $errfile, $errline);
	return true;
}
 

// Подключаем Engine и стандартные интерфейсы
require Configuration::PATH_FRAMEWORK.'src/Core.php';
require Configuration::PATH_FRAMEWORK.'src/Standard/CoreFS.php';
require Configuration::PATH_FRAMEWORK.'bootstrap/interfaces.php';

// Регистрируем autoloader
function __autoload( $className ){
	return Core::ClassLoader($className);
}

// Меняем IP для хостингов
if ( isset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['REMOTE_ADDR'], $_SERVER['SERVER_ADDR']) && $_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR'] ) $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];

// Инициализируем Core
Core::Init();

// Отдельно уничтожаем всякие паразитные элементы
if ( isset($_SERVER['REQUEST_URI']) && strtolower($_SERVER['REQUEST_URI']) === '/favicon.ico' ){
	header('Status: 404 Not Found', 404); exit;
}


