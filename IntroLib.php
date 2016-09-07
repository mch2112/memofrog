<?php

class IntroLib
{
    public static function RenderMenu($className)
    {
        $registerContentReq = ContentKey::CONTENT_KEY_REGISTER_USER;
        $loginContentReq = ContentKey::CONTENT_KEY_LOGIN;

        if (Session::IsMobile())
            return <<< EOT
<div class="$className">
<a href="https://www.facebook.com/memofrog" target="_blank">Facebook</a>
<a href="https://twitter.com/Memo_Frog" target="_blank">Twitter</a>
<a href="/frog?CReq=$registerContentReq">Register</a>
<a href="/frog?CReq=$loginContentReq">Sign In</a>
</div>
EOT;
        else
            return <<< EOT
<div class="$className">
<a href="https://www.facebook.com/memofrog" target="_blank">Memofrog on Facebook</a>
<a href="https://twitter.com/Memo_Frog" target="_blank">Memofrog on Twitter</a>
<a href="/frog?CReq=$registerContentReq">Register</a>
<a href="/frog?CReq=$loginContentReq">Sign In</a>
</div>
EOT;
    }

    /*
    * @return string
    */
    public static function GetFooterText()
    {
        /** @noinspection HtmlUnknownTarget */
        return sprintf('&#x00a9;2015 - 2016 Memofrog. <a href="/frog?CReq=%d">Privacy Policy</a> | <a href="/frog?CReq=%d">Terms of Service</a>', ContentKey::CONTENT_KEY_PRIVACY, ContentKey::CONTENT_KEY_TERMS_OF_SERVICE);
    }
}