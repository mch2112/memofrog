<?php

class CSettings extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_SETTINGS;
    }

    public function AuthLevel()
    {
        return LoginStatus::OK;
    }

    /**
     * @param int $userId
     */
    public function Render($userId)
    {
        $this->cacheable = self::CACHEABLE_NEVER;
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;

        $this->html = Html::Heading('Account Details') .
            Html::Div('big_list',
                Html::LinkButton(Html::Div('big_list_item hoverable', 'Account Options'), ContentKey::CONTENT_KEY_ACCOUNT) .
                Html::LinkButton(Html::Div('big_list_item hoverable', 'Notifications'), ContentKey::CONTENT_KEY_NOTIFICATIONS) .
                Html::LinkButton(Html::Div('big_list_item hoverable', 'Help'), ContentKey::CONTENT_KEY_HELP) .
                self::GetJavaScriptLink(Html::Div('big_list_item hoverable', 'Sign Out'), "logout();return false;", "link_button"));
        if (Account::IsAdmin())
            $this->html .=
                Html::Br() .
                Html::Div('big_list',
                    Html::LinkButton(Html::Div('big_list_item hoverable', 'Admin Options'), ContentKey::CONTENT_KEY_ADMIN));

        $this->html .= Html::DivWithId('version_info', 'footer', '');
        $this->script = 'document.getElementById("version_info").innerHTML = "&#x00a9;2015 - 2016 Memofrog. Software version " + session.appVersion;';
    }
}