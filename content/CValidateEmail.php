<?php

class CValidateEmail extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_GEN_VALIDATION_TOKEN;
    }

    public function AuthLevel()
    {
        return LoginStatus::ANY;
    }

    public function Render($userId)
    {
        Token::GenToken($userId, Token::TOKEN_TYPE_VALIDATION);
        $this->redirect = ContentKey::CONTENT_KEY_NEEDS_VALIDATION;
    }
}