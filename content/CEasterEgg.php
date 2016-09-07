<?php

class CEasterEgg extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_EASTER_EGG;
    }

    public function AuthLevel()
    {
        return LoginStatus::ANY;
    }
    public function Render($userId)
    {
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;
        $this->needsEmailValSuppressed = true;

        $html = '';

        $keys = array(1, 2, 3, 4, 3, 2, 1, 2, 1, 3, 4, 1, 3, 4, 3, 2, 1, 4, 1, 3, 4, 2, 1, 2, 1, 4, 1, 4, 2, 1, 2, 4, 3, 2, 1, 2, 1, 4, 1, 4, 2, 1, 3, 2, 4, 3, 4, 2, 1, 2, 1, 4, 1, 4, 2);

        for ($i = 0; $i < 2; $i++)
            foreach ($keys as $j)
                for ($k = 0; $k < ($j === 1 ? 1 : 3); ++$k)
                    $html .= Html::Img("/images/Easter/hamster$j.gif");

        $html = Html::Div('easter_egg', $html);

        $this->html .= $html.'<audio autoplay loop><source src="/images/Easter/dodo.mp3" type="audio/mpeg"></audio>';
    }
}