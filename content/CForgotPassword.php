<?php

class CForgotPassword extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_FORGOT_PASSWORD;
    }

    public function AuthLevel()
    {
        return LoginStatus::ANY;
    }

    public function Render($userId)
    {
        $this->cacheable = self::CACHEABLE_ALWAYS;
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;
        $this->needsEmailValSuppressed = true;

        $instructions = Html::Div('instructions',  Html::Tag('p',
                'Enter your email address or screen name. If there is an account with that address or name, we will send instructions on how to reset your password.'));

        $this->html =
            Html::Div('submenu',
                self::GetNavLink('&lt; Back', ContentKey::CONTENT_KEY_LOGIN, 'link_button')) .
            Html::Heading('Forgot Password') .
            $instructions .
            $this->renderLostPasswordPanel();
    }

    private function renderLostPasswordPanel()
    {
        $email_pattern = "(?!(^[.-].*|[^@]*[.-]@|.*\\.{2,}.*)|^.{254}.)([a-zA-Z0-9!#$%%&'*+\\/=?^_`{|}~.-]+@)(?!-.*|.*-\\.)([a-zA-Z0-9-]{1,63}\\.)+[a-zA-Z]{2,15}"; // note escaped '%' for sprintf
        $screen_name_pattern = "@?[a-zA-Z0-9]+";
        $pattern = "($email_pattern|$screen_name_pattern)";

        return Html::Form(
            Html::Fieldset(
                Html::Input(Key::KEY_FORGOT_PASSWORD_SCREEN_NAME_OR_EMAIL, Key::KEY_FORGOT_PASSWORD_SCREEN_NAME_OR_EMAIL, '', 'text', 'Screen Name or Email', 'Your screen name or email address', true, true, $pattern) .
                Html::HiddenInput(Key::KEY_CONTENT_REQ, ContentKey::CONTENT_KEY_POST_FORGOT_PASSWORD)) .
            Html::Fieldset(
                Html::Div('button_row',
                    Html::CancelButton(ContentKey::CONTENT_KEY_LOGIN) .
                    Html::SubmitButton('Send Instructions')))
            );
    }
}