<?php
http_response_code(404);
exit(0);
/** @noinspection PhpUnreachableStatementInspection */

require_once('global.php');
header("Content-Type: text/cache-manifest");
header("Cache-Control: no-cache, private");

$version = APP_VERSION . "|" . CSS_VERSION;
$jsUrl = Page::GetJsUrlIntro();
$cssUrl = Page::GetCssUrlIntro();

echo <<< EOT
CACHE MANIFEST
#$appVersion
$jsUrl
$cssUrl
/images/home_frog.jpg
FALLBACK:
/intro /frog
NETWORK:
*
https://*
http://*
EOT;
