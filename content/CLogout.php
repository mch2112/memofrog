<?php

class CLogout extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_LOGOUT;
    }

    public function AuthLevel()
    {
        return LoginStatus::ANY;
    }

    public function Render($userId)
    {
        Session::Logout();
    }
}