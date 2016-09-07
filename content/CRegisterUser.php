<?php

class CRegisterUser extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_REGISTER_USER;
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
        $this->html = $this->renderRegisterUserForm();
    }

    private function renderRegisterUserForm()
    {
        $instructions = $this->renderInstructions('login_notes',
            'Memofrog will never sell your personal information to third parties. See more at our ' .
                self::GetNavLink('Privacy Policy', ContentKey::CONTENT_KEY_PRIVACY) . ' and ' . self::GetNavLink('Terms of Service', ContentKey::CONTENT_KEY_TERMS_OF_SERVICE) . '.');

        $pat = $this->GetScreenNamePattern();

        $email = UserInput::Extract(Key::KEY_REGISTER_EMAIL);
        $screenName = UserInput::Extract(Key::KEY_REGISTER_SCREEN_NAME);
        $realName = UserInput::Extract(Key::KEY_REGISTER_REAL_NAME);

        $html =
            Html::Div('submenu',
                self::GetJavaScriptLink('&lt; Back', "logout(); return false(); ", 'link_button') .
                Html::Div('spacer', '') .
                self::GetNavLink('Sign In', ContentKey::CONTENT_KEY_LOGIN, 'link_button')) .
            $instructions .
            Html::Heading("Create a free account") .
            $this->GetRegForm($email, $screenName, $pat, $realName);

        $this->script = self::GetValidationScript(Validation::VALIDATION_TYPE_REGISTER, array(Key::KEY_REGISTER_REAL_NAME, Key::KEY_REGISTER_SCREEN_NAME, Key::KEY_REGISTER_EMAIL, Key::KEY_REGISTER_PASSWORD));

        return $html;
    }

    /**
     * @param $email string
     * @param $screenName string
     * @param $pat string
     * @param $realName string
     * @return string
     */
    public static function GetRegForm($email, $screenName, $pat, $realName)
    {
        return Html::Form(
            Html::Fieldset(
                Html::Input(Key::KEY_REGISTER_REAL_NAME, Key::KEY_REGISTER_REAL_NAME, $realName, 'text', 'Name', 'Your name', true, true, '', 'name') .
                Html::DivWithId(Key::KEY_VALIDATION_TARGET_REAL_NAME, 'validation', '') .
                Html::Input(Key::KEY_REGISTER_EMAIL, Key::KEY_REGISTER_EMAIL, $email, 'text', 'Email', 'Your email address', true, false, '', 'email') .
                Html::DivWithId(Key::KEY_VALIDATION_TARGET_EMAIL, 'validation', '') .
                Html::Input(Key::KEY_REGISTER_SCREEN_NAME, Key::KEY_REGISTER_SCREEN_NAME, $screenName, 'text', 'Screen Name (lower case, no spaces)', 'Choose a screen name', true, false, $pat, 'username') .
                Html::DivWithId(Key::KEY_VALIDATION_TARGET_SCREEN_NAME, 'validation', '') .
                Html::Input(Key::KEY_REGISTER_PASSWORD, Key::KEY_REGISTER_PASSWORD, '', 'password', 'Password', 'Enter a password', true, false, '', 'new-password') .
                Html::DivWithId(Key::KEY_VALIDATION_TARGET_PASSWORD, 'validation', '') .
                Html::HiddenInput(Key::KEY_CONTENT_REQ, ContentKey::CONTENT_KEY_POST_REGISTER_USER) .
                Html::Div('button_row',
                    self::GetJavaScriptLink("Cancel", "logout(); return false;", "standard_button cancel") .
                    Html::SubmitButton(Session::IsMobile() ? 'Register' : 'Start My Free Account'))
            )
        );
    }
}