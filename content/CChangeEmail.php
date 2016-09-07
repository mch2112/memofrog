<?php

class CChangeEmail extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_CHANGE_EMAIL;
    }

    public function AuthLevel()
    {
        return LoginStatus::OK;
    }

    public function Render($userId)
    {
        $this->cacheable = self::CACHEABLE_NEVER;
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;
        $this->needsEmailValSuppressed = true;

        $email = Database::LookupValue('accounts', 'user_id', $userId, 'i', 'email', '');
        $this->contentData = array('data' => array(Key::KEY_ACCOUNT_DETAILS_EMAIL => $email));
    }
}