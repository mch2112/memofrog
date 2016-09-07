<?php

class CLoginAs extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_LOGIN_AS;
    }

    public function AuthLevel()
    {
        return LoginStatus::ADMIN;
    }

    public function Render($userId)
    {
        $loginAsUserId = UserInput::Extract(Key::KEY_LOGIN_AS_USER_ID);

        if ($loginAsUserId > 0) {
            Session::ExecuteLogin($loginAsUserId, false);
        }
        $this->redirect = ContentKey::CONTENT_KEY_HOME;
    }
}