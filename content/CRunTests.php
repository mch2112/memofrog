<?php

class CRunTests extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_RUN_TESTS;
    }
    public function AuthLevel()
    {
        return LoginStatus::ADMIN;
    }

    /**
     * @param int $userId
     */
    public function Render($userId)
    {
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;

        $html =
            Html::Div('submenu', Html::LinkButton('&lt;&nbsp;Admin', ContentKey::CONTENT_KEY_ADMIN)) .
            Html::Heading('Tests') .
            Html::Tag('displaydate', Util::GetNowAsString()) . Html::Br() .
            HTML::HR() .
            View::RenderInfoWidget('Production', Session::IsProduction() ? 'Yes' : 'No') .
            HTML::HR();

        $startTime = microtime(true);

        Test::EnumerateTests(true, function($t) use (&$html) {
            /* @var $t Test */
            $html .= View::RenderInfoWidget($t->Name(), $t->Result());
        });

        $endTime = microtime(true);
        $diff = $endTime - $startTime;

        $html .=
            Html::HR() .
            "Elapsed Time: $diff seconds" .
            Html::HR() .
            Html::P(Html::LinkButton('Re-run tests', ContentKey::CONTENT_KEY_RUN_TESTS)) .
            Html::P(Html::LinkButton('Data Check', ContentKey::CONTENT_KEY_DATA_CHECK)) .
            Html::P(Html::LinkButton('Security Check', ContentKey::CONTENT_KEY_SECURITY_CHECK));

        $this->html = $html;
    }
}