<?php

function __autoload_elastica ($class) {
	$path = str_replace('_', DIRECTORY_SEPARATOR, $class);

	$file_name = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $path . '.php';
	
	if (file_exists($file_name)) {
		require_once($file_name);
	}
}

function __autoload_app ($class) {
	$path = str_replace('_', DIRECTORY_SEPARATOR, $class);

	$file_name = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $path . '.php';
	
	if (file_exists($file_name)) {
		require_once($file_name);
	}
}

spl_autoload_register('__autoload_elastica');
spl_autoload_register('__autoload_app');