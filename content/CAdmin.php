<?php

class CAdmin extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_ADMIN;
    }

    public function AuthLevel()
    {
        return LoginStatus::OK;
    }

    public function Render($userId)
    {
        $this->cacheable = self::CACHEABLE_ALWAYS;
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;

        $this->html =
            Html::Div('submenu', self::GetNavLink("&lt;&nbsp;Options", ContentKey::CONTENT_KEY_SETTINGS, 'link_button')) .
            Html::Heading('Admin') .
            Html::SubHeading('App Version ' . APP_VERSION) .
                Html::Div('big_list',
                    Html::LinkButton(Html::Div('big_list_item hoverable', 'Run Batch Processes'), ContentKey::CONTENT_KEY_BATCH) .
                    Html::LinkButton(Html::Div('big_list_item hoverable', 'Run Tests'), ContentKey::CONTENT_KEY_RUN_TESTS) .
                    Html::LinkButton(Html::Div('big_list_item hoverable', 'Data Check'), ContentKey::CONTENT_KEY_DATA_CHECK) .
                    Html::LinkButton(Html::Div('big_list_item hoverable', 'Security Check'), ContentKey::CONTENT_KEY_SECURITY_CHECK) .
                    Html::LinkButton(Html::Div('big_list_item hoverable', 'Users'), ContentKey::CONTENT_KEY_USERS) .
                    Html::LinkButton(Html::Div('big_list_item hoverable', 'SQL Ops'), ContentKey::CONTENT_KEY_SQL_OPS));
    }
}