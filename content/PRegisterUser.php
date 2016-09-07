<?php

class PRegisterUser extends Content
{
    const MIN_SCREEN_NAME_LENGTH = 4;
    const MAX_SCREEN_NAME_LENGTH = 30;

    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_POST_REGISTER_USER;
    }

    public function AuthLevel()
    {
        return LoginStatus::ANY;
    }

    public function Render($userId)
    {
        $this->needsEmailValSuppressed = true;

        $success = $this->processRegisterUserForm($alert);

        if (strlen($alert))
            self::$alert = $alert;

        if ($success)
            $this->redirect = ContentKey::CONTENT_KEY_HOME;
        else
            $this->redirect = ContentKey::CONTENT_KEY_REGISTER_USER;
    }

    private function processRegisterUserForm(&$alert)
    {
        $alert = '';
        $email = UserInput::Extract(Key::KEY_REGISTER_EMAIL, '', false);
        if (strlen($email) > 0) {
            $password = UserInput::Extract(Key::KEY_REGISTER_PASSWORD, '', false);
            if (Password::PasswordIsStrong($password, $passwordMsg)) {
                $screenName = UserInput::Extract(Key::KEY_REGISTER_SCREEN_NAME, '', false);
                if (Account::GetUserIdFromEmail($email) > 0) {
                    $alert = 'That email account is already used. Please select another one.';
                } else if (mb_strlen($screenName) < self::MIN_SCREEN_NAME_LENGTH || mb_strlen($screenName) > self::MAX_SCREEN_NAME_LENGTH) {
                    $alert = 'Please use between ' . strval(self::MIN_SCREEN_NAME_LENGTH) . ' and ' . strval(self::MAX_SCREEN_NAME_LENGTH) . ' characters.';
                } else  if (Account::GetUserIdFromScreenName($screenName) > 0) {
                    $alert = 'That screen name is already used. Please select another one.';
                } else {
                    $realName = UserInput::Extract(Key::KEY_REGISTER_REAL_NAME, '', false);
                    $hashedPassword = Account::HashPassword($password);
                    $userId = Controller::RegisterUser($email, $screenName, $realName, $hashedPassword, true);
                    Session::ExecuteLogin($userId);
                    UserInput::Clear(Key::KEY_REGISTER_EMAIL);
                    UserInput::Clear(Key::KEY_REGISTER_SCREEN_NAME);
                    UserInput::Clear(Key::KEY_REGISTER_REAL_NAME);
                    UserInput::Clear(Key::KEY_REGISTER_PASSWORD);
                    return true;
                }
            } else {
                $alert = $passwordMsg;
            }
        }
        return false;
    }
}