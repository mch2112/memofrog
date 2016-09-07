<?php

class PForgotPassword extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_POST_FORGOT_PASSWORD;
    }
    public function AuthLevel()
    {
        return LoginStatus::ANY;
    }
    public function Render($userId)
    {
        $screenNameOrEmail = UserInput::Extract(Key::KEY_FORGOT_PASSWORD_SCREEN_NAME_OR_EMAIL, '');

        if (strlen($screenNameOrEmail) > 0) {
            $userId = Account::GetUserIdFromEmail($screenNameOrEmail);

            if ($userId <= 0)
                $userId = Account::GetUserIdFromScreenName($screenNameOrEmail);

            if ($userId > 0)
                Notification::Notify($userId, Notification::NOTIFY_FORGOT_PASSWORD_INSTRUCTIONS);

            $this->html =
                Html::Div('submenu', self::GetNavLink('&lt; Back', ContentKey::CONTENT_KEY_LOGIN, 'link_button')) .
                Html::Heading('Password Reset Instructions Sent') .
                Html::P('Please check your email for instructions for resetting your password. If you don&apos;t see an email within a few minutes, your can ' . self::GetNavLink('try again', ContentKey::CONTENT_KEY_FORGOT_PASSWORD) .  ' or contact our customer support desk at <a href="mailto:support@memofrog.com">support@memofrog.com</a>.');
        }
        else {
            $this->redirect = ContentKey::CONTENT_KEY_FORGOT_PASSWORD;
        }
    }
}