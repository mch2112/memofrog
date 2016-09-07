<?php

class CResetPassword extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_RESET_PASSWORD;
    }

    public function AuthLevel()
    {
        return LoginStatus::ANY;
    }

    public function Render($userId)
    {
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;

        $newPassword = UserInput::Extract(Key::KEY_CHANGE_PASSWORD_NEW, '');
        $resetKey = UserInput::Extract(Key::KEY_PASSWORD_RESET_KEY, '', false);
        $userId = $this->getUserIdFromResetKey($resetKey);

        if ($userId <= 0) {
            $this->redirect = ContentKey::CONTENT_KEY_FORGOT_PASSWORD;
            self::$alert = "That password reset key is invalid.";
            return;
        }

        if (strlen($newPassword) > 0) {
            if (Password::PasswordIsStrong($newPassword, $passwordMsg)) {
                Account::changePasswordBypassCheck($userId, $newPassword);
                Database::ExecutePreparedStatement("DELETE FROM password_reset_tokens WHERE user_id=?", 'i', array($userId));
                UserInput::Clear(Key::KEY_PASSWORD_RESET_KEY);
                self::$alert = 'Password changed.';
                Session::ExecuteLogin($userId);
                $this->redirect = ContentKey::CONTENT_KEY_HOME;
                return;
            } else {
                self::$alert = $passwordMsg;
            }
        }
        $this->renderForm();
    }

    private function renderForm()
    {
        $this->html =
            Html::Div('submenu',
                self::GetNavLink('&lt; Back', ContentKey::CONTENT_KEY_LOGIN, 'link_button')) .
            Html::Heading('Reset Password') .
            Html::Form(
                Html::Fieldset(
                    Html::Input(Key::KEY_CHANGE_PASSWORD_NEW, Key::KEY_CHANGE_PASSWORD_NEW, '', 'password', 'New Password', 'Enter your new password', true, true, '', 'new-password') .
                    Html::HiddenInput(Key::KEY_CONTENT_REQ, ContentKey::CONTENT_KEY_RESET_PASSWORD)) .
                Html::Fieldset(
                    Html::Div('button_row',
                        Html::CancelButton(ContentKey::CONTENT_KEY_LOGIN) .
                        Html::SubmitButton('Reset Password'))));
    }

    /**
     * @param $resetKey
     * @return int
     */
    private function getUserIdFromResetKey($resetKey)
    {
        $userId = 0;
        Database::QueryCallback("SELECT * FROM password_reset_tokens WHERE token='$resetKey'", function ($row) use (&$userId) {
            $userId = (int)$row['user_id'];
        });
        return $userId;
    }
}