<?php

class CValidateEmailComplete extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_VALIDATE_EMAIL_COMPLETE;
    }

    public function AuthLevel()
    {
        return LoginStatus::ANY;
    }

    public function Render($userId)
    {
        $this->needsEmailValSuppressed = true;

        if (Token::ValidateEmail($userId, UserInput::Extract(Key::KEY_VALIDATION_KEY))) {
            Session::ExecuteLogin($userId);
            $email = Account::GetEmail();
            $this->html = Html::Heading('Email Validation') .
                Html::P("Thanks for verifying your email address. Your email account $email has been validated!") .
                Html::UL( Html::LI( self::GetNavLink('Let&apos;s Go!', ContentKey::CONTENT_KEY_HOME, 'link_button')) .
                    Html::LI(self::GetNavLink('Go to Account Details', ContentKey::CONTENT_KEY_ACCOUNT, 'link_button')) .
                    Html::LI(self::GetNavLink('Sign Out', ContentKey::CONTENT_KEY_LOGOUT, 'link_button')));
        } else {
            $this->html = Html::Heading('Email Validation Failed') .
                Html::P('Email validation has failed. Please ' . self::GetNavLink('try again.', ContentKey::CONTENT_KEY_GEN_VALIDATION_TOKEN, 'link_button')) .
                self::GetNavLink('Sign In', ContentKey::CONTENT_KEY_POST_LOGIN, 'link_button');
        }
    }
}