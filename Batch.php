<?php

class Batch
{
    const BATCH_DELETE_OLD_TRASH = 210;
    const BATCH_MOVE_DONE_TO_TRASH = 220;
    const BATCH_ALARM = 230;
    const BATCH_NUKE_DELETED = 240;
    const BATCH_SEND_NOTIFICATIONS = 250;

    const DAYS_DONE_TO_TRASH = 14;
    const DAYS_TRASH_TO_DELETED = 14;
    const DAYS_DELETED_TO_NUKE = 1;

    public static function RunAll()
    {
        $results = array();

        foreach (array(self::BATCH_MOVE_DONE_TO_TRASH,
                   self::BATCH_DELETE_OLD_TRASH,
                   self::BATCH_ALARM,
                   self::BATCH_NUKE_DELETED,
                   self::BATCH_SEND_NOTIFICATIONS) as $batch)
            $results[] = self::Run($batch);

        return join($results, '<br>');
    }

    public static function Run($batchId)
    {
        switch ($batchId) {
            case self::BATCH_DELETE_OLD_TRASH:
                return self::deleteOldTrash();
            case self::BATCH_MOVE_DONE_TO_TRASH:
                return self::moveDoneToTrash();
            case self::BATCH_ALARM:
                return self::alarm();
            case self::BATCH_NUKE_DELETED:
                return self::nukeDeleted();
            case self::BATCH_SEND_NOTIFICATIONS:
                return self::sendNotifications();
            default:
                return "Batch ID $batchId not found.";
        }
    }

    private static function alarm()
    {
        $count = 0;
        $ok = false;
        try {
            $sql = 'SELECT shared_memo_status.id AS id FROM shared_memo_status INNER JOIN accounts on shared_memo_status.user_id=accounts.user_id WHERE shared_memo_status.alarm_date IS NOT NULL AND NOW() >= (shared_memo_status.alarm_date + INTERVAL (accounts.time_zone_offset - 1) MINUTE) AND shared_memo_status.visible=1 ' .
                    ' AND (bucket<' . Bucket::BUCKET_TRASH . ' OR bucket=' . Bucket::BUCKET_HIDDEN . ')';

            $smsIds = array();
            Database::QueryCallback($sql, function ($row) use (&$smsIds) {
                $smsIds[] = (int)$row['id'];
            });

            foreach ($smsIds as $smsId) {
                $sql = <<< EOT
SELECT
    memos.id AS memo_id,
    shared_memo_status.user_id AS user_id,
    shared_memo_status.bucket AS bucket
FROM
    accounts
        INNER JOIN
    shared_memo_status ON accounts.user_id = shared_memo_status.user_id
INNER JOIN
    memos ON shared_memo_status.memo_id = memos.id
WHERE
    shared_memo_status.id=$smsId
EOT;
                $row = Database::QueryOneRow($sql);
                $userId = (int)$row['user_id'];
                $memoId = (int)$row['memo_id'];
                $bucket = (int)$row['bucket'];

                ControllerW::EditAlarm($userId, $memoId, null);

                if ($bucket === Bucket::BUCKET_HOT_LIST)
                    ControllerW::SetStar($userId, $memoId, true);
                else
                    ControllerW::SetBucket($userId, $memoId, Bucket::BUCKET_HOT_LIST, true);

                Notification::Notify($userId, Notification::NOTIFY_MEMO_ALARM, $memoId);

                $count++;
            }
            $countMsg = Content::Pluralize($count, 'alarm');
            $ok = true;
            return "Alarm: OK ($countMsg)";
        } catch (Exception $e) {
            return "Alarm: Error";
        } finally {
            Database::ExecutePreparedStatement('INSERT INTO batch (batch_id, ok, items) VALUES (?,?,?)', 'iii', array(self::BATCH_ALARM, $ok, $count));
        }
    }
    private static function sendNotifications()
    {
        $ok = false;
        $count = 0;
        try {
            $count = Notification::ExecuteNotifications();
            $countMsg = Content::Pluralize($count, 'notification');
            $ok = true;
            return "Send Notifications: OK ($countMsg)";
        } catch (Exception $e) {
            return "Send Notifications: Error";
        } finally {
            Database::ExecutePreparedStatement('INSERT INTO batch (batch_id, ok, items) VALUES (?,?,?)', 'iii', array(self::BATCH_SEND_NOTIFICATIONS, $ok, $count));
        }

    }
    private static function deleteOldTrash()
    {
        $count = 0;
        $ok = false;
        try {
            $trashBucket = Bucket::BUCKET_TRASH;

            $days = self::DAYS_TRASH_TO_DELETED;
            Database::QueryCallback("SELECT shared_memo_status.memo_id AS memo_id, shared_memo_status.user_id AS viewer_id FROM shared_memo_status WHERE bucket=$trashBucket AND TIMESTAMPDIFF(DAY, last_bucket_change, NOW()) > $days", function ($row) use (&$count) {
                $memoId = (int)$row['memo_id'];
                $viewerId = (int)$row['viewer_id'];
                ControllerW::SetBucket($viewerId, $memoId, Bucket::BUCKET_DELETED, false);
                $count++;
            });

            $countMsg = Content::Pluralize($count, 'memo');
            $ok = true;
            return "Delete Old Trash: OK ($countMsg deleted)";
        } catch (Exception $e) {
            return "Delete Old Trash: Error";
        } finally {
            Database::ExecutePreparedStatement('INSERT INTO batch (batch_id, ok, items) VALUES (?,?,?)', 'iii', array(self::BATCH_DELETE_OLD_TRASH, $ok, $count));
        }
    }

    private static function nukeDeleted()
    {
        // disabled
        return "Nuke Deleted Memos: Disabled.";

        /*$deletedBucket = Bucket::BUCKET_DELETED;
        $memosToNuke = array();
        $days = self::DAYS_DELETED_TO_NUKE;

        Database::QueryCallback("SELECT memos.id FROM memos INNER JOIN shared_memo_status ON memos.id=shared_memo_status.memo_id AND memos.user_id=shared_memo_status.user_id WHERE shared_memo_status.bucket=$deletedBucket AND TIMESTAMPDIFF(DAY, last_bucket_change, NOW()) > $days", function ($row) use (&$memosToNuke) {
            $memosToNuke[] = $row['id'];
        });

        foreach ($memosToNuke as $memoId) {
            Controller::NukeMemo($memoId);
        }

        $countMsg = Content::pluralize(count($memosToNuke), 'memo');

        return "Nuke Deleted Memos: OK ($countMsg deleted)";*/
    }

    private static function moveDoneToTrash()
    {
        $ok = false;
        $count = 0;
        try {
            $optionId = Option::OPTION_MOVE_DONE_TO_TRASH;
            $bucketDone = Bucket::BUCKET_DONE;
            $bucketTrash = Bucket::BUCKET_TRASH;
            $days = self::DAYS_DONE_TO_TRASH;

            $sql = <<< EOT
UPDATE
    shared_memo_status
INNER JOIN
    (SELECT
        shared_memo_status.user_id AS user_id,
        shared_memo_status.memo_id AS memo_id,
        TIMESTAMPDIFF(DAY, shared_memo_status.last_bucket_change, NOW()) AS days_since_bucket_change
    FROM
        shared_memo_status
    LEFT JOIN
        options ON shared_memo_status.user_id = options.user_id AND option_id = $optionId
    WHERE
        shared_memo_status.bucket = $bucketDone
            AND
        (options.option_value > 0 OR options.option_value IS NULL)
    HAVING days_since_bucket_change > $days) q
ON
    shared_memo_status.user_id = q.user_id AND shared_memo_status.memo_id = q.memo_id
SET
    shared_memo_status.bucket=$bucketTrash,
    shared_memo_status.last_bucket_change=NOW();
EOT;
            $count = Database::ExecuteQuery($sql);
            $countMsg = Content::Pluralize($count, 'memo');
            $ok = true;
            return "Move Done to Trash: OK ($countMsg moved)";
        } catch (Exception $e) {
            return "Move Done to Trash: Error";
        } finally {
            Database::ExecutePreparedStatement('INSERT INTO batch (batch_id, ok, items) VALUES (?,?,?)', 'iii', array(self::BATCH_MOVE_DONE_TO_TRASH, $ok, $count));
        }
    }
}