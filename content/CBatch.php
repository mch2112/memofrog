<?php

class CBatch extends Content
{
    public function AuthLevel()
    {
        return LoginStatus::ADMIN;
    }
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_BATCH;
    }
    public function Render($userId)
    {
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;

        $this->html =
            Html::Div('submenu', self::GetNavLink("&lt;&nbsp;Admin", ContentKey::CONTENT_KEY_ADMIN, 'link_button')) .
            Html::Heading('Batch Processes').
            Batch::RunAll();
    }
}