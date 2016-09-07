<?php

class PLogin extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_POST_LOGIN;
    }

    public function AuthLevel()
    {
        return LoginStatus::ANY;
    }

    public function Render($userId)
    {
        $this->cacheable = self::CACHEABLE_NEVER;
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;
        $this->needsEmailValSuppressed = true;

        $passwordSent = strlen(UserInput::Extract(Key::KEY_LOGIN_PASSWORD, '', false)) > 0;

        if (!Session::IsLoggedIn())
            Session::TryLogin();

        if (Session::IsLoggedIn()) {
            self::$response[Key::KEY_CLEAR_FILTERS] = true;
            $this->redirect = ContentKey::CONTENT_KEY_HOME;
        } else {
            if ($passwordSent && Session::IsLoginError()) {
                switch (Session::GetLoginError()) {
                    case LoginError::EMAIL_OR_SCREEN_NAME_NOT_FOUND:
                        /** @noinspection SpellCheckingInspection */
                        self::$alert = 'That Memofrog account doesn&apos;t exist. Enter a different account or ' . Content::GetNavLink('get a new one.', ContentKey::CONTENT_KEY_REGISTER_USER);
                        break;
                    case LoginError::PASSWORD_MISMATCH:
                        self::$alert = 'That password is incorrect.';
                        break;
                    case LoginError::ACCOUNT_LOCKED:
                        $this->redirect = ContentKey::CONTENT_KEY_ACCOUNT_LOCKED;
                        return;
                }
                Session::SetLoginError(LoginError::NONE);
            }
            $this->redirect = ContentKey::CONTENT_KEY_LOGIN;
        }
    }
}