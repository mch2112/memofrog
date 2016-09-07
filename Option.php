<?php
class Option
{
    const OPTION_MOVE_DONE_TO_TRASH = 100;
    const OPTION_SHOW_TIPS = 110;
    const OPTION_SEND_EMAIL_ON_ALARM = 120;
    const OPTION_SEND_EMAIL_ON_SHARE = 130;

    const OPTION_VALUE_SHOW_TIPS_NEVER = 0;
    const OPTION_VALUE_SHOW_TIPS_LARGE_SCREEN = 1;
    const OPTION_VALUE_SHOW_TIPS_ALWAYS = 2;

    const OPTION_VALUE_ENABLED = 1;
    const OPTION_VALUE_DISABLED = 0;

    /* @param $userId int
     * @param $optionId int
     * @return int
     */
    public static function GetOptionValue($userId, $optionId) {
        $row = Database::QueryOneRow("SELECT option_value FROM options WHERE user_id=$userId AND option_id=$optionId");
        if ($row)
            return (int)$row['option_value'];
        else
            return self::GetDefault($optionId);
    }

    /* @param $userId int
     * @param $optionId int
     * @param $value int
     */
    public static function SetOption($userId, $optionId, $value)
    {
        Database::ExecutePreparedStatement("INSERT INTO options (user_id, option_id, option_value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE option_value=?", 'iiii', array($userId, $optionId, $value, $value));
    }

    /* @param $userId int
    * @param $optionId int
    * @return int
     */
    public static function CycleOption($userId, $optionId)
    {
        $currentValue = self::GetOptionValue($userId, $optionId);

        switch ($optionId)
        {
            case self::OPTION_SHOW_TIPS:
                $newValue = ($currentValue + 1) % 3;
                break;
            default:
                $newValue  = 1 - $currentValue;
                break;
        }

        self::SetOption($userId, $optionId, $newValue);
        return $newValue;
    }

    /* @param $optionId int
     * @return int
     */
    public static function GetDefault($optionId)
    {
        switch ($optionId)
        {
            case self::OPTION_SHOW_TIPS:
                return self::OPTION_VALUE_SHOW_TIPS_ALWAYS;
            case self::OPTION_MOVE_DONE_TO_TRASH:
            case self::OPTION_SEND_EMAIL_ON_ALARM:
            case self::OPTION_SEND_EMAIL_ON_SHARE:
                return self::OPTION_VALUE_ENABLED;
            default:
                return self::OPTION_VALUE_DISABLED;
        }
    }

    /* @param $optionId int
     * @return string
     */
    public static function GetOptionName($optionId)
    {
        switch ($optionId) {
            case self::OPTION_SEND_EMAIL_ON_ALARM:
                return 'Send email when memos alarm';
            case self::OPTION_SEND_EMAIL_ON_SHARE:
                return 'Send email when friends share memos';
            case self::OPTION_SHOW_TIPS:
                return 'Show tips each session';
            case self::OPTION_MOVE_DONE_TO_TRASH:
                return 'Auto move done to trash';
            default:
                return '';
        }
    }

    /* @param $optionId int
     * @param $value int
     * @return int
     */
    public static function GetOptionDescription($optionId, $value)
    {
        switch ($optionId) {
            case self::OPTION_SHOW_TIPS:
                switch ($value) {
                    case self::OPTION_VALUE_SHOW_TIPS_ALWAYS:
                        return 'Enabled';
                    case self::OPTION_VALUE_SHOW_TIPS_LARGE_SCREEN:
                        return 'Disabled on small screens';
                    case self::OPTION_VALUE_SHOW_TIPS_NEVER:
                    default:
                        return 'Disabled';
                }
            default:
                if ($value === self::OPTION_VALUE_DISABLED)
                    return 'Disabled';
                else
                    return 'Enabled';
        }
    }
}