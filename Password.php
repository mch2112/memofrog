<?php

class Password
{
    const MIN_PASSWORD_LENGTH = 6;
    public static function PasswordIsStrong($password, &$passwordMsg)
    {
        $password = mb_strtolower($password);

        if (true || Session::IsProduction()) {
            if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
                $passwordMsg = 'That password is too short. Passwords must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters long.';
                return false;
            } else if (!preg_match('/[a-z]/', $password)) {
                $passwordMsg = 'Passwords need at least one letter.';
                return false;
            } else if (!preg_match('/[0-9]/', $password)) {
                $passwordMsg = 'Passwords need at least one number.';
                return false;}
            else if (preg_match('/passwo/', $password)) {
                $passwordMsg = 'That password is too easy to guess.';
                return false;
            } else if (self::isCommon($password)) {
                $passwordMsg = 'That password is too common.';
                return false;
            } else {
                $passwordMsg = '';
                return true;
            }
        }
        else
        {
            return true;
        }
    }
    private static function isCommon($password) {
        $password = mb_strtolower($password);

        $common = 'password1ncc1701baseball';

        return mb_strpos($common, $password) !== false;
    }
}