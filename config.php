<?php 
define('SITE_NAME','leave.final.digital');
// application root
define('APP_ROOT', dirname(dirname(__FILE__)));
define('URL_ROOT','/');
define('URL_SUBFOLDER','');
define('base_url',filter_input(INPUT_SERVER,'SERVER_ADDR',FILTER_SANITIZE_ENCODED)
        .':'.filter_input(INPUT_SERVER,'SERVER_PORT',FILTER_SANITIZE_ENCODED)
        .'/index.php/');
//echo 'base_url '.base_url;


// $db_params = array(
// 	'servername' => trim('127.0.0.1'),
// 	'username' => trim('finaldigital_engdep'),
// 	'password' => trim('Hup84e5OCBH1'),
// 	'dbname' => trim('finaldigital_engdep')$db_params = array(
    'servername' => 'localhost',      // or '127.0.0.1'
    'username' => 'root',             // default XAMPP/WAMP username
    'password' => '',                 // empty password
    'dbname' => 'leave_db'            // your database name
);


);
define('upload_error',"");
 ?>