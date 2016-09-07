<?php

class UserInput
{
    const MAX_MEMO_LENGTH = 4096;

    /* @var $userSentData bool */
    static $userSentData = false;

    public static function StoreInput()
    {
        foreach ($_GET as $k => $v) {
            self::$userSentData |= self::Store($k, $v);
        }
        foreach ($_POST as $k => $v) {
            self::$userSentData |= self::Store($k, $v);
        }
    }

    /* @param $key string
     * @param $default
     * @param $clear bool
     * @return
     */
    public static function Extract($key, $default = null, $clear = true)
    {
        return Session::ExtractSessionVal($key, $default, $clear);
    }

    /* @param $key string
     * @return bool
     */
    public static function IsKeySet($key)
    {
        return Session::IsSessionKeySet($key);
    }

    /* @param $key string */
    public static function Clear($key)
    {
        unset($_SESSION[$key]);
    }

    /* @return bool */
    public static function UserSentData()
    {
        return self::$userSentData;
    }

    /* @param $input
     * @return int
     */
    private static function sanitizeNumber($input)
    {
        return intval($input);
    }

    /* @param $input
     * @return string
     */
    private static function sanitizeTag($input)
    {
        $tag = preg_replace('/\s+/', '_', self::sanitizeString($input));
        while (strlen($tag) && $tag[0] === '#')
            $tag = mb_substr($tag, 1);
        return $tag;
    }

    /* @param $input
     * @param $length int
     * @return string
     */
    private static function sanitizeStringLowerCaseNoSpaces($input, $length = 128)
    {
        return preg_replace('/\s+/', '_', self::sanitizeStringLowerCase($input, $length));
    }

    /* @param $input
     * @param $length int
     * @return string
     */
    private static function sanitizeStringLowerCase($input, $length = 128)
    {
        return mb_strtolower(self::sanitizeString($input, $length));
    }

    /* @param $input
     * @param $length int
     * @return string
     */
    private static function sanitizeString($input, $length = 128)
    {
        if (mb_check_encoding($input, 'UTF-8'))
            return htmlspecialchars(trim(mb_substr($input, 0, $length)));
        else
            return '<Invalid Encoding>';
    }

    /* @param $input
     * @return string
     */
    private static function sanitizeHexString($input)
    {
        $str = trim(substr($input, 0, 64));
        if (ctype_xdigit($str))
            return $str;
        return '<Invalid Encoding>';
    }

    /* @param $input
     * @return bool
     */
    private static function sanitizeBool($input)
    {
        switch ($input) {
            case 'false':
                return false;
            case 'true':
                return true;
            default:
                return boolval($input);
        }
    }

    /* @param $input
     * @return DateTime
     */
    private static function sanitizeDate($input)
    {
        $input = trim($input);
        if (strlen($input) >= 10) {
            $date = new DateTime(substr($input, 0, 10));
            $date->setTime(0, 0, 0);
            return $date;
        } else
            return null;
    }

    /* @param $k string
     * @param $v
     * @return bool
     */
    public static function Store($k, $v)
    {
        switch ($k) {
            case Key::KEY_VALIDATION_KEY:
            case Key::KEY_PASSWORD_RESET_KEY:
                $_SESSION[$k] = self::sanitizeHexString($v);
                break;
            case Key::KEY_NEW_EDIT_MEMO_TEXT:
                $_SESSION[$k] = self::sanitizeString($v, self::MAX_MEMO_LENGTH);
                break;
            case Key::KEY_CHANGE_PASSWORD_OLD:
            case Key::KEY_CHANGE_PASSWORD_NEW:
                $_SESSION[$k] = self::sanitizeString($v, 256);
                break;
            case Key::KEY_ALARM_DATE:
                $_SESSION[$k] = self::sanitizeDate($v);
                break;
            case Key::KEY_LOGIN_PASSWORD:
            case Key::KEY_ACCOUNT_DETAILS_REAL_NAME:
            case Key::KEY_REGISTER_REAL_NAME:
            case Key::KEY_REGISTER_PASSWORD:
            case Key::KEY_FILTER_TEXT:
            case Key::KEY_POST_NEW_VALUE:
            case Key::KEY_PLACEHOLDER_TX_ID:
            case Key::KEY_POST_TX_ID:
            case Key::KEY_POST_TX_GUID:
                $_SESSION[$k] = self::sanitizeString($v);
                break;
            case Key::KEY_TAG:
            case Key::KEY_RENAME_TAG_OLD_TAG:
            case Key::KEY_RENAME_TAG_NEW_TAG:
                $_SESSION[$k] = self::sanitizeTag($v);
                break;
            case Key::KEY_FORGOT_PASSWORD_SCREEN_NAME_OR_EMAIL:
            case Key::KEY_SCREEN_NAME:
            case Key::KEY_FRIEND:
            case Key::KEY_REGISTER_SCREEN_NAME:
            case Key::KEY_ACCOUNT_DETAILS_SCREEN_NAME:
            case Key::KEY_SHARE_WITH_SCREEN_NAME:
            case Key::KEY_LOGIN_SCREEN_NAME_OR_EMAIL:
            case Key::KEY_REGISTER_EMAIL:
            case Key::KEY_ACCOUNT_DETAILS_EMAIL:
            case Key::KEY_FILTER_TAGS:
            case Key::KEY_APP_VERSION:
                $_SESSION[$k] = self::sanitizeStringLowerCaseNoSpaces($v);
                break;
            case Key::KEY_SHARE_TAGS:
                $_SESSION[$k] = self::sanitizeStringLowerCase($v);
                break;
            case Key::KEY_MEMO_ID:
            case Key::KEY_MEMO_BUCKET:
            case Key::KEY_BUCKET:
            case Key::KEY_CONTENT:
            case Key::KEY_NUM_MEMOS_REQ:
            case Key::KEY_SHARE_ID:
            case Key::KEY_ENABLE_SHARE_SOURCE:
            case Key::KEY_DISABLE_SHARE_SOURCE:
            case Key::KEY_ENABLE_SHARE_TARGET:
            case Key::KEY_DISABLE_SHARE_TARGET:
            case Key::KEY_SHARE_TO_DELETE:
            case Key::KEY_SHARE_TO_UNDELETE:
            case Key::KEY_SHARE_CAN_EDIT_TO_TOGGLE:
            case Key::KEY_SPECIAL_SEARCH:
            case Key::KEY_TAGS_SCREEN_SORTING:
            case Key::KEY_TAGS_SCREEN_FILTER:
            case Key::KEY_FRIEND_ID:
            case Key::KEY_TIP_ID:
            case Key::KEY_TIME_ZONE_OFFSET:
            case Key::KEY_PENDING_MEMO_TEMP_ID:
            case Key::KEY_FILTER_TAGS_OP:
            case Key::KEY_VALIDATION_TYPE:
            case Key::KEY_ERROR_CODE:
            case Key::KEY_ERROR_CONTENT_ID:
            case Key::KEY_POST_TYPE:
            case Key::KEY_POST_USER_ID:
            case Key::KEY_POST_MEMO_ID:
            case Key::KEY_PLACEHOLDER_KEY:
            case Key::KEY_DATA_REQ:
                $_SESSION[$k] = self::sanitizeNumber($v);
                break;
            case Key::KEY_CONTENT_REQ:
            case Key::KEY_TARGET_MEMO:
            case Key::KEY_TAG_ID:
            case Key::KEY_ADMIN_VALIDATE_USER_ID:
            case Key::KEY_EXTERNAL_SHARE_ID:
            case Key::KEY_EXTERNAL_MEMO_ID:
            case Key::KEY_EXTERNAL_CONTENT_REQ:
                $v = self::sanitizeNumber($v);
                if ($v > 0)
                    $_SESSION[$k] = $v;
                break;
            case Key::KEY_CLEAR_FILTERS:
            case Key::KEY_LOGIN_REMEMBER_ME:
            case Key::KEY_NEW_EDIT_MEMO_PRIVATE:
            case Key::KEY_NEW_EDIT_MEMO_FRIENDS_CAN_EDIT:
            case Key::KEY_NEW_EDIT_MEMO_SYNC_BUCKETS:
            case Key::KEY_IS_UNDO:
            case Key::KEY_SHARE_CAN_EDIT:
            case Key::KEY_SCREEN_NAME_TO:
            case Key::KEY_TOGGLE_MOVE_DONE_TO_TRASH:
            case Key::KEY_EMPTY_TRASH:
            case Key::KEY_ALARM_ENABLED:
            case Key::KEY_TOGGLE_SHOW_TIPS:
            case Key::KEY_TOGGLE_EMAIL_ON_ALARM:
            case Key::KEY_TOGGLE_EMAIL_ON_SHARE:
            case Key::KEY_DELETE_MEMO_CONFIRMED:
            case Key::KEY_MEMO_STAR:
            case Key::KEY_RESET_DB:
                $_SESSION[$k] = self::sanitizeBool($v);
                break;
            case Key::KEY_LOGIN_AS_USER_ID:
                if (Account::IsAdmin())
                    $_SESSION[$k] = self::sanitizeNumber($v);
                break;
            default:
                return false;
        }
        return true;
    }
}