<?php

class PChangePassword extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_POST_CHANGE_PASSWORD;
    }

    public function AuthLevel()
    {
        return LoginStatus::OK;
    }

    public function Render($userId)
    {
        $this->cacheable = self::CACHEABLE_NEVER;

        $oldPassword = UserInput::Extract(Key::KEY_CHANGE_PASSWORD_OLD, '');
        $newPassword = UserInput::Extract(Key::KEY_CHANGE_PASSWORD_NEW, '');

        if ((strlen($oldPassword) > 0) && (strlen($newPassword) > 0)) {
            if (Account::PasswordOk($userId, $oldPassword)) {
                if (Password::PasswordIsStrong($newPassword, $passwordMsg)) {
                    if ($oldPassword === $newPassword) {
                        self::$alert = 'That&apos;s the same password!';
                    } else if (Account::ChangePassword($userId, $oldPassword, $newPassword)) {
                        self::$alert = 'Password changed.';
                        $this->redirect = ContentKey::CONTENT_KEY_ACCOUNT;
                        return;
                    } else {
                        self::$alert = 'Error changing password.';
                    }
                } else {
                    self::$alert = $passwordMsg;
                }
            } else {
                self::$alert = 'That old password is incorrect.';
            }
        }
        $this->redirect = ContentKey::CONTENT_KEY_CHANGE_PASSWORD;
    }
}