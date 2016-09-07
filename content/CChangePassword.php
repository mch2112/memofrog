<?php

class CChangePassword extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_CHANGE_PASSWORD;
    }

    public function AuthLevel()
    {
        return LoginStatus::OK;
    }

    public function Render($userId)
    {
        $this->cacheable = self::CACHEABLE_ALWAYS;
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;
        $this->needsEmailValSuppressed = true;

        $this->html =
            Html::Div('submenu',
                self::GetNavLink('&lt; Back', ContentKey::CONTENT_KEY_ACCOUNT, 'link_button')) .
            Html::Heading('Change Password') .
            Html::Form(
                Html::Fieldset(
                    Html::Input(Key::KEY_CHANGE_PASSWORD_OLD, Key::KEY_CHANGE_PASSWORD_OLD, '', 'password', 'Verify Old Password', 'Enter your old password', true, true, '', 'old-password') .
                    Html::DivWithId(Key::KEY_VALIDATION_TARGET_OLD_PASSWORD, 'validation', '') .
                    Html::Input(Key::KEY_CHANGE_PASSWORD_NEW, Key::KEY_CHANGE_PASSWORD_NEW, '', 'password', 'New Password', 'Enter your new password', true, false, '', 'new-password') .
                    Html::DivWithId(Key::KEY_VALIDATION_TARGET_NEW_PASSWORD, 'validation', '') .
                    Html::HiddenInput(Key::KEY_CONTENT_REQ, ContentKey::CONTENT_KEY_POST_CHANGE_PASSWORD)) .
                Html::Fieldset(
                    Html::Div('button_row',
                        Html::CancelButton(ContentKey::CONTENT_KEY_ACCOUNT) .
                        Html::SubmitButton('Change Password', true)))
                );

        $this->script = self::GetValidationScript(Validation::VALIDATION_TYPE_CHANGE_PASSWORD, array(Key::KEY_CHANGE_PASSWORD_OLD, Key::KEY_CHANGE_PASSWORD_NEW));
    }
}