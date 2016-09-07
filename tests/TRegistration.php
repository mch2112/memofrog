<?php

class TRegistration extends Test
{
    function __construct()
    {
        $this->name = 'Registration';
    }

    public function Run()
    {
        $screenName = $this->GetUniqueIdentifier('screen_name');
        $email =  $this->GetUniqueIdentifier('email') . '@memofrog.com';
        $password = $this->GetUniqueIdentifier('pwd');
        $hashedPassword = Account::HashPassword($password);
        $realName = $this->GetUniqueIdentifier('firstname') . ' ' . $this->GetUniqueIdentifier('lastname');

        $userId = Controller::RegisterUser($email, $screenName, $realName, $hashedPassword, true);
        Notification::Notify($userId, Notification::NOTIFY_EMAIL_VALIDATION);

        $this->assertIsTrue('A', Account::IsNewbie($userId), 'Newbie account not newbie.');
        $this->assertIsTrue('B', Account::GetEmail($userId) === $email, 'New account email mismatch');
        $this->assertIsFalse('C', Account::IsAdmin($userId), 'Regular account qualifies as admin');
        $this->assertIsFalse('D', Session::IsValidated($userId), 'Unvalidated account is marked validated.');
        $this->assertIsTrue('E', Account::PasswordOk($userId, $password), 'Password failed.');

        Batch::RunAll();

        $notificationType = Notification::NOTIFY_EMAIL_VALIDATION;

        $this->assertIsTrue('F', Database::RecordExists("mail WHERE user_id=$userId AND notification_type=$notificationType"), "Email validation not sent for user $userId.");

        $row = Database::QueryOneRow("SELECT body FROM mail WHERE user_id=$userId AND notification_type=$notificationType ORDER BY sent_on DESC");
        $mailText = $row['body'];

        $token = Token::GetTokenByTypeAndUserId(Token::TOKEN_TYPE_VALIDATION, $userId);
        $this->assertIsTrue('G', mb_strpos($mailText, $token->key) !== false, "Validation token key mismatch for user $userId.");

        $this->assertIsTrue('H', Controller::GetUserAuthStatus($userId) === LoginStatus::OK, 'User should be registered.');
        $this->assertIsFalse('I', Session::IsValidated($userId), 'User should not be validated.');

        Token::ValidateEmail($userId, $token->key);

        $this->assertIsTrue('J', Session::IsValidated($userId), 'User should be validated.');

        $this->finished = true;
    }

    public function OkForProduction()
    {
        return false;
    }
}
