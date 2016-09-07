<?php

require_once('lib/SendGrid/SendGrid_loader.php');

class Notification
{
    const NOTIFY_EMAIL_VALIDATION = 110;
    const NOTIFY_FORGOT_PASSWORD_INSTRUCTIONS = 120;
    const NOTIFY_MEMO_ALARM = 210;
    const NOTIFY_NEW_SHARE = 220;
    const NOTIFY_NEW_DIRECT_SHARE = 230;

    const SEND_METHOD_NONE = 0;
    const SEND_METHOD_EMAIL = 1;

    public static function Notify($userId, $type, $dataId = 0)
    {
        switch ($type) {
            case self::NOTIFY_EMAIL_VALIDATION:
                self::sendEmailValidation($userId);
                self::recordNotification($type, $userId, 0, true, false);
                break;
            case self::NOTIFY_FORGOT_PASSWORD_INSTRUCTIONS:
                self::notifyForgotPasswordInstructions($userId);
                self::recordNotification($type, $userId, 0, true, false);
                break;
            case self::NOTIFY_MEMO_ALARM:
                self::recordNotification($type, $userId, $dataId, false, true);
                break;
            case self::NOTIFY_NEW_DIRECT_SHARE:
            case self::NOTIFY_NEW_SHARE:
                self::recordNotification($type, $userId, $dataId, false, false);
                break;
            default:
                self::recordNotification($type, $userId, $dataId, false, false);
                break;
        }
    }

    public static function ExecuteNotifications()
    {
        $count = 0;
        Database::QueryCallback('SELECT * FROM notifications INNER JOIN accounts ON notifications.user_id = accounts.user_id WHERE notifications.sent=0 AND accounts.email_validated=1', function ($row) use (&$count) {
            $id = (int)$row['id'];
            $userId = (int)$row['user_id'];
            $dataId = (int)$row['data_id'];
            $type = (int)$row['type'];
            switch ($type) {
                case self::NOTIFY_MEMO_ALARM:
                    $count += self::sendAlarmEmail($userId, $dataId, $id);
                    break;
                case self::NOTIFY_NEW_SHARE:
                    $count += self::sendNewShareEmail($userId, $dataId, $id);
                    break;
                case self::NOTIFY_NEW_DIRECT_SHARE:
                    $count += self::sendNewDirectShareEmail($userId, $dataId, $id);
                    break;
            }
        });

        return $count;
    }

    /* @param $userId int
     * @param $type int
     * @return DateTime
     */
    public static function GetTimeOfNotification($userId, $type)
    {
        $row = Database::QueryOneRow("SELECT created FROM notifications WHERE user_id=$userId and type=$type ORDER BY id DESC");
        if ($row)
            return new DateTime($row['created']);
        else
            return null;
    }
    private static function recordNotification($type, $userId, $dataId, $sent, $allowRepeat)
    {
        if ($sent) {
            Database::ExecutePreparedStatement('INSERT INTO notifications (type, user_id, data_id, sent) VALUES (?,?,?,?)', 'iiii', array($type, $userId, $dataId, 1));
        } else if ($allowRepeat) {
            // only suppress if same notification not yet sent
            if (!Database::RecordExists("notifications WHERE type=$type AND user_id=$userId AND data_id=$dataId AND sent=0"))
                Database::ExecutePreparedStatement('INSERT INTO notifications (type, user_id, data_id, sent) VALUES (?,?,?,?)', 'iiii', array($type, $userId, $dataId, 0));
        } else {
            // suppress if same notification has ever been sent or scheduled
            if (!Database::RecordExists("notifications WHERE type=$type AND user_id=$userId AND data_id=$dataId"))
                Database::ExecutePreparedStatement('INSERT INTO notifications (type, user_id, data_id, sent) VALUES (?,?,?,?)', 'iiii', array($type, $userId, $dataId, 0));
        }
    }

    private static function sendEmailValidation($userId)
    {
        $token = Token::GetTokenByTypeAndUserId(Token::TOKEN_TYPE_VALIDATION, $userId);

        $bodyText = $bodyHtml = array('Memofrog Email Validation',
            'You\'re almost done! Please click the link below to validate your email address.');

        $bodyHtml[] = '<a href="https://www.memofrog.com/frog?' . Key::KEY_VALIDATION_KEY . '=' . $token->key . '">Click Here.</a>';
        $bodyText[] = 'https://www.memofrog.com/frog?' . Key::KEY_VALIDATION_KEY . '=' . $token->key;

        Mail::SendMail($userId, self::NOTIFY_EMAIL_VALIDATION, $token->data, "Memofrog Email Validation", $bodyHtml, $bodyText);
    }

    private static function sendNewShareEmail($userId, $shareId, $notificationId)
    {
        $row = Database::QueryOneRow('SELECT * FROM shares WHERE id=' . $shareId);
        if ($row) {
            $shareId = (int)$row['id'];
            $targetUserId = (int)$row['target_user_id'];
            $sourceEnabled = boolval($row['source_enabled']);
            if ($targetUserId === $userId && $sourceEnabled) {
                $numMemos = Controller::GetNumberOfMemosAssociatedWithShare($shareId, $userId, false);
                if ($numMemos > 0) {
                    if (Option::GetOptionValue($userId, Option::OPTION_SEND_EMAIL_ON_SHARE) > 0) {
                        $sendMethod = self::SEND_METHOD_EMAIL;

                        $sourceUserId = (int)$row['source_user_id'];
                        $sourceRealName = Account::GetRealName($sourceUserId);
                        $sourceScreenName = Account::GetScreenName($sourceUserId);
                        $memoCountText = Content::Pluralize($numMemos, 'memo');
                        $title = 'New memo' . ($numMemos > 1 ? 's' : '') . " shared from $sourceRealName (@$sourceScreenName)";
                        $tags = Controller::GetTagsAsString($shareId);
                        $bodyText = "$sourceRealName has shared " .
                            ($numMemos > 1 ? "$memoCountText with you that are" : 'a memo that is') .
                            " tagged with $tags. Click the link below to view " . ($numMemos > 1 ? 'them' : 'it') . ':';

                        $keyShareId = Key::KEY_EXTERNAL_SHARE_ID;
                        $linkHtml = "<a href=\"https://www.memofrog.com/frog?$keyShareId=$shareId\">Click Here.</a>";
                        $linkText = "Click to view memos: https://www.memofrog.com/frog?$keyShareId=$shareId";

                        $bodyHtml = array($title, $bodyText, $linkHtml);
                        $bodyText = array($title, $bodyText, $linkText);

                        Mail::SendMail($userId, self::NOTIFY_NEW_SHARE, '', $title, $bodyHtml, $bodyText);
                    } else {
                        $sendMethod = self::SEND_METHOD_NONE;
                    }
                    Database::ExecuteQuery("UPDATE notifications SET sent=1, send_method=$sendMethod WHERE id = $notificationId");

                    return 1;
                } else {
                    return 0;
                }
            }
        }
        Database::ExecuteQuery("DELETE FROM notifications WHERE id = $notificationId");
        return 0;
    }

    private static function sendNewDirectShareEmail($userId, $memoId, $notificationId)
    {
        if (Controller::UserCanViewMemo($userId, $memoId)) {
            if (Option::GetOptionValue($userId, Option::OPTION_SEND_EMAIL_ON_SHARE) > 0) {
                $sendMethod = self::SEND_METHOD_EMAIL;
                $authorId = Controller::GetMemoAuthor($memoId);
                $authorRealName = Account::GetRealName($authorId);
                $authorScreenName = Account::GetScreenName($authorId);

                $title = "New memo shared from $authorRealName (@$authorScreenName)";
                $bodyText1 = "$authorRealName has shared this memo with you:";
                $memoText = Controller::GetMemoText($memoId);
                $bodyText2 = "Click the link below to view it on memofrog.com:";

                $linkHtml = sprintf('<a href="https://www.memofrog.com/frog?%s=%d">Click Here.</a>',
                    Key::KEY_EXTERNAL_MEMO_ID,
                    $memoId);

                $linkText = sprintf('Click to view memos: https://www.memofrog.com/frog?%s=%d',
                    Key::KEY_EXTERNAL_MEMO_ID,
                    $memoId);

                $bodyHtml = array($title, $bodyText1, $memoText, $bodyText2, $linkHtml);
                $bodyText = array($title, $bodyText1, $memoText, $bodyText2, $linkText);

                Mail::SendMail($userId, self::NOTIFY_NEW_DIRECT_SHARE, '', $title, $bodyHtml, $bodyText);
            } else {
                $sendMethod = self::SEND_METHOD_NONE;
            }
            Database::ExecuteQuery("UPDATE notifications SET sent=1, send_method=$sendMethod WHERE id = $notificationId");
            return 1;
        }
        Database::ExecuteQuery("DELETE FROM notifications WHERE id = $notificationId");
        return 0;
    }

    /**
     * @param $userId int
     * @param $memoId int
     * @param $notificationId int
     * @return int
     */
    private static function sendAlarmEmail($userId, $memoId, $notificationId)
    {
        if (Option::GetOptionValue($userId, Option::OPTION_SEND_EMAIL_ON_ALARM) > 0) {
            $sendMethod = self::SEND_METHOD_EMAIL;


            $sql = <<< EOT
SELECT
    memos.memo_text AS memo_text,
    accounts.email AS email,
    shared_memo_status.bucket AS bucket
FROM
    accounts
        INNER JOIN
    shared_memo_status ON accounts.user_id = shared_memo_status.user_id
        INNER JOIN
    memos ON shared_memo_status.memo_id = memos.id
WHERE
    shared_memo_status.user_id=$userId AND
    memos.id=$memoId
EOT;
            $row = Database::QueryOneRow($sql);
            $bucket = (int)$row['bucket'];
            $memoText = $row['memo_text'];
            $email = $row['email'];

            $title = 'Memofrog Memo Alarm';

            if ($bucket === Bucket::BUCKET_HOT_LIST)
                $mailText = 'The following memo has just been starred based on your alarm:';
            else
                $mailText = 'The following memo has just been starred and sent to the ' . Bucket::GetShortBucketName(Bucket::BUCKET_HOT_LIST) . ' based on your alarm:';

            $bodyHtml = array($title, $mailText, $memoText,
                sprintf("<a href=\"https://www.memofrog.com/frog?%s=%d\">Click to view memo</a>.",
                    Key::KEY_EXTERNAL_MEMO_ID,
                    $memoId));

            $bodyText = array($title, $mailText, $memoText,
                sprintf('Click to view memo: https://www.memofrog.com/frog?%s=%d',
                    Key::KEY_EXTERNAL_MEMO_ID,
                    $memoId));

            Mail::SendMail($userId, self::NOTIFY_MEMO_ALARM, $email, $title, $bodyHtml, $bodyText);
        } else {
            $sendMethod = self::SEND_METHOD_NONE;
        }

        Database::ExecuteQuery("UPDATE notifications SET sent=1, send_method=$sendMethod WHERE id = $notificationId");

        return 1;
    }

    /**
     * @param $userId
     */
    private static function notifyForgotPasswordInstructions($userId)
    {
        // save reset token
        $passwordResetKey = Util::GetRandomHexString(32);
        $expires = Util::GetNowOffsetAsString("P2D");
        Database::ExecutePreparedStatement("INSERT INTO password_reset_tokens (user_id, token, expires) VALUES (?, ?, ?)", "iss", array($userId, $passwordResetKey, $expires));

        $title = 'Memofrog Reset Password';
        $bodyHtml = array($title,
            'Please click the link below to reset your password.',
            '<a href="https://www.memofrog.com/frog?' . Key::KEY_PASSWORD_RESET_KEY . "=$passwordResetKey\">Click Here.</a>");
        $bodyText = array($title,
            'Please click the link below to reset your password.',
            'https://www.memofrog.com/frog?' . Key::KEY_PASSWORD_RESET_KEY . "=$passwordResetKey");

        Mail::SendMail($userId, self::NOTIFY_FORGOT_PASSWORD_INSTRUCTIONS, '', $title, $bodyHtml, $bodyText);
    }
}