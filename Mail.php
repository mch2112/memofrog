<?php

require_once('lib/SendGrid/SendGrid_loader.php');

class Mail
{
    const API_USER = 'memofrog';
    const API_KEY = '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';
    const /** @noinspection SpellCheckingInspection */ PASSWORD = 'bg0TaOUJM9Vj';
    const SENDER_EMAIL = 'frog@memofrog.com';
    const SENDER_NAME = 'Memofrog';

    /**
     * @param $userId int
     * @param $notificationType int
     * @param $emailAddress string
     * @param $subject string
     * @param array $paragraphsHtml
     * @param array $paragraphsText
     * @return bool
     */
    public static function SendMail($userId, $notificationType, $emailAddress, $subject, array $paragraphsHtml, array $paragraphsText)
    {
        if ($userId === 0)
            $userId = Account::GetUserIdFromEmail($emailAddress);
        else if ($emailAddress === '')
            $emailAddress = Account::GetEmail($userId);

        $bodyHtml = Html::Tag('html', Html::Tag('head','').Html::Tag('body', self::CreateMailBodyHtml($paragraphsHtml)));
        $bodyText = self::CreateMailBodyText($paragraphsText);

        try {
            if (Session::IsProduction()) {
                /* @var $sendgrid SendGrid\SendGrid */
                $sendgrid = new SendGrid\SendGrid(self::API_USER, self::PASSWORD);
                /* @var $mail SendGrid\Mail */
                $mail = new SendGrid\Mail();

                $mail
                    ->addTo($emailAddress)
                    ->setFrom(self::SENDER_EMAIL)
                    ->setFromName(self::SENDER_NAME)
                    //->setBcc('sendgrid@memofrog.com')
                    ->setSubject($subject)
                    ->setHtml($bodyHtml)
                    ->setText($bodyText);

                $sendgrid->send($mail);
            }
            Database::ExecutePreparedStatement('INSERT INTO mail (user_id, notification_type, sent_to, subject, body) VALUES (?,?,?,?,?)', 'iisss', array($userId, $notificationType, $emailAddress, $subject, $bodyText));
        }
        catch (Exception $e) {
            return false;
        }
        return true;
    }
    public static function CreateMailBodyHtml(array $paragraphs)
    {
        $ret = '';
        foreach ($paragraphs as $p) {
            if (strlen($ret) === 0)
                $ret .= Html::TagWithStyle('p', 'font: normal 18px Arial; font-weight: bold; color: #006060;', $p);
            else
                $ret .= Html::TagWithStyle('p', 'font: normal 14px Arial;color:#004040;', $p);
        }
        return $ret;
    }
    public static function CreateMailBodyText(array $paragraphs)
    {
        return join("\n\n", $paragraphs);
    }
}