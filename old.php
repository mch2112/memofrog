<?php

require_once('global.php');

$footerText = IntroLib::GetFooterText();
$isMobile = Session::IsMobile();
$menu = IntroLib::RenderMenu('intro_menu');
if (!$isMobile)
    $menu = Html::Div('intro_menu_container', $menu);
$introPageDivClass = $isMobile ? 'intro_page mobile' : 'intro_page';
$body = <<< EOT
<div id='intro_page' class="$introPageDivClass">
    <div id='home_container'>
        <div id='home_frog'></div>
        <h1>Error: Outdated Browser</h1>
        <img src="/images/seofrog.png" class="offscreen" />
        <p>Sorry. Memofrog relies on advanced capabilities in modern browsers. Please consider updating your browser.</p>
        <div id="footer" class="footer">$footerText</div>
    </div>
</div>
EOT;
$body = Html::Tag('body', $body);
echo Page::RenderPage(true, $body);