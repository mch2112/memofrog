<?php

class CNotifications extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_NOTIFICATIONS;
    }

    public function AuthLevel()
    {
        return LoginStatus::OK;
    }
    public function Render($userId)
    {
        $this->html =
            Html::Heading('Notifications') .
            Html::SubHeading('Coming Soon...');
    }
}