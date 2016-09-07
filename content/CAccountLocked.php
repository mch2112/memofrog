<?php

class CAccountLocked extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_ACCOUNT_LOCKED;
    }

    public function AuthLevel()
    {
        return LoginStatus::ANY;
    }
    public function Render($userId)
    {
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;
        $this->needsEmailValSuppressed = true;

        $this->html = Html::Heading('Account Locked') .
                      Html::P('Your account has been locked. You will need to '.self::GetNavLink('reset your password', ContentKey::CONTENT_KEY_RESET_PASSWORD).'.');
    }
}