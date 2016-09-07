<?php

class Util
{
    const DATE_FORMAT = 'Y-m-d';
    const DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    const DISPLAY_DATE_FORMAT = 'l, F jS, Y';
    const DISPLAY_DATE_FORMAT_SHORT = 'D, M jS, Y';

    function GetLorum()
    {
        return file_get_contents('http://loripsum.net/api/1/short/plaintext');
    }

    public static function GetTodayAsString()
    {
        return (new DateTime('NOW'))->format(self::DATE_FORMAT);
    }

    /* @param $date DateTime
     * @return DateTime
     */
    public static function FormatDate(DateTime $date)
    {
        if (is_null($date))
            return null;
        else
            return $date->format(Util::DATE_FORMAT);
    }

    /* @param $days int
     * @param $tzOffset int
     * @return DateTime
     */
    public static function GetDaysFromToday($days, $tzOffset = 0)
    {
        if ($days > 0) {
            $dt = (new DateTime('NOW'))->add(new DateInterval("P{$days}D"));
        } else {
            $days = -$days;
            $dt = (new DateTime('NOW'))->sub(new DateInterval("P{$days}D"));
        }

        if ($tzOffset > 0)
            $dt = $dt->add(new DateInterval("PT{$tzOffset}M"));
        else {
            $tzOffset = -$tzOffset;
            $dt = $dt->sub(new DateInterval("PT{$tzOffset}M"));
        }
        return $dt;
    }

    public static function GetDaysFromTodayAsString($days, $tzOffset = 0)
    {
        return self::GetDaysFromToday($days, $tzOffset)->format(self::DATE_FORMAT);
    }

    public static function GetMonthsFromTodayAsString($months, $tzOffset = 0)
    {
        if ($months > 0) {
            $dt = (new DateTime('NOW'))->add(new DateInterval("P{$months}M"));
        } else {
            $months = -$months;
            $dt = (new DateTime('NOW'))->sub(new DateInterval("P{$months}M"));
        }

        if ($tzOffset > 0)
            $dt = $dt->add(new DateInterval("PT{$tzOffset}M"));
        else {
            $tzOffset = -$tzOffset;
            $dt = $dt->sub(new DateInterval("PT{$tzOffset}M"));
        }

        return $dt->format(self::DATE_FORMAT);
    }

    public static function GetNowAsString()
    {
        return (new DateTime('NOW'))->format(DateTime::ISO8601);
    }

    public static function GetNowOffsetAsString($offset)
    {
        return (new DateTime('NOW'))->add(new DateInterval($offset))->format(DateTime::ISO8601);
    }

    public static function WriteTextFile($fileName, $text)
    {
        if (!Session::IsProduction()) {
            file_put_contents('./' . $fileName, $text);
        }
    }

    public static function LogTextFile($fileName, $text)
    {
        if (!Session::IsProduction()) {
            file_put_contents('./' . $fileName, self::GetNowAsString() . "\n" . $text . "\n\n", FILE_APPEND);
        }
    }

    public static function LogState($marker)
    {
        if (!Session::IsProduction()) {
            $userId = Account::GetUserId();
            $screenName = Account::GetScreenName($userId);
            self::Log("\n\n$marker - User: $screenName ($userId)" .
                "\nSession Data: " . json_encode($_SESSION));
        }
    }

    public static function Log($text)
    {
        self::LogTextFile('./junk.txt', $text)    ;
    }

    public static function GetRandomHexString($digits)
    {
        // TODO: Make cryptographically secure
        $ret = '';
        while (strlen($ret) < $digits) {
            $rand = mt_rand(0x000000, 0xffffff);
            $ret .= dechex(str_pad($rand, 6, '0', STR_PAD_LEFT));
        }
        if (strlen($ret) == $digits)
            return $ret;
        else
            return substr($ret, 0, $digits);
    }
}