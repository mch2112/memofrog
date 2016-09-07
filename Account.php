<?php

class Account
{
    const MAX_LOGIN_ATTEMPTS = 30;

    /* @return int */
    public static function GetUserId()
    {
        return Session::ExtractSessionVal(Session::SESSION_KEY_USER_ID, 0, false);
    }

    /* @param int $userId
     * @return bool
     */
    public static function IsAdmin($userId = 0)
    {
        if ($userId <= 0)
            $userId = Account::GetUserId();

        if ($userId > 0)
            return boolval(Database::LookupValue('users', 'id', $userId,'i', 'admin', false));
        else
            return false;
    }

    /**
     * @param int $userId
     * @return bool
     */
    public static function IsNewbie($userId) {
        return Controller::GetMemoCount($userId, Bucket::BUCKET_NONE) < 30;
    }

    /**
     * @param int $userId
     * @return int
     */
    public static function GetTimeZoneOffset($userId)
    {
        return (int)Database::LookupValue('accounts', 'user_id', $userId, 'i', 'time_zone_offset', 0);
    }

    /**
     * @param int $userId
     * @param int $tzOffset
     */
    public static function SetTimeZoneOffset($userId, $tzOffset)
    {
        Database::ExecutePreparedStatement('UPDATE accounts SET time_zone_offset=? WHERE user_id=?', 'ii', array($tzOffset, $userId));
    }

    /* @param $userId int
     * @param $realName string
     * @param $screenName string
     */
    public static function UpdateAccountDetails($userId, $realName, $screenName)
    {
        $sql = 'UPDATE accounts SET real_name=? WHERE user_id=?';
        Database::ExecutePreparedStatement($sql, 'si', array($realName, $userId));
        $oldScreenName = self::GetScreenName($userId);
        if ($oldScreenName !== $screenName) {

            Database::ExecutePreparedStatement('UPDATE users SET screen_name=? WHERE id=?', 'si', array($screenName, $userId));

            $regEx = '/(^|\s)+@' . $oldScreenName . '($|[^a-zA-Z0-9_])/i';
            $replace = '$1@'.$screenName.'$2';

            Database::QueryCallback("SELECT memos.id AS memo_id, memos.memo_text AS memo_text FROM memos INNER JOIN direct_shares ON memos.id=direct_shares.memo_id WHERE direct_shares.user_id=$userId",
                function ($row) use ($userId, $regEx, $replace) {
                    $memoId = (int)$row['memo_id'];
                    $oldText = $row['memo_text'];
                    $newText = preg_replace($regEx, $replace, $oldText);
                    ControllerW::EditMemo($userId, $memoId, $newText, Controller::GetPrivateStatus($memoId));
                });
        }
    }

    public static function GetUserIdFromScreenName($screenName)
    {
        return Database::LookupValue('users', 'screen_name', $screenName, 's', 'id', 0);
    }

    public static function GetScreenName($userId = 0)
    {
        if ($userId <= 0)
            return '';
        else
            return Database::LookupValue('users', 'id', $userId, 'i', 'screen_name', '');
    }
    public static function GetRealName($userId = 0)
    {
        if ($userId <= 0)
            return '';
        else
            return Database::LookupValue('accounts', 'user_id', $userId, 'i', 'real_name', '');
    }

    /**
     * @param string $email
     * @return int
     */
    public static function GetUserIdFromEmail($email)
    {
        return (int)Database::LookupValue('accounts', 'email', $email, 's', 'user_id', -1);
    }

    /**
     * @param int $user_id
     * @return string
     */
    public static function GetEmail($user_id = 0)
    {
        if ($user_id <= 0)
            $user_id = self::GetUserId();

        if ($user_id <= 0)
            return '';

        return Database::LookupValue('accounts', 'user_id', $user_id, 'i', 'email', '');
    }

    /**
     * @param $userId int
     * @return int
     */
    public static function GetDefaultBucket($userId) {
        return self::IsNewbie($userId) ? Bucket::BUCKET_EVERYTHING : Bucket::BUCKET_HOT_LIST;
    }

    /**
     * @param $userId int
     * @param $success bool
     */
    public static function RecordLogin($userId, $success)
    {
        if ($success)
            Database::ExecuteQuery("UPDATE accounts SET failed_logins=0 WHERE user_id=$userId");
        else
            Database::ExecuteQuery("UPDATE accounts SET failed_logins=failed_logins + 1 WHERE user_id=$userId");
    }

    /* @param $userId int
     * @param $password string
     * @return bool
     */
    public static function PasswordOk($userId, $password)
    {
        /*if (!Session::IsProduction())
            return true;*/

        $password_hash = Database::LookupValue('accounts', 'user_id', $userId, 'i', 'password', '');
        return password_verify($password, $password_hash);
    }

    /**
     * @param int $userId
     * @param string $oldPassword
     * @param string $newPassword
     * @return bool
     */
    public static function ChangePassword($userId, $oldPassword, $newPassword)
    {
        if (self::PasswordOk($userId, $oldPassword)) {
            self::changePasswordBypassCheck($userId, $newPassword);
            Token::DeleteTokensForUser($userId, Token::TOKEN_TYPE_AUTHENTICATION);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $userId
     * @param string $newPassword
     */
    public static function changePasswordBypassCheck($userId, $newPassword)
    {
        $hashedPassword = self::HashPassword($newPassword);
        Database::ExecutePreparedStatement('UPDATE accounts SET password=? WHERE user_id=?;', 'si', array($hashedPassword, $userId));
    }

    /**
     * @param string $password
     * @return bool|string
     */
    public static function HashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}