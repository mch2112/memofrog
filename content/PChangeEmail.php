<?php

class PChangeEmail extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_POST_CHANGE_EMAIL;
    }

    public function AuthLevel()
    {
        return LoginStatus::OK;
    }

    public function Render($userId)
    {
        $changedEmail = UserInput::Extract(Key::KEY_ACCOUNT_DETAILS_EMAIL);

        if (!is_null($changedEmail) && strlen($changedEmail) > 0) {
            $oldEmail = Database::LookupValue('accounts', 'user_id', $userId, 'i', 'email', '');
            if ($changedEmail === $oldEmail)
                self::$alert = 'That&apos;s the same email address!';
            else if (Account::GetUserIdFromEmail($changedEmail) > 0)
                self::$alert = "The email address '$changedEmail' is already in use.";
            else {
                Token::GenToken($userId, Token::TOKEN_TYPE_VALIDATION, $changedEmail);
                $this->redirect = ContentKey::CONTENT_KEY_NEEDS_VALIDATION;
                return;
            }
        }
        $this->redirect = ContentKey::CONTENT_KEY_CHANGE_EMAIL;
    }
}