<?php

require_once('global.php');

Session::DoSession();

if (Session::IsLoggedIn())
    Session::Refresh();

$regKey = ContentKey::CONTENT_KEY_REGISTER_USER;
$helpKey = ContentKey::CONTENT_KEY_HELP;
$registerKey = ContentKey::CONTENT_KEY_REGISTER_USER;
$footerText = IntroLib::GetFooterText();
$isMobile = Session::IsMobile();
$menu = IntroLib::RenderMenu('intro_menu');
if (!$isMobile)
    $menu = Html::Div('intro_menu_container', $menu);
$regForm = getRegForm();
$firstBlock = Session::IsMobile() ? '' : getListOfThings();
$secondBlock = Session::IsMobile() ? Html::Div('', Html::Tag('h2', 'Memofrog can help you organize.') . getListOfThings()) : '';
$introPageDivClass = $isMobile ? 'intro_page mobile' : 'intro_page';
$body = <<< EOT
<div id='intro_page' class="$introPageDivClass">
$menu
<div id='home_container'>
<div id='home_frog'></div>
<h1>Memofrog remembers stuff so you don&apos;t have to.</h1>
<img src="/images/seofrog.png" class="offscreen" />
<div class='two_col'>
    <div>
        <p>Toss your thoughts into the pond and Memofrog will organize them for you. Easily share with friends or keep them private. On your computer and phone, with no app to download or install. And all free!</p>
        $firstBlock
    </div>
    <div>$regForm</div>
    $secondBlock
</div>
<h2>Memofrog knows how humans think.</h2>
<p>When you have an idea, a task, or just something you need to remember, you just want to capture it on the spot and have it ready when you need it. Quickly and easily, without fuss. That's what Memofrog is all about.</p>
<p>You can classify your memos by importance, or save them to your journal, or just keep them for reference, all with a single click. Use <span class="hashtag">#tags</span> to organize and share. And it's all Free!&nbsp;&nbsp; <a href="./frog?CReq=$regKey">Give it a try!</a></p>
<div id="footer" class="footer">$footerText</div>
</div>
</div>
EOT;

$body = Html::Tag('body', $body);

echo Page::RenderPage(true, $body);

exit(0);

function getRegForm()
{
    $pat = Content::GetScreenNamePattern();

    return
        '<form class="login_form" method="post" action="/frog">' .
        Html::Div('intro_reg_caption', 'Register for Free') .
        Html::Fieldset(
            Html::Input(Key::KEY_REGISTER_REAL_NAME, Key::KEY_REGISTER_REAL_NAME, '', 'text', 'Name', 'Your name', true, true, '', 'name') .
            Html::DivWithId(Key::KEY_VALIDATION_TARGET_REAL_NAME, 'validation', '') .
            Html::Input(Key::KEY_REGISTER_EMAIL, Key::KEY_REGISTER_EMAIL, '', 'text', 'Email', 'Your email address', true, false, '', 'email') .
            Html::DivWithId(Key::KEY_VALIDATION_TARGET_EMAIL, 'validation', '') .
            Html::Input(Key::KEY_REGISTER_SCREEN_NAME, Key::KEY_REGISTER_SCREEN_NAME, '', 'text', 'Screen Name (lower case, no spaces)', 'Choose a screen name', true, false, $pat, 'username') .
            Html::DivWithId(Key::KEY_VALIDATION_TARGET_SCREEN_NAME, 'validation', '') .
            Html::Input(Key::KEY_REGISTER_PASSWORD, Key::KEY_REGISTER_PASSWORD, '', 'password', 'Password', 'Enter a password', true, false, '', 'new-password') .
            Html::DivWithId(Key::KEY_VALIDATION_TARGET_PASSWORD, 'validation', '') .
            Html::HiddenInput(Key::KEY_CONTENT_REQ, ContentKey::CONTENT_KEY_POST_REGISTER_USER)
        ) .
        Html::SubmitButton('Start my free account', true) .
        '</form>' .
        Html::Script(Content::GetValidationScript(Validation::VALIDATION_TYPE_REGISTER, array(Key::KEY_REGISTER_REAL_NAME, Key::KEY_REGISTER_SCREEN_NAME, Key::KEY_REGISTER_EMAIL, Key::KEY_REGISTER_PASSWORD)));
}
function getListOfThings()
{
    return <<< EOT
<ul>
<li>Things to do</li>
<li>Plans &amp; Ideas</li>
<li>Tasks</li>
<li>Journaling</li>
<li>Places to go</li>
<li>Stuff to remember</li>
<li>More!</li>
</ul>
EOT;
}