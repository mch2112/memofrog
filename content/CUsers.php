<?php

class CUsers extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_USERS;
    }

    public function AuthLevel()
    {
        return LoginStatus::ADMIN;
    }

    public function Render($userId)
    {
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;

        $valUserId = UserInput::Extract(Key::KEY_ADMIN_VALIDATE_USER_ID, 0);
        if ($valUserId > 0)
            Token::ValidateUser($valUserId);

        $sql = <<< EOT
SELECT
    users.id AS id,
    users.screen_name AS screen_name,
    accounts.email AS email,
    accounts.real_name AS real_name,
    COALESCE(accounts.last_login, DATE_SUB(NOW(),INTERVAL 1 YEAR)) AS last_login,
    accounts.email_validated AS email_validated,
    accounts.created AS created,
    COUNT(memos.id) AS memo_count,
    MAX(memos.created) AS last_memo_date
FROM
    users
        INNER JOIN
    accounts ON users.id = accounts.user_id
        LEFT JOIN
    memos ON accounts.user_id = memos.user_id
WHERE
    users.screen_name NOT LIKE 'screen_name%'
GROUP BY accounts.user_id
ORDER BY last_memo_date DESC
EOT;

        $html = Html::Div('submenu', Html::LinkButton('&lt;&nbsp;Admin', ContentKey::CONTENT_KEY_ADMIN)) .
                Html::Heading('Users');

        Database::QueryCallback($sql, function ($row) use (&$html) {
            $userId = $row['id'];
            $html .= self::GetNavLinkWithArgs(Html::Span('screen_name', '@'. $row['screen_name']), array(Key::KEY_LOGIN_AS_USER_ID => $userId, Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_LOGIN_AS)) . " ($userId)";
            if (!$row['email_validated'])
                $html .= View::RenderInfoWidget('Email Validation', self::GetNavLinkWithArgs('NOT ACTIVATED', array(Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_USERS, Key::KEY_ADMIN_VALIDATE_USER_ID => $userId), 'link_button'));
            $html .= View::RenderInfoWidget('Info', $row['real_name'] . ' ('. $row['email'] .')');
            $html .= View::RenderInfoWidget('Last Login', Html::Tag('displaydate', $row['last_login']));
            $html .= View::RenderInfoWidget('Memo Count', $row['memo_count'] . ': Latest on '. Html::Tag('displaydate', $row['last_memo_date']));
            $html .= View::RenderInfoWidget('Created', Html::Tag('displaydate', $row['created']));
            $html .= Html::HR();
        });
        $this->html = $html;
    }
}