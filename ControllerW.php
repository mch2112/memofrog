<?php

class ControllerW
{
    /**
     * @param $userId int
     */
    private static function incrementDataVersion($userId)
    {
        Database::ExecuteQuery("UPDATE users SET data_version = data_version + 1 WHERE id=$userId");
    }

    /**
     * @param $shareId int
     */
    private static function incrementDataVersionsForShare($shareId) {
        $row = Database::QueryOneRow('SELECT source_user_id, target_user_id FROM shares WHERE id = '. $shareId);
        if ($row) {
            self::incrementDataVersion((int)$row['source_user_id']);
            self::incrementDataVersion((int)$row['target_user_id']);
        }
    }
    /**
     * @param $memoId int
     */
    public static function incrementDataVersionForMemo($memoId)
    {
        Database::QueryCallback("SELECT user_id FROM shared_memo_status WHERE memo_id=$memoId", function ($row) {
            self::incrementDataVersion((int)$row['user_id']);
        });
    }

    /* @param $userId int
     * @param $memoId int
     * @param $star bool
     */
    public static function SetStar($userId, $memoId, $star)
    {
        $sql = 'UPDATE shared_memo_status SET star=? WHERE user_id=? AND memo_id=?';
        if (Database::ExecutePreparedStatement($sql, 'iii', array($star ? 1 : 0, $userId, $memoId))) {
            self::incrementDataVersion($userId);
            self::UpdateSortKeys($memoId, $userId);
        }
    }

    /* @param $shareId int
     * @param $reciprocalId int
     * @param $undelete bool
     */
    public static function DeleteShare($shareId, $reciprocalId, $undelete)
    {
        $val = $undelete ? 0 : 1;

        foreach (array($shareId, $reciprocalId) as $s) {
            if ($s > 0) {
                self::incrementDataVersionsForShare($s);
                if (Database::ExecutePreparedStatement('UPDATE shares SET deleted=? WHERE id=?', 'ii', array($val, $s))) {
                    if ($undelete)
                        ShareDbUpdater::UpdateShareData(ShareDbUpdater::UPDATE_SHARE_ENABLE, $s);
                    else
                        ShareDbUpdater::UpdateShareData(ShareDbUpdater::UPDATE_SHARE_DISABLE, $s);
                }
            }
        }
    }

    /* @param $shareId int
     * @param $isSource bool
     * @param $enable bool
     */
    public static function SetShareEnable($shareId, $isSource, $enable)
    {
        $dir = $isSource ? 'source' : 'target';
        $val = $enable ? 1 : 0;
        $sql = "UPDATE shares SET {$dir}_enabled=? WHERE id=?";

        if (Database::ExecutePreparedStatement($sql, 'ii', array($val, $shareId))) {
            if ($enable) {
                ShareDbUpdater::UpdateShareData(ShareDbUpdater::UPDATE_SHARE_ENABLE, $shareId);
                if ($isSource)
                    Notification::Notify(Controller::GetShareTarget($shareId), Notification::NOTIFY_NEW_SHARE, $shareId);
            } else {
                ShareDbUpdater::UpdateShareData(ShareDbUpdater::UPDATE_SHARE_DISABLE, $shareId);
            }
            self::incrementDataVersionsForShare($shareId);
        }
    }

    /* @param $shareId int
     * @return bool
     */
    public static function ToggleShareCanEdit($shareId)
    {
        Database::ExecuteQuery("UPDATE shares SET can_edit=1-can_edit WHERE id=$shareId");
        $canEdit = Database::LookupValue('shares', 'id', $shareId, 'i', 'can_edit', 0);
        ShareDbUpdater::UpdateShareData(ShareDbUpdater::UPDATE_SHARE_ENABLE, $shareId);
        self::incrementDataVersionsForShare($shareId);
        return $canEdit;
    }

    public static function NukeShare($shareId)
    {
        $reciprocal = Controller::GetReciprocalShareId($shareId);

        Database::ExecutePreparedStatement('DELETE FROM shares WHERE id=? OR id=?', 'ii', array($shareId, $reciprocal));
        Database::ExecutePreparedStatement('DELETE FROM shares_tags WHERE share_id=? OR share_id=?', 'ii', array($shareId, $reciprocal));
        Database::ExecutePreparedStatement('DELETE FROM memos_shares WHERE share_id=? OR share_id=?', 'ii', array($shareId, $reciprocal));
    }

    public static function NukeMemo($memoId)
    {
        self::incrementDataVersionForMemo($memoId);
        Database::ExecutePreparedStatement("DELETE FROM memos WHERE id=?", 'i', array($memoId));
        Database::ExecutePreparedStatement("DELETE FROM memos_tags WHERE memo_id=?", 'i', array($memoId));
        Database::ExecutePreparedStatement("DELETE FROM memos_shares WHERE memo_id=?", 'i', array($memoId));
        Database::ExecutePreparedStatement("DELETE FROM shared_memo_status WHERE memo_id=?", 'i', array($memoId));
        Database::ExecutePreparedStatement("DELETE FROM direct_shares WHERE memo_id=?", 'i', array($memoId));

        $history = Controller::GetHistory($memoId);

        foreach ($history as $historyId) {
            Database::ExecutePreparedStatement('DELETE FROM history WHERE id=?', 'i', array($historyId));
        }
    }

    /*
     * @param $userId int
     */
    public static function NukeUser($userId)
    {
        Database::QueryCallback("SELECT * FROM memos WHERE user_id=$userId", function ($row) {
            self::NukeMemo($row['id']);
        });

        Database::QueryCallback("SELECT * FROM shares WHERE source_user_id=$userId", function ($row) {
            $shareId = $row['id'];
            Database::ExecuteQuery("DELETE FROM shares_tags WHERE share_id=$shareId");
        });
        Database::QueryCallback("SELECT * FROM shares WHERE target_user_id=$userId", function ($row) {
            $shareId = $row['id'];
            Database::ExecuteQuery("DELETE FROM shares_tags WHERE share_id=$shareId");
        });

        Database::ExecutePreparedStatement("DELETE FROM shared_memo_status WHERE user_id=?", 'i', array($userId));
        Database::ExecutePreparedStatement("DELETE FROM shares WHERE source_user_id=?", 'i', array($userId));
        Database::ExecutePreparedStatement("DELETE FROM shares WHERE target_user_id=?", 'i', array($userId));
        Database::ExecutePreparedStatement("DELETE FROM options WHERE user_id=?", 'i', array($userId));
        Database::ExecutePreparedStatement("DELETE FROM users WHERE id=?", 'i', array($userId));
        Database::ExecutePreparedStatement("DELETE FROM accounts WHERE user_id=?", 'i', array($userId));
        Token::DeleteTokensForUser($userId, Token::TOKEN_TYPE_ALL);
    }

    /* @param $userId int
     * @param $oldTag string
     * @param $newTag string
     * @return int
     */
    public static function RenameTag($userId, $oldTag, $newTag)
    {
        $count = 0;

        if ($oldTag !== $newTag) {

            $regEx = '/(^|\s)+#' . $oldTag . '($|[^a-zA-Z0-9_])/i';
            $replace = '$1#' . $newTag . '$2';

            $oldTagId = Controller::GetTagId($oldTag);

            if ($oldTagId > 0) {
                Database::QueryCallback("SELECT memos.id AS memo_id, memos.memo_text AS memo_text FROM memos INNER JOIN memos_tags ON memos.id=memos_tags.memo_id WHERE memos_tags.tag_id = $oldTagId AND memos.user_id=$userId AND memos.deleted=0 AND memos_tags.valid=1",
                    function ($row) use (&$count, $regEx, $replace, $userId) {
                        $memoId = (int)$row['memo_id'];
                        $oldText = $row['memo_text'];
                        $newText = preg_replace($regEx, $replace, $oldText);
                        if ($oldText !== $newText) {
                            ++$count;
                            self::EditMemo($userId, $memoId, $newText, Controller::GetPrivateStatus($memoId));
                        }
                    });
            }
        }
        return $count;
    }

    /**
     * @param $userId
     * @param $memoId
     */
    public static function DeleteMemo($userId, $memoId)
    {
        $authorId = Controller::GetMemoAuthor($memoId);
        if ($authorId === $userId) {
            // Delete for everyone
            Database::ExecutePreparedStatement('UPDATE memos SET deleted=1 WHERE memos.id=?', 'i', array($memoId));
            Database::ExecutePreparedStatement('UPDATE shared_memo_status SET bucket=?, visible=?, star=?, alarm_date=NULL WHERE memo_id=?', 'iiii', array(Bucket::BUCKET_DELETED, 0, 0, $memoId));
            ShareDbUpdater::UpdateShareData(ShareDbUpdater::UPDATE_MEMO_DELETED, $memoId);
            self::incrementDataVersionForMemo($memoId);
        } else {
            // Delete only for viewer (who isn't the author)
            Database::ExecutePreparedStatement('UPDATE shared_memo_status SET bucket=?, visible=?, alarm_date=NULL WHERE memo_id=? AND user_id=?', 'iiii', array(Bucket::BUCKET_DELETED, 0, $memoId, $userId));
            self::incrementDataVersion($userId);
        }
    }

    /*
     * Sets the bucket and optionally the star
     *
     * @param $userId int
     * @param $memoId int
     * @param $bucket int
     * @param $star bool
     */
    public static function SetBucket($userId, $memoId, $bucket, $star = null)
    {
        $row = Database::QueryOneRow("SELECT shared_memo_status.bucket AS bucket, shared_memo_status.star AS star, memos.sync_buckets AS sync_buckets FROM shared_memo_status INNER JOIN memos ON shared_memo_status.memo_id=memos.id WHERE shared_memo_status.user_id=$userId AND shared_memo_status.memo_id=$memoId");
        if ($row) {
            $prevBucket = (int)$row['bucket'];
            $oldStar = (bool)$row['star'];
            $syncBuckets = (bool)$row['sync_buckets'];
            if (is_null($star))
                $star = $oldStar;

            if ($bucket === $prevBucket) {
                if ($star !== $oldStar)
                    ControllerW::SetStar($userId, $memoId, $star);
            } else {
                if ($bucket === Bucket::BUCKET_DELETED) {
                    ControllerW::DeleteMemo($userId, $memoId);
                } else if ($syncBuckets) {
                    $sql = 'UPDATE shared_memo_status SET prev_bucket=bucket, bucket=?, last_bucket_change=NOW() WHERE memo_id=? AND shared_memo_status.bucket<>?';
                    Database::ExecutePreparedStatement($sql, 'iii', array($bucket, $memoId, Bucket::BUCKET_DELETED));
                    if ($star !== $oldStar)
                        ControllerW::SetStar($userId, $memoId, $star);
                    self::incrementDataVersionForMemo($memoId);
                    self::UpdateSortKeys($memoId);
                } else {
                    $sql = 'UPDATE shared_memo_status SET prev_bucket=bucket, bucket=?, star=?, last_bucket_change=NOW() WHERE memo_id=? AND user_id=?;';
                    Database::ExecutePreparedStatement($sql, 'iiii', array($bucket, $star ? 1 : 0, $memoId, $userId));
                    self::incrementDataVersion($userId);
                    self::UpdateSortKeys($memoId, $userId);
                }
            }
        }
    }

    /* @param $userId int
     * @param $memoId int
     * @return int
     */
    public static function RevertBucket($userId, $memoId)
    {
        $row = Database::QueryOneRow("SELECT prev_bucket FROM shared_memo_status WHERE user_id=$userId AND memo_id=$memoId");
        $prevBucket = (int)$row['prev_bucket'];
        self::SetBucket($userId, $memoId, $prevBucket);
        return $prevBucket;
    }

    /* @param $userId int */
    public static function EmptyTrash($userId)
    {
        $trashBucket = Bucket::BUCKET_TRASH;
        Database::QueryCallback("SELECT memo_id FROM shared_memo_status WHERE user_id=$userId AND bucket=$trashBucket", function ($row) use ($userId) {
            self::SetBucket($userId, (int)$row['memo_id'], Bucket::BUCKET_DELETED);
        });
    }

    /* @param $userId int
     * @param $memoText string
     * @param $bucket int
     * @param $private bool
     * @param $canEdit bool
     * @param $syncBuckets bool
     * @return int
     */
    public static function CreateMemo($userId, $memoText, $bucket, $private = false, $canEdit = false, $syncBuckets = false)
    {
        if (strlen($memoText) > 0) {

            $starVal = 0;
            $privateVal = $private ? 1 : 0;
            $canEditVal = $canEdit ? 1 : 0;
            $syncBucketVal = $syncBuckets ? 1 : 0;

            Database::ExecutePreparedStatement('INSERT INTO memos (user_id, updated, memo_text, ip_at_create, private, friends_can_edit, sync_buckets) VALUES (?,NOW(),?,?,?,?,?)',
                "issiii", array($userId, $memoText, $_SERVER['REMOTE_ADDR'], $privateVal, $canEditVal, $syncBucketVal), $memoId);

            $insertSql = 'INSERT INTO shared_memo_status (memo_id, user_id, is_author, available, visible, bucket, star, can_edit) VALUES(?,?,?,?,?,?,?,?)';

            Database::ExecutePreparedStatement($insertSql,
                'iiiiiiii',
                array($memoId, $userId, 1, 1, 1, $bucket, $starVal, 1));

            if (self::AssociateTags($memoId, $memoText, true, $private))
                ShareDbUpdater::UpdateShareData(ShareDbUpdater::UPDATE_MEMO_NEW, $memoId);

            self::incrementDataVersionForMemo($memoId);
            self::UpdateSortKeys($memoId);

            return $memoId;
        }
        return 0;
    }

    /* @param $userId int
     * @param $memoId int
     * @param $memoText string
     * @param $private bool
     * @param $friendsCanEdit bool
     * @param $syncBuckets bool
     * @return bool
     */
    public static function EditMemo($userId, $memoId, $memoText = null, $private = null, $friendsCanEdit = null, $syncBuckets = null)
    {
        $row = Database::QueryOneRow("SELECT memo_text, private, friends_can_edit, sync_buckets FROM memos WHERE memos.id=$memoId");

        $oldMemoText = $row['memo_text'];
        $oldPrivateVal = (int)$row['private'];
        $oldCanEditVal = (int)$row['friends_can_edit'];
        $oldSyncBucketsVal = (int)$row['sync_buckets'];

        if (is_null($memoText))
            $newMemoText = $oldMemoText;
        else
            $newMemoText = $memoText;

        if (is_null($private)) {
            $newPrivateVal = $oldPrivateVal;
            $private = $oldPrivateVal > 0;
        } else {
            $newPrivateVal = $private ? 1 : 0;
        }

        if (is_null($friendsCanEdit))
            $newCanEditVal = $oldCanEditVal;
        else
            $newCanEditVal = $friendsCanEdit ? 1 : 0;

        if (is_null($syncBuckets))
            $newSyncBucketsVal = $oldSyncBucketsVal;
        else
            $newSyncBucketsVal = $syncBuckets ? 1 : 0;

        $textIsDifferent = ($newMemoText !== $oldMemoText);
        $privateIsDifferent = ($newPrivateVal !== $oldPrivateVal);
        $canEditIsDifferent = ($newCanEditVal !== $oldCanEditVal);
        $syncBucketsIsDifferent = ($oldSyncBucketsVal !== $newSyncBucketsVal);

        $anyDifferent = $textIsDifferent || $privateIsDifferent || $canEditIsDifferent || $syncBucketsIsDifferent;

        if ($anyDifferent) {
            $tokensDifferent = false;
            if ($textIsDifferent) {
                Database::ExecutePreparedStatement("INSERT INTO history (updated, previous_version, user_id, memo_text, ip_at_create, edited_by) SELECT memos.updated, memos.previous_version, memos.user_id, memos.memo_text, memos.ip_at_create, memos.edited_by FROM memos WHERE id=?",
                    'i',
                    array($memoId),
                    $historyId);
                $now = Util::GetNowAsString();
                Database::ExecutePreparedStatement('UPDATE memos SET updated=NOW(), memo_text=?, previous_version=?, ip_at_create=?, updated=?, edited_by=?, private=?, friends_can_edit=?, sync_buckets=? WHERE id=?', 'sissiiiii', array($memoText, $historyId, $_SERVER['REMOTE_ADDR'], $now, $userId, $newPrivateVal, $newCanEditVal, $newSyncBucketsVal, $memoId));
                $tokensDifferent = self::AssociateTags($memoId, $memoText, false, $private);
            } else {
                Database::ExecutePreparedStatement('UPDATE memos SET private=?, friends_can_edit=?, sync_buckets=? WHERE id =?', 'iiii', array($newPrivateVal, $newCanEditVal, $newSyncBucketsVal, $memoId));
            }
            if ($tokensDifferent)
                ShareDbUpdater::UpdateShareData(ShareDbUpdater::UPDATE_MEMO_EDIT, $memoId);
            if ($privateIsDifferent)
                ShareDbUpdater::UpdateShareData($private ? ShareDbUpdater::UPDATE_MEMO_PRIVATE : ShareDbUpdater::UPDATE_MEMO_NOT_PRIVATE, $memoId);
            if ($canEditIsDifferent)
                ShareDbUpdater::UpdateShareData($friendsCanEdit ? ShareDbUpdater::UPDATE_MEMO_FRIENDS_CAN_ALWAYS_EDIT : ShareDbUpdater::UPDATE_MEMO_FRIENDS_CAN_NOT_ALWAYS_EDIT, $memoId);
            self::incrementDataVersionForMemo($memoId);
            self::UpdateSortKeys($memoId);
        }
        return $anyDifferent;
    }

    /* @param $userId int
     * @param $memoId int
     * @param $alarmDate DateTime
     */
    public static function EditAlarm($userId, $memoId, $alarmDate)
    {
        if (is_null($alarmDate))
            $rowCount = Database::ExecutePreparedStatement('UPDATE shared_memo_status SET alarm_date=NULL WHERE user_id=? AND memo_id=?', 'ii', array($userId, $memoId));
        else
            $rowCount = Database::ExecutePreparedStatement('UPDATE shared_memo_status SET alarm_date=? WHERE user_id=? AND memo_id=?', 'sii', array(Util::FormatDate($alarmDate), $userId, $memoId));
        if ($rowCount)
            self::incrementDataVersion($userId);
    }

    /* @param $memoId int
     * @param $memoText string
     * @param $isNewMemo bool
     * @param $isPrivate bool
     * @return bool
     */
    public static function AssociateTags($memoId, $memoText, $isNewMemo, $isPrivate)
    {
        if (!$isNewMemo) {
            Database::ExecutePreparedStatement('UPDATE memos_tags SET new_valid=? WHERE memo_id=?', 'ii', array(0, $memoId));
            Database::ExecutePreparedStatement('UPDATE direct_shares SET new_valid=? WHERE memo_id=?', 'ii', array(0, $memoId));
        }

        $changed = false;

        // TAGS
        preg_match_all(RegEx::TAG_REGEX, $memoText, $tags, PREG_PATTERN_ORDER);
        foreach ($tags[1] as $tag) {
            $tagId = ControllerW::saveTag($tag);
            $sql = 'INSERT INTO memos_tags (memo_id, tag_id, valid, new_valid) VALUES (?, ?, 0, 1) ON DUPLICATE KEY UPDATE new_valid=1';
            Database::ExecutePreparedStatement($sql, 'ii', array($memoId, $tagId));
        }
        $changed |= (Database::ExecutePreparedStatement('UPDATE memos_tags SET valid=new_valid WHERE memo_id=?', 'i', array($memoId)) > 0);

        if (!$isPrivate) {
            preg_match_all(RegEx::SCREEN_NAME_REGEX, $memoText, $screenNames, PREG_PATTERN_ORDER);
            foreach ($screenNames[1] as $screenName) {
                if (($userId = Account::GetUserIdFromScreenName($screenName)) > 0) {
                    $sql = 'INSERT INTO direct_shares (memo_id, user_id, valid, new_valid) VALUES (?, ?, 0, 1) ON DUPLICATE KEY UPDATE new_valid=1';
                    Database::ExecutePreparedStatement($sql, 'ii', array($memoId, $userId));
                    Notification::Notify($userId, Notification::NOTIFY_NEW_DIRECT_SHARE, $memoId);
                }
            }
        }
        $changed |= (Database::ExecutePreparedStatement('UPDATE direct_shares SET valid=new_valid WHERE memo_id=?', 'i', array($memoId)) > 0);

        return $changed;
    }

    public static function FindOrCreateShare($sourceUserId, $targetUserId, $tagsAsString, $sourceEnabled, $targetEnabled, $canEdit, &$isNew)
    {
        $tagIds = Controller::GetTagIdsFromString($tagsAsString, true);
        $count = count($tagIds);

        if ($count === 0)
            return 0;

        $shareId = Controller::findShare($sourceUserId, $targetUserId, $tagIds, $deleted);

        if ($shareId === 0) {
            $isNew = true;
            Database::ExecutePreparedStatement("INSERT INTO shares (source_user_id, target_user_id, tag_count, source_enabled, target_enabled, can_edit) VALUES (?,?,?,?,?,?)", 'iiiiii', array($sourceUserId, $targetUserId, $count, $sourceEnabled ? 1 : 0, $targetEnabled ? 1 : 0, $canEdit ? 1 : 0), $shareId);
            foreach ($tagIds as $tagId)
                Database::ExecutePreparedStatement("INSERT INTO shares_tags (share_id, tag_id) VALUES (?,?)", 'ii', array($shareId, $tagId));

            Notification::Notify($targetUserId, Notification::NOTIFY_NEW_SHARE, $shareId);

            $needUpdate = true;
        } else {
            $isNew = false;
            $rowsChanged = Database::ExecutePreparedStatement('UPDATE shares SET source_enabled=?, target_enabled=?, can_edit=?, deleted=? WHERE id=?', 'iiiii', array($sourceEnabled ? 1 : 0, $targetEnabled ? 1 : 0, $canEdit ? 1 : 0, 0, $shareId));

            if ($deleted) {
                $reciprocal = Controller::GetReciprocalShareId($shareId);
                Database::ExecutePreparedStatement('UPDATE shares SET source_enabled=?, target_enabled=?, can_edit=?, deleted=? WHERE id=?', 'iiiii', array(0, 1, 0, 0, $reciprocal));
            }
            $needUpdate = $deleted || ($rowsChanged > 0);
        }
        if ($needUpdate) {
            ShareDbUpdater::UpdateShareData($isNew ? ShareDbUpdater::UPDATE_SHARE_NEW : ShareDbUpdater::UPDATE_SHARE_ENABLE, $shareId);
            Controller::GetOrCreateReciprocalShareId($shareId);
            self::UpdateSortKeys();
        }

        return $shareId;
    }

    /* @param $tag string
     * @return int tag id
     */
    public static function saveTag($tag)
    {
        $tag = strtolower($tag);
        $id = Database::LookupValue('tags', 'tag', $tag, 's', 'id', 0);
        if ($id === 0)
            Database::ExecutePreparedStatement('INSERT INTO tags (tag) VALUES (?)', 's', array($tag), $id);
        return $id;
    }

    public static function UpdateSortKeys($memoId=0, $userId=0)
    {
        $sql = <<< EOT
UPDATE memos INNER JOIN shared_memo_status ON memos.id = shared_memo_status.memo_id
SET sort_key =
CASE
WHEN shared_memo_status.star = 0 OR shared_memo_status.bucket=250 OR shared_memo_status.bucket=310 THEN
    CONCAT('100|', DATE_FORMAT(memos.updated, '%y-%m-%d %k-%i-%s'))
ELSE
    CASE
        WHEN shared_memo_status.bucket=110 THEN
            CONCAT('400|', DATE_FORMAT(memos.updated, '%y-%m-%d %k-%i-%s'))
        WHEN shared_memo_status.bucket=120 THEN
            CONCAT('300|', DATE_FORMAT(memos.updated, '%y-%m-%d %k-%i-%s'))
        ELSE
            CONCAT('200|', DATE_FORMAT(memos.updated, '%y-%m-%d %k-%i-%s'))
    END
END
EOT;
        if ($userId <= 0)
            if ($memoId <= 0)
                $sql .= " WHERE sort_key IS NULL";
            else
                $sql .= " WHERE memos.id=$memoId";
        else
            $sql .= " WHERE memos.id=$memoId AND shared_memo_status.user_id=$userId";

        Database::ExecuteQuery($sql);
    }
}