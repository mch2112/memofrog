<?php

class CNeedsValidation extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_NEEDS_VALIDATION;
    }

    public function AuthLevel()
    {
        return LoginStatus::OK;
    }

    public function Render($userId)
    {
        $this->needsEmailValSuppressed = true;

        if (!Token::UserHasToken($userId, Token::TOKEN_TYPE_VALIDATION))
            Token::GenToken($userId, Token::TOKEN_TYPE_VALIDATION);

        $token = Token::GetTokenByTypeAndUserId(Token::TOKEN_TYPE_VALIDATION, $userId);
        $email = $token->data;

        /* @var $sendTime DateTime */
        $sendTime = Notification::GetTimeOfNotification($userId, Notification::NOTIFY_EMAIL_VALIDATION);

        if (is_null($sendTime)) {
            $this->redirect = ContentKey::CONTENT_KEY_GEN_VALIDATION_TOKEN;
            return;
        }

        $now = new DateTime();
        $age = $sendTime->diff($now)->i;
        $old = $age > 15;   // new if it's been less than 15 minutes
        $canResend = $age > 2; // no more than one per two minutes minute
        $sendTimeString = $sendTime->format(Util::DATE_TIME_FORMAT);

        $html =
            Html::Heading('You&apos;ve got mail!') .
            Html::P("A registration email has been sent to <strong>$email</strong> at <strong><displaydate>$sendTimeString</displaydate></strong>. " .
                ($canResend ? 'Memofrog can resend this registration email if needed. ' : '') .
                'Please open this email and click the link and you&apos;ll be ready to go!') .
            Html::P('If you don&apos;t see this email in your inbox' . ($old ? '' : ' within a few minutes') . ', look for it in your junk mail folder. If you find it there, please mark the email as &quot;Not Junk.&quot; Thank you for validating your account!');

        $options =
            Html::LI(Html::LinkButton('Home', ContentKey::CONTENT_KEY_HOME)) .
            ($canResend ? Html::LI(Html::LinkButton('Resend validation email', ContentKey::CONTENT_KEY_GEN_VALIDATION_TOKEN)) : '') .
            Html::LI(Html::LinkButton('View account details', ContentKey::CONTENT_KEY_ACCOUNT)) .
            Html::LI(Html::LinkButton('Change email address', ContentKey::CONTENT_KEY_CHANGE_EMAIL)) .
            Html::LI(self::GetJavaScriptLink('Sign out', "logout(); return false", "link_button"));

        $this->html = $html . Html::UL( $options);
    }
}