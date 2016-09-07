<?php

class PAccountDetails extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_POST_ACCOUNT_DETAILS;
    }

    public function AuthLevel()
    {
        return LoginStatus::OK;
    }

    public function Render($userId)
    {
        $screenName = UserInput::Extract(Key::KEY_ACCOUNT_DETAILS_SCREEN_NAME);
        $realName = UserInput::Extract(Key::KEY_ACCOUNT_DETAILS_REAL_NAME);
        $ok = false;
        if (strlen($screenName) > 0 && strlen($realName)) {
            $userIdForScreenName = Account::GetUserIdFromScreenName($screenName);
            if ($userIdForScreenName > 0 && $userIdForScreenName !== $userId) {
                self::$alert = "The screen name '$screenName' is already in use.";
                $this->redirect = ContentKey::CONTENT_KEY_ACCOUNT_DETAILS;
                $ok = true;
            } else {
                Account::UpdateAccountDetails($userId, $realName, $screenName);
                self::$alert = 'Account details updated.';
                $this->redirect = ContentKey::CONTENT_KEY_ACCOUNT;
                $ok = true;
            }
        }
        if (!$ok)
            $this->setError(ErrorCode::ERROR_SAVING_ACCOUNT_DETAILS);
    }
}