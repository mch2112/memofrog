<?php

class CAccountDetails extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_ACCOUNT_DETAILS;
    }

    public function AuthLevel()
    {
        return LoginStatus::OK;
    }

    public function Render($userId)
    {
        $this->cacheable = self::CACHEABLE_NEVER;
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;
        $row = Database::QueryOneRow("SELECT users.screen_name AS screen_name, accounts.real_name AS real_name FROM users INNER JOIN accounts ON users.id=accounts.user_id WHERE users.id=$userId");
        $this->contentData = array('data' => array('acctScreenName' => $row['screen_name'], 'acctRealName' => $row['real_name']));
    }
}