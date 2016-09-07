<?php

class Session
{
    const SESSION_KEY_EXCEPTION = 'session_key_exception';
    const SESSION_KEY_USER_ID = 'session_user_id';
    const SESSION_KEY_ALERT = 'alert';
    const SESSION_KEY_EMAIL_VALIDATED = 'email_validated';
    const SESSION_KEY_LOGIN_ERROR = 'login_error';

    private static $isMobile = null;
    private static $isTouch = null;

    public static function DoSession()
    {
        session_start();

        UserInput::StoreInput();

        if (UserInput::UserSentData()) {
            Session::Refresh();
            return 0;
        } else {
            return Account::GetUserId();
        }
    }

    public static function DoAjaxSession($redirectIfNotLoggedIn)
    {
        session_start();

        UserInput::StoreInput();

        if (self::IsLoggedIn())
            $userId = Account::GetUserId();
        else
            $userId = self::loginWithAuthCookie();

        if ($userId < 0 && $redirectIfNotLoggedIn) {
            echo json_encode(
                array(
                    Key::KEY_REDIRECT => true,
                    Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_LOGIN));
            exit(0);
        } else {
            return $userId;
        }
    }

    public static function Refresh()
    {
        header('location: /frog');
        die();
    }

    public static function IsProduction()
    {
        return
            !isset($_SERVER['SERVER_SOFTWARE']) ||
            ((strpos($_SERVER['SERVER_SOFTWARE'], 'Apache/2.4.9 (Win64) PHP/5.5.12') === false) &&
                (strpos($_SERVER['DOCUMENT_ROOT'], '/MAMP/') === false));
    }

    public static function Assert($predicate, $message)
    {
        if (!self::IsProduction() && !$predicate) {
            Util::LogTextFile('errors.txt', $message);
            self::SetSessionVal(Session::SESSION_KEY_ALERT, $message);
        }
    }

    public static function SetSessionVal($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function CopySessionValue($from, $to, $clear)
    {
        if (isset($_SESSION[$from])) {
            $_SESSION[$to] = $_SESSION[$from];
            if ($clear)
                unset($_SESSION[$from]);
        }
    }

    /* @param $key string
     * @param $default
     * @param $clear bool
     * @return
     */
    public static function ExtractSessionVal($key, $default = null, $clear = true)
    {
        if (isset($_SESSION[$key])) {
            $val = $_SESSION[$key];
            if ($clear)
                unset($_SESSION[$key]);
            return $val;
        } else {
            return $default;
        }
    }

    /* @param $key string
     * @return bool
     */
    public static function IsSessionKeySet($key)
    {
        return isset($_SESSION[$key]);
    }

    /* @param $errorCode int
     * @param $errorContentId int
     */
    public static function SetError($errorCode, $errorContentId)
    {
        self::SetSessionVal(Key::KEY_ERROR_CODE, $errorCode);
        self::SetSessionVal(Key::KEY_ERROR_CONTENT_ID, $errorContentId);
    }

    public static function TryLogin()
    {
        // SEE IF CREDENTIALS ARE POSTED
        $screenNameOrEmail = UserInput::Extract(Key::KEY_LOGIN_SCREEN_NAME_OR_EMAIL, null, false);
        if (!is_null($screenNameOrEmail)) {
            $password = UserInput::Extract(Key::KEY_LOGIN_PASSWORD);
            if (!is_null($password)) {
                $rememberMe = UserInput::Extract(Key::KEY_LOGIN_REMEMBER_ME, true);
                return self::login($screenNameOrEmail, $password, $rememberMe);
            }
        }
        // OR IF WE HAVE AN AUTH COOKIE
        return self::loginWithAuthCookie();
    }

    public static function Logout()
    {
        Token::ClearAuthToken();
        self::SetSessionVal(self::SESSION_KEY_USER_ID, 0);
        self::SetSessionVal(self::SESSION_KEY_EMAIL_VALIDATED, false);
        self::SetLoginError(LoginError::NONE);
    }

    public static function IsLoggedIn()
    {
        return Session::ExtractSessionVal(Session::SESSION_KEY_USER_ID, 0, false) > 0;
    }

    public static function IsValidated($userId = 0)
    {
        if ($userId > 0) {
            return boolval(Database::LookupValue('accounts', 'user_id', $userId, 'i', 'email_validated', false));
        } else {
            $val = self::ExtractSessionVal(self::SESSION_KEY_EMAIL_VALIDATED, false, false);
            if ($val)
                return $val;

            if ($userId <= 0)
                $userId = Account::GetUserId();

            if ($userId <= 0)
                return false;

            $val = boolval(Database::LookupValue('accounts', 'user_id', $userId, 'i', 'email_validated', false));
            if ($val)
                self::SetSessionVal(self::SESSION_KEY_EMAIL_VALIDATED, $val);
            return $val;
        }
    }

    public static function IsLoginError()
    {
        return self::getLoginError() < LoginError::NONE;
    }

    public static function IsMobile()
    {
        if (is_null(self::$isMobile))
            self::setTouchMobile();
        return self::$isMobile;
    }

    public static function IsTouch()
    {
        if (is_null(self::$isTouch))
            self::setTouchMobile();
        return self::$isTouch;
    }
    private static function setTouchMobile() {
        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
        $isAndroid = boolval(preg_match('/android/', $ua));
        self::$isTouch = boolval(preg_match('/mobile|iphone|ipod|mobile safari|android|mini|ipad|touch/', $ua));
        self::$isMobile = self::$isTouch && (($isAndroid && boolval(preg_match('/mobile/', $ua))) || boolval(preg_match('/phone|ipod|blackberry|mobile safari/', $ua)));
    }
    public static function SetAlert($text)
    {
        $_SESSION[Session::SESSION_KEY_ALERT] = $text;
    }

    public static function GetAlert()
    {
        if (isset($_SESSION[Session::SESSION_KEY_ALERT])) {
            $text = $_SESSION[Session::SESSION_KEY_ALERT];
            unset($_SESSION[Session::SESSION_KEY_ALERT]);
            return $text;
        } else
            return '';
    }

    public static function GetLoginError()
    {
        return self::ExtractSessionVal(self::SESSION_KEY_LOGIN_ERROR, LoginError::NONE, false);
    }

    public static function UserAuthorizedFor($loginStatusLevel)
    {
        switch ($loginStatusLevel) {
            case LoginStatus::ADMIN:
                return Account::IsAdmin();
            case LoginStatus::OK:
                return self::IsLoggedIn();
            case LoginStatus::ANY:
            case LoginStatus::NONE:
                return true;
            default:
                return false;
        }
    }

    private static function logLogin($userId)
    {
        $now = Util::GetNowAsString();
        Database::ExecutePreparedStatement("INSERT INTO logins (user_id, date) VALUES (?, ?)", "is", array($userId, $now));;
        Database::ExecutePreparedStatement("UPDATE accounts SET last_login=? WHERE user_id=?", 'si', array($now, $userId));
    }

    private static function login($screenNameOrEmail, $password, $staySignedIn)
    {
        $userId = self::loginWithCredentials($screenNameOrEmail, $password);

        if ($userId > 0) {
            if ($staySignedIn)
                Token::GenToken($userId, Token::TOKEN_TYPE_AUTHENTICATION);
        }
        return $userId;
    }

    /* @param $screenNameOrEmail string
     * @param $password string
     * @return int user id
     */
    private static function loginWithCredentials($screenNameOrEmail, $password)
    {
        $screenNameOrEmail = strtolower($screenNameOrEmail);

        $userId = Account::GetUserIdFromEmail($screenNameOrEmail);

        if ($userId <= 0)
            $userId = Account::GetUserIdFromScreenName($screenNameOrEmail);

        if ($userId <= 0) {
            self::setLoginError($userId = LoginError::EMAIL_OR_SCREEN_NAME_NOT_FOUND);
            return 0;
        }
        $numFailed = (int)Database::LookupValue('accounts', 'user_id', $userId, 'i', 'failed_logins', 0);
        if ($numFailed > Account::MAX_LOGIN_ATTEMPTS) {
            self::setLoginError(LoginError::ACCOUNT_LOCKED);
            return 0;
        }
        if (Account::PasswordOk($userId, $password)) {
            self::ExecuteLogin($userId);
            Account::RecordLogin($userId, true);
            return $userId;
        } else {
            self::setLoginError(LoginError::PASSWORD_MISMATCH);
            Account::RecordLogin($userId, false);
            return 0;
        }
    }

    public static function SetLoginError($error)
    {
        self::SetSessionVal(Session::SESSION_KEY_LOGIN_ERROR, $error);
    }

    /* @param $userId int
     * @param $logEvent bool
     */
    public static function ExecuteLogin($userId, $logEvent = true)
    {
        self::Assert($userId > 0, "Execute login with user id: $userId");
        $oldUserId = Account::GetUserId();

        if ($userId !== $oldUserId) {
            self::SetSessionVal(Session::SESSION_KEY_USER_ID, $userId);
            if ($logEvent)
                self::logLogin($userId);
        }

        $tzOffset = UserInput::Extract(Key::KEY_TIME_ZONE_OFFSET, null);
        if (!is_null($tzOffset))
            Account::SetTimeZoneOffset($userId, $tzOffset);
    }

    public static function SetException(Exception $e)
    {
        $_SESSION[self::SESSION_KEY_EXCEPTION] = $e;
    }

    public static function GetException($clear = true)
    {
        if (isset($_SESSION[self::SESSION_KEY_EXCEPTION])) {
            $e = $_SESSION[self::SESSION_KEY_EXCEPTION];
            if ($clear)
                unset($_SESSION[self::SESSION_KEY_EXCEPTION]);
            return $e;
        } else {
            return null;
        }
    }

    private static function loginWithAuthCookie()
    {
        $userId = Token::AuthenticateWithCookie();

        if ($userId > 0) {
            self::ExecuteLogin($userId);
            Account::RecordLogin($userId, true);
            return $userId;
        } else {
            return 0;
        }
    }
}