<?php

class CSqlOps extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_SQL_OPS;
    }

    public function AuthLevel()
    {
        return LoginStatus::ADMIN;
    }

    public function Render($userId)
    {
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;

        $html = Html::Div('submenu', Html::LinkButton('&lt;&nbsp;Admin', ContentKey::CONTENT_KEY_ADMIN)) .
            Html::Heading('SQL Ops');

        $html .= Html::P("Ready...");

        $res = SqlOps::DoOps();

        if (strlen($res) > 0)
            $html .= Html::P($res . Html::Br() . Html::Br() . 'Done!');
        else
            $html .= Html::P('Nothing to do.');

        $this->html = $html;
    }

}
