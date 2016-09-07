<?php
http_response_code(404);
exit(0);
/** @noinspection PhpUnreachableStatementInspection */

require_once('global.php');
header("Content-Type: text/cache-manifest");
header("Cache-Control: no-cache, private");

$version = APP_VERSION . "|" . CSS_VERSION;
$jsUrl = Page::GetJsUrl();
$cssUrl = Page::GetCssUrl();

echo <<< EOT
CACHE MANIFEST
#$version
$jsUrl
$cssUrl
FALLBACK:
/intro /frog
/frog?CReq=2 /frog
NETWORK:
*
https://*
http://*
/ajax_fetch_data.php
EOT;
