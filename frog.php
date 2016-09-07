<?php

require_once('global.php');

Session::DoSession();

Response::RenderHtmlResponse(Page::RenderPage(false));
