<?php

class Token
{
    const TOKEN_TYPE_NONE = 0;
    const TOKEN_TYPE_ALL = 10;
    const TOKEN_TYPE_AUTHENTICATION = 100;
    const TOKEN_TYPE_VALIDATION = 110;

    const TOKEN_LENGTH = 32;

    const COOKIE_AUTH_TOKEN = 'auto_auth_token';

    /* @var $type int
     * @var $key string
     * @var $userId int
     * @var $data string
     * @var $created DateTime
     * @var $expires DateTime
     */
    public $type = self::TOKEN_TYPE_NONE;
    public $key = '';
    public $userId = 0;
    public $data = null;
    public $created = null;
    public $expires = null;

    /* @param $type int
     * @param $key string
     * @param $userId int
     * @param $data string
     * @param DateTime $created
     * @param DateTime $expires
     */
    public function __construct($type, $key, $userId, $data, $created, $expires = null)
    {
        $this->type = $type;
        $this->key = $key;
        $this->userId = $userId;
        $this->data = $data;
        $this->created = $created;
        if (is_null($expires)) {
            $offset = ($type === self::TOKEN_TYPE_AUTHENTICATION ? 'P1Y' : 'P3D');
            $expires = (new DateTime('NOW'))->add(new DateInterval($offset));
        }
        $this->expires = $expires;
    }

    /* @return int */
    public static function AuthenticateWithCookie()
    {
        if (isset($_COOKIE[self::COOKIE_AUTH_TOKEN])) {
            $token = self::GetToken($_COOKIE[self::COOKIE_AUTH_TOKEN]);
            if ($token != null && $token->type === self::TOKEN_TYPE_AUTHENTICATION)
                return $token->userId;
        }
        return 0;
    }

    /* @param $userId int
     * @param $tokenKey string
     * @return bool
     */
    public static function ValidateEmail(&$userId, $tokenKey)
    {
        $token = self::GetToken($tokenKey);
        if ($token != null) {
            if ($userId > 0 && $token->userId !== $userId)
                return false;

            if ($token->type === self::TOKEN_TYPE_VALIDATION) {
                $userId = $token->userId;
                self::ValidateUser($token->userId, $token->data);
                self::DeleteTokensForUser($token->userId, self::TOKEN_TYPE_VALIDATION);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public static function UserHasToken($userId, $type)
    {
        return Database::RecordExists("tokens WHERE user_id=$userId AND type=$type");
    }

    public static function GenToken($userId, $type, $data = '')
    {
        switch ($type) {
            case self::TOKEN_TYPE_VALIDATION :
                if (strlen($data) === 0)
                    $data = Account::GetEmail($userId);

                $token = self::GetTokenByTypeAndUserIdAndData($type, $userId, $data);

                if (is_null($token)) {
                    $token = new Token($type, self::genSecureKey(self::TOKEN_LENGTH), $userId, $data, new DateTime());
                    $token->save();
                }

                Notification::Notify($userId, Notification::NOTIFY_EMAIL_VALIDATION);
                break;
            case self::TOKEN_TYPE_AUTHENTICATION:
                $token = new Token($type, self::genSecureKey(self::TOKEN_LENGTH), $userId, $data, new DateTime());
                $token->save();
                $secure = Session::IsProduction();
                setcookie(self::COOKIE_AUTH_TOKEN, $token->key, $token->expires->getTimestamp(), null, null, $secure, true);
                break;
        }
    }

    public static function DeleteTokensForUser($userId, $type)
    {
        switch ($type) {
            case self::TOKEN_TYPE_NONE:
                break;
            case self::TOKEN_TYPE_ALL:
                Database::ExecutePreparedStatement("DELETE FROM tokens WHERE user_id=?", 'i', array($userId));
                break;
            default:
                Database::ExecutePreparedStatement("DELETE FROM tokens WHERE user_id=? AND type=?", 'ii', array($userId, $type));
                break;
        }
    }

    public static function ClearAuthToken()
    {
        if (isset($_COOKIE[self::COOKIE_AUTH_TOKEN])) {
            $tokenKey = $_COOKIE[self::COOKIE_AUTH_TOKEN];
            if (strlen($tokenKey) > 0)
                Database::ExecutePreparedStatement("DELETE FROM tokens WHERE token_key=?", 's', array($tokenKey));
        }
        setcookie(self::COOKIE_AUTH_TOKEN, '');
    }

    private static function genSecureKey($length)
    {
        // TODO: Replace with crypto secure method
        return Util::GetRandomHexString($length);
    }

    private function save()
    {
        Database::ExecutePreparedStatement("INSERT INTO tokens (type, user_id, token_key, data, expires) VALUES (?, ?, ?, ?, ?)", "iisss", array($this->type, $this->userId, $this->key, $this->data, $this->expires->format(DateTime::ISO8601)));
    }

    /* @param $tokenKey string
     * @return Token
     */
    public static function GetToken($tokenKey)
    {
        return self::getTokenByDbRow(Database::QueryOneRow('SELECT type, token_key, user_id, data, created, expires FROM tokens WHERE token_key=\'' . $tokenKey . '\''));
    }

    /* @param $type int
     * @param $userId int
     * @return Token
     */
    public static function GetTokenByTypeAndUserId($type, $userId)
    {
        return self::getTokenByDbRow(Database::QueryOneRow("SELECT type, token_key, user_id, data, created, expires FROM tokens WHERE type=$type AND user_id=$userId ORDER BY id DESC"));
    }

    /* @param $type int
     * @param $userId int
     * @param $data string
     * @return Token
     */
    public static function GetTokenByTypeAndUserIdAndData($type, $userId, $data)
    {
        return self::getTokenByDbRow(Database::QueryOneRow("SELECT type, token_key, user_id, data, created, expires FROM tokens WHERE type=$type AND user_id=$userId AND data='$data' ORDER BY id DESC"));
    }

    /* @param $row array
     * @return Token
     */
    private static function getTokenByDbRow($row)
    {
        if ($row) {
            $expires = new DateTime($row['expires']);
            if (new DateTime() < $expires)
                return new Token((int)$row['type'],
                    $row['token_key'],
                    (int)$row['user_id'],
                    $row['data'],
                    new DateTime($row['created']),
                    new DateTime($row['expires']));
        }
        return null;
    }

    /* @param $userId int
     * @param $email string
     */
    public static function ValidateUser($userId, $email = '')
    {
        if (strlen($email) > 0)
            Database::ExecutePreparedStatement('UPDATE accounts SET email=?, email_validated=? WHERE user_id=?', 'sii', array($email, 1, $userId));
        else
            Database::ExecutePreparedStatement('UPDATE accounts SET email_validated=? WHERE user_id=?', 'ii', array(1, $userId));
    }
}