<?php

/**
 * Class Feedback
 */
class Validation
{
    const VALIDATION_TYPE_NON_ZERO_LENGTH_ANY_INPUT = 50;
    const VALIDATION_TYPE_NON_ZERO_LENGTH_ALL_INPUTS = 60;
    const VALIDATION_TYPE_NEW_SHARE = 110;
    const VALIDATION_TYPE_REGISTER = 120;
    const VALIDATION_TYPE_CHANGE_PASSWORD = 130;
    const VALIDATION_TYPE_ACCOUNT_DETAILS = 140;
    const VALIDATION_TYPE_CHANGE_EMAIL = 150;

    private static $cantLeaveEmpty = 'You can&apos;t leave this empty.';

    /**
     * @param $realName string
     * @param $screenName string
     * @param $email string
     * @param $password string
     * @return array
     */
    public static function GetRegisterValidation($realName, $screenName, $email, $password)
    {
        $validation = array();
        $submitOk = true;

        if (strlen($realName) === 0) {
            $submitOk = false;
            $err = true;
            $warning = false;
            $msg = self::$cantLeaveEmpty;
        } else {
            $msg = '';
            $err = false;
            $warning = false;
        }
        $validation[] = array(Key::KEY_VALIDATION_SOURCE => Key::KEY_REGISTER_REAL_NAME, Key::KEY_VALIDATION_TARGET => Key::KEY_VALIDATION_TARGET_REAL_NAME, Key::KEY_VALIDATION_MESSAGE => $msg, Key::KEY_VALIDATION_ERROR => $err, Key::KEY_VALIDATION_WARNING => $warning);

        if (strlen($screenName) === 0) {
            $submitOk = false;
            $err = true;
            $warning = false;
            $msg = self::$cantLeaveEmpty;
        } else if (mb_strlen($screenName) < PRegisterUser::MIN_SCREEN_NAME_LENGTH || mb_strlen($screenName) > PRegisterUser::MAX_SCREEN_NAME_LENGTH) {
            $msg = 'Please use between ' . strval(PRegisterUser::MIN_SCREEN_NAME_LENGTH) . ' and ' . strval(PRegisterUser::MAX_SCREEN_NAME_LENGTH) . ' characters.';
            $err = true;
            $warning = false;
        } else if (Account::GetUserIdFromScreenName($screenName) > 0) {
            $msg = 'That screen name is not available.';
            $err = true;
            $warning = false;
        } else {
            $msg = '';
            $err = false;
            $warning = false;
        }
        $validation[] = array(Key::KEY_VALIDATION_SOURCE => Key::KEY_REGISTER_SCREEN_NAME, Key::KEY_VALIDATION_TARGET => Key::KEY_VALIDATION_TARGET_SCREEN_NAME, Key::KEY_VALIDATION_MESSAGE => $msg, Key::KEY_VALIDATION_ERROR => $err, Key::KEY_VALIDATION_WARNING => $warning);

        if (strlen($email) === 0) {
            $submitOk = false;
            $err = true;
            $warning = false;
            $msg = self::$cantLeaveEmpty;
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $submitOk = false;
            $err = true;
            $warning = false;
            /** @noinspection SpellCheckingInspection */
            $msg = 'Hmmm, that doesn&apos;t look like a valid email address.';
        } else {
            $msg = '';
            $err = false;
            $warning = false;
        }
        $validation[] = array(Key::KEY_VALIDATION_SOURCE => Key::KEY_REGISTER_EMAIL, Key::KEY_VALIDATION_TARGET => Key::KEY_VALIDATION_TARGET_EMAIL, Key::KEY_VALIDATION_MESSAGE => $msg, Key::KEY_VALIDATION_ERROR => $err, Key::KEY_VALIDATION_WARNING => $warning);

        if (strlen($password) === 0) {
            $submitOk = false;
            $err = true;
            $warning = false;
            $msg = self::$cantLeaveEmpty;
        } else if (!Password::PasswordIsStrong($password, $msg)) {
            $submitOk = false;
            $err = true;
            $warning = false;
        } else {
            $err = false;
            $warning = false;
            $msg = '';
        }
        $validation[] = array(Key::KEY_VALIDATION_SOURCE => Key::KEY_REGISTER_PASSWORD, Key::KEY_VALIDATION_TARGET => Key::KEY_VALIDATION_TARGET_PASSWORD, Key::KEY_VALIDATION_MESSAGE => $msg, Key::KEY_VALIDATION_ERROR => $err, Key::KEY_VALIDATION_WARNING => $warning);

        return array(Key::KEY_VALIDATION => $validation, Key::KEY_SUBMIT_OK => $submitOk);
    }

    /**
     * @param $userId int
     * @param $name string
     * @param $tags string
     * @return array
     */
    public static function GetNewShareValidation($userId, $name, $tags)
    {
        $validation = array();
        $submitOk = true;

        $err = false;
        $warning = false;

        if (strlen($name) === 0) {
            $submitOk = false;
            $err = true;
            $msg = self::$cantLeaveEmpty;
        } else {
            if (mb_substr($name, 0, 1) === '@')
                $name = mb_substr($name, 1);
            $targetUserId = Account::GetUserIdFromScreenName($name);
            if ($targetUserId > 0) {
                $msg = Html::Icon('ok') . '&nbsp;Screen name ' . Html::Span('screen_name', '@' . $name) . ' found.';
            } else {
                $submitOk = false;
                $err = true;
                $msg = 'Screen name ' . Html::Span('screen_name', '@' . $name) . ' not found.';
            }
        }

        $validation[] = array(Key::KEY_VALIDATION_SOURCE => Key::KEY_SHARE_WITH_SCREEN_NAME, Key::KEY_VALIDATION_TARGET => Key::KEY_VALIDATION_TARGET_SCREEN_NAME, Key::KEY_VALIDATION_MESSAGE => $msg, Key::KEY_VALIDATION_ERROR => $err, Key::KEY_VALIDATION_WARNING => $warning);

        $err = false;

        $tagList = Controller::GetTagListFromString($tags);
        switch (count($tagList)) {
            case 0:
                $submitOk = false;
                $err = true;
                $msg = self::$cantLeaveEmpty;
                break;
            case 1:
                $tagId = Controller::GetTagId($tagList[0]);
                $count = Controller::GetNumberMemosWithTagId($userId, $tagId);
                $num = Content::Pluralize($count, 'memo', true);
                $warning |= $count === 0;
                $msg = "$num found with tag <span class=\"hashtag\">#{$tagList[0]}</span>.";
                break;
            default:
                $tagIdList = Controller::GetTagIdsFromTagList($tagList, false);
                $count = Controller::GetNumberMemosWithTagIds($userId, $tagIdList);
                $num = Content::Pluralize($count, 'memo', true);
                $warning |= $count === 0;
                $msg = "$num found with tags <span class=\"hashtag\">#" . join('+#', $tagList) . '</span>.';
                break;
        }
        $validation[] = array(Key::KEY_VALIDATION_SOURCE => Key::KEY_SHARE_TAGS, Key::KEY_VALIDATION_TARGET => Key::KEY_VALIDATION_TARGET_TAGS, Key::KEY_VALIDATION_MESSAGE => $msg, Key::KEY_VALIDATION_ERROR => $err, Key::KEY_VALIDATION_WARNING => $warning);
        return array(Key::KEY_VALIDATION => $validation, Key::KEY_SUBMIT_OK => $submitOk);
    }

    public static function GetChangePasswordValidation($oldPassword, $newPassword)
    {
        $submitOk = true;
        $validation = array();
        if (strlen($oldPassword) === 0) {
            $submitOk = false;
            $err = true;
            $msg = self::$cantLeaveEmpty;
        } else {
            $msg = '';
            $err = false;
        }
        $validation[] = array(Key::KEY_VALIDATION_SOURCE => Key::KEY_CHANGE_PASSWORD_OLD, Key::KEY_VALIDATION_TARGET => Key::KEY_VALIDATION_TARGET_OLD_PASSWORD, Key::KEY_VALIDATION_MESSAGE => $msg, Key::KEY_VALIDATION_ERROR => $err, Key::KEY_VALIDATION_WARNING => false);

        if (strlen($newPassword) === 0) {
            $submitOk = false;
            $err = true;
            $msg = self::$cantLeaveEmpty;
        } else if (!Password::PasswordIsStrong($newPassword, $msg)) {
            $submitOk = false;
            $err = true;
        } else {
            $err = false;
            $msg = '';
        }
        $validation[] = array(Key::KEY_VALIDATION_SOURCE => Key::KEY_CHANGE_PASSWORD_NEW, Key::KEY_VALIDATION_TARGET => Key::KEY_VALIDATION_TARGET_NEW_PASSWORD, Key::KEY_VALIDATION_MESSAGE => $msg, Key::KEY_VALIDATION_ERROR => $err, Key::KEY_VALIDATION_WARNING => false);

        return array(Key::KEY_VALIDATION => $validation, Key::KEY_SUBMIT_OK => $submitOk);
    }
    public static function GetAccountDetailsValidation($userId, $screenName, $realName) {
        $validation = array();
        $submitOk = true;

        $screenNameUserId = Account::GetUserIdFromScreenName($screenName);

        if (strlen($realName) === 0) {
            $submitOk = false;
            $err = true;
            $msg = self::$cantLeaveEmpty;
        } else {
            $msg = '';
            $err = false;
        }
        $validation[] = array(Key::KEY_VALIDATION_SOURCE => Key::KEY_ACCOUNT_DETAILS_REAL_NAME, Key::KEY_VALIDATION_TARGET => Key::KEY_VALIDATION_TARGET_REAL_NAME, Key::KEY_VALIDATION_MESSAGE => $msg, Key::KEY_VALIDATION_ERROR => $err, Key::KEY_VALIDATION_WARNING => false);

        if (strlen($screenName) === 0) {
            $submitOk = false;
            $err = true;
            $msg = self::$cantLeaveEmpty;
        } else if ($screenNameUserId > 0 && $screenNameUserId !== $userId) {
            $submitOk = false;
            $msg = 'That screen name is not available.';
            $err = true;
        } else if (mb_strlen($screenName) < PRegisterUser::MIN_SCREEN_NAME_LENGTH || mb_strlen($screenName) > PRegisterUser::MAX_SCREEN_NAME_LENGTH) {
            $msg = 'Please use between ' . strval(PRegisterUser::MIN_SCREEN_NAME_LENGTH) . ' and ' . strval(PRegisterUser::MAX_SCREEN_NAME_LENGTH) . ' characters.';
            $submitOk = false;
            $err = true;
        } else {
            $msg = '';
            $err = false;
        }
        $validation[] = array(Key::KEY_VALIDATION_SOURCE => Key::KEY_ACCOUNT_DETAILS_SCREEN_NAME, Key::KEY_VALIDATION_TARGET => Key::KEY_VALIDATION_TARGET_SCREEN_NAME, Key::KEY_VALIDATION_MESSAGE => $msg, Key::KEY_VALIDATION_ERROR => $err, Key::KEY_VALIDATION_WARNING => false);

        return array(Key::KEY_VALIDATION => $validation, Key::KEY_SUBMIT_OK => $submitOk);
    }
    /**
     * @param $userId int
     * @param $email string
     * @return array
     */
    public static function GetChangeEmailValidation($userId, $email)
    {
        $validation = array();

        $submitOk = Account::GetEmail($userId) !== $email;

        if (strlen($email) === 0) {
            $submitOk = false;
            $err = true;
            $msg = self::$cantLeaveEmpty;
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $submitOk = false;
            $err = true;
            /** @noinspection SpellCheckingInspection */
            $msg = 'Hmmm, that doesn&apos;t look like a valid email address.';
        } else {
            $msg = '';
            $err = false;
        }
        $validation[] = array(Key::KEY_VALIDATION_SOURCE => Key::KEY_ACCOUNT_DETAILS_EMAIL, Key::KEY_VALIDATION_TARGET => Key::KEY_VALIDATION_TARGET_EMAIL, Key::KEY_VALIDATION_MESSAGE => $msg, Key::KEY_VALIDATION_ERROR => $err, Key::KEY_VALIDATION_WARNING => false);

        return array(Key::KEY_VALIDATION => $validation, Key::KEY_SUBMIT_OK => $submitOk);
    }
}