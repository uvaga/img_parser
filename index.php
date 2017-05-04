<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 03.05.2017
 * Time: 19:10
 */
require_once ("./config/web.php");


function my_autoloader($class_name) {
    global $config;
    require_once $config['paths']['classes'].$class_name.'.Class.php';
}

spl_autoload_register('my_autoloader');

DB::connect($config["db"]);
$URL = urldecode($_REQUEST['url']);

Pages::findHrefs($URL);
