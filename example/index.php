<?php
$app = "app";
define ( "APP", "/youku/" );
$yii = dirname ( __FILE__ ) . '/framework/yii.php';
$config = dirname ( __FILE__ ) . '/app/config/main.php';
require_once ($yii);
Yii::createWebApplication ($config)->run ();
?>