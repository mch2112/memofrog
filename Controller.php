<?php

class Controller
{
    /* @param $email string
     * @param $screenName string
     * @param $realName string
     * @param $hashedPassword string
     * @param $sampleMemo bool
     * @return int
     */
    public static function RegisterUser($email, $screenName, $realName, $hashedPassword, $sampleMemo)
    {
        Database::ExecutePreparedStatement("INSERT INTO users (screen_name) VALUES (?)",
            's', array($screenName), $userId);

        Database::ExecutePreparedStatement("INSERT INTO accounts (user_id, email, real_name, password, ip_at_create) VALUES (?, ?, ?, ?, ?)",
            'issss', array($userId, $email, $realName, $hashedPassword, $_SERVER['REMOTE_ADDR']));

        Token::GenToken($userId, Token::TOKEN_TYPE_VALIDATION);

        if ($sampleMemo)
            self::createSampleMemos($userId);

        return $userId;
    }

    public static function GetUserAuthStatus($userId)
    {
        if (Database::LookupValue('users', 'id', $userId, 'i', 'admin', 0)) // ADMIN
            return LoginStatus::ADMIN;
        else
            return LoginStatus::OK;
    }


    /* @param $memoId int
     * @return string
     */
    public static function GetMemoText($memoId)
    {
        $row = Database::QueryOneRow("SELECT memo_text FROM memos WHERE id=$memoId");
        if ($row)
            return $row['memo_text'];
        else
            return '';
    }

    /* @param $memoId int
     * @return int
     */
    public static function GetMemoAuthor($memoId)
    {
        return (int)Database::LookupValue('memos', 'id', $memoId, 'i', 'user_id', 0);
    }

    public static function GetMemoDate($memoId, $isHistoric)
    {
        return Database::LookupValue($isHistoric ? 'history' : 'memos', 'id', $memoId, 'i', 'updated', '');
    }

    /* @param $userId int
     * @param $memoId int
     * @return int
     */
    public static function GetMemoBucket($userId, $memoId)
    {
        $row = Database::QueryOneRow("SELECT bucket FROM shared_memo_status WHERE user_id=$userId AND memo_id=$memoId");
        return (int)$row['bucket'];
    }

    /* @param $numMemosReq int
     * @param $memosToSkip int
     * @param $userId int
     * @param $shareUserId int
     * @param $shareTo bool
     * @param $tagsAsString string
     * @param $tagsOp int
     * @param $filterText string
     * @param $bucket int
     * @param $special int
     * @return array
     */
    public static function GetMemoData($numMemosReq, $memosToSkip, $userId, $shareUserId, $shareTo, $tagsAsString, $tagsOp, $filterText, $bucket, $special)
    {
        $sql = Sql::GetMemosSql($numMemosReq, $memosToSkip, $userId, $shareUserId, $shareTo, $tagsAsString, $tagsOp, $filterText, $bucket, $special);

 //       Util::Log($sql);

        $memos = array();

        if (strlen($sql) > 0) {
            Database::QueryCallback($sql,
                function ($row) use (&$memos) {
                    $memos[] = array(
                        Key::KEY_MEMO_ID => (int)$row['memo_id'],
                        Key::KEY_MEMO_TEXT => $row['memo_text'],
                        Key::KEY_MEMO_PRIVATE => (int)$row['private'],
                        Key::KEY_MEMO_SHARED => (int)$row['shared'],
                        Key::KEY_MEMO_SYNC_BUCKETS => (int)$row['sync_buckets'],
                        Key::KEY_MEMO_FRIENDS_CAN_EDIT => (int)$row['friends_can_edit'],
                        Key::KEY_MEMO_ALARM => $row['alarm_date'],
                        Key::KEY_MEMO_BUCKET => (int)$row['bucket'],
                        Key::KEY_MEMO_EDITED => ((int)$row['edited_by'] > 0) ? true : false,
                        Key::KEY_MEMO_STAR => (int)$row['star'],
                        Key::KEY_MEMO_CAN_EDIT => (int)$row['can_edit'],
                        Key::KEY_MEMO_AUTHOR_NAME => $row['screen_name'],
                        Key::KEY_MEMO_IS_AUTHOR => (int)$row['is_author'],
//                        Key::KEY_MEMO_TIMESTAMP => $row['date'],
                        Key::KEY_MEMO_CREATE_DATE => $row['create_date'],
                        Key::KEY_MEMO_EDIT_DATE => $row['edit_date'],
                        Key::KEY_MEMO_MOVE_DATE => $row['move_date'],
                        Key::KEY_MEMO_IS_HISTORIC => false);
                });
        }
        return $memos;
    }

    /* @param $userId int
     * @param $memoId int
     * @param $isHistoric bool
     * @return array
     */
    public static function GetSingleMemoData($userId, $memoId, $isHistoric)
    {
        $sql = Sql::GetSingleMemoSql($memoId, $userId, $isHistoric);

        $row = Database::QueryOneRow($sql);

        return array(
            Key::KEY_MEMO_ID => (int)$row['memo_id'],
            Key::KEY_MEMO_TEXT => $row['memo_text'],
            Key::KEY_MEMO_PRIVATE => (int)$row['private'],
            Key::KEY_MEMO_SHARED => (int)$row['shared'],
            Key::KEY_MEMO_SYNC_BUCKETS => (int)$row['sync_buckets'],
            Key::KEY_MEMO_FRIENDS_CAN_EDIT => (int)$row['friends_can_edit'],
            Key::KEY_MEMO_ALARM => $row['alarm_date'],
            Key::KEY_MEMO_BUCKET => (int)$row['bucket'],
            Key::KEY_MEMO_EDITED => ((int)$row['edited_by'] > 0) ? true : false,
            Key::KEY_MEMO_STAR => (int)$row['star'],
            Key::KEY_MEMO_CAN_EDIT => (int)$row['can_edit'],
            Key::KEY_MEMO_AUTHOR_NAME => $row['screen_name'],
            Key::KEY_MEMO_IS_AUTHOR => (int)$row['is_author'],
            //Key::KEY_MEMO_TIMESTAMP => $row['date'],
            Key::KEY_MEMO_CREATE_DATE => $row['create_date'],
            Key::KEY_MEMO_EDIT_DATE => $row['edit_date'],
            Key::KEY_MEMO_MOVE_DATE => $row['move_date'],
            Key::KEY_MEMO_IS_HISTORIC => $isHistoric);
    }

    /* @param $userId int
     * @param $memoId int
     * @return bool
     */
    public static function GetStar($userId, $memoId)
    {
        return boolval(Database::QueryOneRow("SELECT star FROM shared_memo_status WHERE user_id=$userId AND memo_id=$memoId")['star']);
    }


    /* @param $sourceUserId int
     * @param $targetUserId int
     * @param $tagIdList int[]
     * @param $deleted bool
     * @return int
     */
    public static function findShare($sourceUserId, $targetUserId, $tagIdList, &$deleted)
    {
        $tags = join(',', $tagIdList);
        $count = count($tagIdList);
        $sql = <<< EOT
SELECT
    shares.id AS share_id,
    shares.tag_count AS tags_needed,
    shares.deleted AS deleted,
    COUNT(shares_tags.tag_id)
FROM
    shares
        INNER JOIN
    shares_tags ON shares.id = shares_tags.share_id
WHERE
	shares.source_user_id = $sourceUserId AND
    shares.target_user_id = $targetUserId AND
	shares.tag_count = $count AND
    tag_id IN ($tags)
GROUP BY share_id
EOT;

        $row = Database::QueryOneRow($sql);
        if ($row) {
            $deleted = boolval($row['deleted']);
            return (int)$row['share_id'];
        }
        else {
            return 0;
        }
    }

    /* @param $shareId int
     * @return int
     */
    public static function GetReciprocalShareId($shareId)
    {
        return (int)Database::LookupValue('shares', 'id', $shareId, 'i', 'reciprocal', 0);
    }

    /* @param $shareId int
     * @return bool
     */
    public static function ShareIsEnabled($shareId)
    {
        $row = Database::QueryOneRow("SELECT * FROM shares WHERE id=$shareId");
        return boolval($row['source_enabled']) && boolval($row['target_enabled']);
    }

    /* @param $shareId int
     * @return int
     */
    public static function ComputeReciprocalShareId($shareId)
    {
        $sql = Sql::GetReciprocalShareSql($shareId);

        $row = Database::QueryOneRow($sql);
        if ($row)
            return (int)$row['s2_id'];
        else
            return 0;
    }

    /* @param $shareId int
     * @return string
     */
    public static function GetTagsAsString($shareId, $includeSymbol = true)
    {
        $symbol = $includeSymbol ? '#' : '';
        $sql =
            <<< EOT
SELECT
    GROUP_CONCAT(tag SEPARATOR '+') AS tags
FROM
(SELECT
    shares.id AS share_id,
    CONCAT('$symbol', tags.tag) AS tag
FROM
    shares
    INNER JOIN shares_tags ON shares.id = shares_tags.share_id
    INNER JOIN tags ON tags.id = shares_tags.tag_id
    WHERE shares.id = $shareId
    GROUP BY shares.id, tag
    ORDER BY tags.tag) q
GROUP BY share_id
EOT;
        $row = Database::QueryOneRow($sql);

        return $row['tags'];
    }

    /* @param $userId int
     * @param $shareId int
     * @return bool
     */
    public static function UserIsAssociatedWithShare($userId, $shareId)
    {
        $row = Database::QueryOneRow("SELECT * FROM shares where id=$shareId");
        $sourceUserId = (int)$row['source_user_id'];
        $targetUserId = (int)$row['target_user_id'];

        return ($sourceUserId === $userId) || ($targetUserId === $userId);
    }

    /* @param $shareId int
     * @return int
     */
    public static function GetShareTarget($shareId)
    {
        return (int)Database::LookupValue('shares', 'id', $shareId, 'i', 'target_user_id', 0);
    }

    /* @param $userId int
     * @param $shareId int
     * @return int
     */
    public static function OtherUserOnShare($userId, $shareId)
    {
        $row = Database::QueryOneRow("SELECT * FROM shares where id=$shareId");
        $sourceUserId = (int)$row['source_user_id'];
        $targetUserId = (int)$row['target_user_id'];

        if ($sourceUserId === $userId) {
            return $targetUserId;
        } else if ($targetUserId === $userId) {
            return $sourceUserId;
        } else {
            return 0;
        }
    }

    /* @param $shareId int
     * @return int
     */
    public static function GetOrCreateReciprocalShareId($shareId)
    {
        $reciprocalId = self::GetReciprocalShareId($shareId);

        if ($reciprocalId <= 0) {
            $row = Database::QueryOneRow("SELECT * FROM shares WHERE id=$shareId");
            if ($row) {
                Database::ExecutePreparedStatement("INSERT INTO shares (reciprocal, source_user_id, target_user_id, source_enabled, target_enabled, tag_count) VALUES (?,?,?,?,?,?)",
                    'iiiiii', array($shareId,
                        (int)$row['target_user_id'],
                        (int)$row['source_user_id'],
                        0,
                        1,
                        (int)$row['tag_count']), $reciprocalId);
                Database::QueryCallback("SELECT * FROM shares_tags WHERE share_id=$shareId", function ($row) use ($reciprocalId) {
                    Database::ExecutePreparedStatement("INSERT INTO shares_tags (share_id, tag_id) VALUES (?,?)",
                        'ii', array($reciprocalId, (int)$row['tag_id']));
                });
                Database::ExecutePreparedStatement('UPDATE shares SET reciprocal=? WHERE id=?', 'ii', array($reciprocalId, $shareId));
            }
        }
        return $reciprocalId;
    }

    /* @param $shareId int
     * @return bool
     */
    public static function ShareIsDeleted($shareId)
    {
        return boolval(Database::LookupValue('shares', 'id', $shareId, 'i', 'deleted', false));
    }

    /**
     * @param $tagsAsString string
     * @return array
     */
    public static function GetTagListFromString($tagsAsString)
    {
        $tagList = preg_split('/\s+/', strtolower($tagsAsString), -1, PREG_SPLIT_NO_EMPTY);

        $tagList = array_map(function ($t) {
            if (substr($t, 0, 1) == '#') return substr($t, 1); else return $t;
        }, $tagList);

        $tagList = array_unique($tagList);

        sort($tagList);
        return $tagList;
    }

    /**
     * @param $tagList array[string]
     * @param $createIfNeeded bool
     * @return array[int]
     */
    public static function GetTagIdsFromTagList($tagList, $createIfNeeded)
    {
        $tagIdList = array();
        foreach ($tagList as $tag) {
            $tagId = (int)Database::LookupValue('tags', 'tag', $tag, 's', 'id', 0);
            if (($tagId <= 0) && $createIfNeeded) {
                Database::ExecutePreparedStatement('INSERT INTO tags (tag) VALUES (?)', 's', array($tag), $tagId);
                $tagId = (int)$tagId;
            }
            $tagIdList[] = $tagId;
        }
        return array_unique($tagIdList);
    }

    /*
     * @param $tag string
     * @return int
     */
    public static function GetTagId($tag)
    {
        return (int)Database::LookupValue('tags', 'tag', $tag, 's', 'id', 0);
    }

    /**
     * @param $tagsAsString string
     * @param $createIfNeeded bool
     * @return array[int]
     */
    public static function GetTagIdsFromString($tagsAsString, $createIfNeeded)
    {
        $tagList = self::GetTagListFromString($tagsAsString);

        return self::GetTagIdsFromTagList($tagList, $createIfNeeded);
    }

    /* @param $shareId int
     * @param $userId int
     * @param $includeTrash bool
     * @return int
     */
    public static function GetNumberOfMemosAssociatedWithShare($shareId, $userId, $includeTrash)
    {
        if ($includeTrash)
            $maxBucket = Bucket::BUCKET_TRASH;
        else
            $maxBucket = Bucket::BUCKET_TRASH - 1;

        $sql = <<< EOT
SELECT
    COUNT(memos.id) AS count
FROM
    shared_memo_status
INNER JOIN
    memos_shares ON shared_memo_status.memo_id = memos_shares.memo_id
INNER JOIN
    memos ON memos_shares.memo_id=memos.id
WHERE
   shared_memo_status.visible=1 AND
   shared_memo_status.bucket<=$maxBucket AND
   memos_shares.share_id=$shareId AND
   shared_memo_status.user_id=$userId AND
   memos.private=0 AND
   memos.deleted=0
GROUP BY
   memos_shares.share_id
EOT;
        return (int)Database::QueryOneRow($sql)['count'];
    }

    /* @param $sourceUserId int
     * @param $targetUserId int
     * @param $sharingOut bool
     * @return int
     */
    public static function GetNumberOfMemosShared($sourceUserId, $targetUserId, $sharingOut)
    {
        if ($sharingOut) {
            $bucketClause = '';
            $criterion = 'available';
        } else {
            $bucketClause = 'AND shared_memo_status.bucket<' . Bucket::BUCKET_TRASH;
            $criterion = 'visible';
        }
        // MYSQL doesn't like '=0' but is ok with '<>1': WTF??
        $sql = "shared_memo_status INNER JOIN memos ON shared_memo_status.memo_id=memos.id WHERE memos.user_id=$sourceUserId AND shared_memo_status.user_id=$targetUserId AND shared_memo_status.$criterion=1 AND memos.deleted<>1 $bucketClause";
        return Database::RecordCount($sql);
    }

    /* @param $userId int
     * @param $friendId int
     * @return int
     */
    public static function GetMemoCountWithFriend($userId, $friendId)
    {
        $trash = Bucket::BUCKET_TRASH;
        $sql = <<< EOT
SELECT count(*) AS count FROM shared_memo_status
INNER JOIN memos
    ON shared_memo_status.memo_id=memos.id
WHERE
    (memos.user_id=$userId AND shared_memo_status.user_id=$friendId AND shared_memo_status.available=1)
OR
    (memos.user_id=$friendId AND shared_memo_status.user_id=$userId AND shared_memo_status.visible=1 AND shared_memo_status.bucket<$trash)
EOT;
        return (int)Database::QueryOneRow($sql)['count'];
    }

    /* @param $userId int
     * @param $memoId int
     * @return bool
     */
    public static function UserCanViewMemo($userId, $memoId)
    {
        return Database::RecordExists("shared_memo_status WHERE user_id=$userId AND memo_id=$memoId AND visible=1");
    }


    /* @param $userId int
     * @param $memoId int
     * @return bool
     */
    public static function UserCanEditMemo($userId, $memoId)
    {
        return Database::RecordExists("shared_memo_status WHERE user_id=$userId AND memo_id=$memoId AND visible=1 AND can_edit=1");
    }


    public static function GetFriendsCallback($userId, $func)
    {
        $sql = <<< EOT
SELECT
    users.id AS user_id,
    users.screen_name AS screen_name,
    accounts.real_name AS real_name
FROM
(
    SELECT
        source_user_id AS user_id
    FROM
        shares
    WHERE
        shares.source_enabled = 1 AND
        shares.deleted = 0 AND
        target_user_id=$userId
UNION
    SELECT
        target_user_id AS user_id
            FROM shares
        WHERE
            shares.deleted=0 AND
            source_user_id=$userId
UNION
	SELECT
		direct_shares.user_id AS user_id
			FROM direct_shares
            INNER JOIN memos ON direct_shares.memo_id=memos.id
		WHERE memos.user_id=$userId
		    AND direct_shares.valid=1
UNION
	SELECT
		memos.user_id AS user_id
			FROM direct_shares
            INNER JOIN memos ON direct_shares.memo_id=memos.id
		WHERE direct_shares.user_id=$userId
		    AND direct_shares.valid=1
) q
INNER JOIN
    users ON users.id=q.user_id
INNER JOIN
    accounts ON accounts.user_id=users.id
ORDER BY screen_name
EOT;

        Database::QueryCallback($sql, $func);
    }

    public static function GetNumberMemosInBucket($userId, $bucket)
    {
        return Database::RecordCount("shared_memo_status WHERE user_id=$userId AND bucket=$bucket AND visible=1");
    }

    public static function GetNumberMemosWithTagId($userId, $tagId)
    {
        $trashBucket = Bucket::BUCKET_TRASH;

        $sql = "memos INNER JOIN memos_tags ON memos.id = memos_tags.memo_id INNER JOIN shared_memo_status ON memos.id=shared_memo_status.memo_id WHERE memos.user_id=$userId AND shared_memo_status.user_id=$userId AND memos_tags.tag_id=$tagId AND shared_memo_status.bucket <= $trashBucket AND memos_tags.valid=1";

        return Database::RecordCount($sql);
    }

    public static function GetNumberMemosWithTagIds($userId, array $tagIds)
    {
        $trashBucket = Bucket::BUCKET_TRASH;

        $tagClauseJoin = array();
        $tagClauseWhere = array();

        $i = 0;
        foreach ($tagIds as $id) {

            if ($id <= 0)
                return 0;

            $tagClauseJoin[] = " INNER JOIN memos_tags mt{$i} ON memos.id=mt{$i}.memo_id ";
            $tagClauseWhere[] = " mt{$i}.tag_id=$id AND mt{$i}.valid=1 ";
            $i++;
        }

        $sql = 'memos ' . join('', $tagClauseJoin) . "INNER JOIN shared_memo_status ON memos.id=shared_memo_status.memo_id WHERE memos.user_id=$userId AND" . join('AND', $tagClauseWhere) . " AND shared_memo_status.bucket < $trashBucket";

        return Database::RecordCount($sql);
    }


    /* @param $userId int
     * @param $memoId int
     * @return DateTime
     */
    public static function GetLastBucketChangeDateString($userId, $memoId)
    {
        $row = Database::QueryOneRow("SELECT last_bucket_change FROM shared_memo_status WHERE user_id=$userId AND memo_id=$memoId");
        return $row['last_bucket_change'];
    }

    public static function GetUserOption($userId, $optionId)
    {
        return Database::QueryOneRow("SELECT * FROM options WHERE user_id=$userId AND option_id=$optionId")['option_value'];
    }

    public static function SetUserOption($userId, $optionId, $optionValue)
    {
        return Database::ExecutePreparedStatement('UPDATE options SET option_value=? WHERE user_id=? AND option_id=?',
            'iii',
            array($optionValue, $userId, $optionId));
    }

    /* @param $userId int
     * @param $memoId int
     * @return DateTime
     */
    public static function GetAlarmForMemo($userId, $memoId)
    {
        $date = Database::QueryOneRow("SELECT alarm_date FROM shared_memo_status WHERE user_id=$userId AND memo_id=$memoId")['alarm_date'];

        if (is_null($date))
            return null;
        else
            return new DateTime($date);
    }
    /* @param $userId int
     * @param $memoId int
     * @return DateTime
     */
    public static function GetAlarmDateForMemoAsString($userId, $memoId)
    {
        $date = Database::QueryOneRow("SELECT alarm_date FROM shared_memo_status WHERE user_id=$userId AND memo_id=$memoId")['alarm_date'];

        if (is_null($date))
            return null;
        else
            return $date;
    }

    /* Returns ids from history for the given non-historic memo
     * @param $memoId int
     * @return array[int]
    */
    public static function GetHistory($memoId)
    {
        $history = array();
        $previousVersion = Database::LookupValue('memos', 'id', $memoId, 'i', 'previous_version', 0);

        while ($previousVersion > 0) {
            $history[] = $previousVersion;
            $previousVersion = Database::LookupValue('history', 'id', $previousVersion, 'i', 'previous_version', 0);
        }

        return $history;
    }



    /*
     * @param $memoId int
     */

    /* @param $shareId int
     * @param $memoId int
     * @param $userId int
     * @return bool
     */
    public static function ShareAuthorizesViewer($shareId, $memoId, $userId)
    {
        $targetUserId = (int)Database::LookupValue('shares', 'id', $shareId, 'i', 'target_user_id', 0);
        if ($targetUserId !== $userId)
            return false;

        $tagIdsInMemo = self::GetTagIdsInMemo($memoId);
        $tagIdsInShare = self::GetTagIdsInShare($shareId);

        return count(array_diff($tagIdsInShare, $tagIdsInMemo)) === 0;
    }

    /* @param $memoId int
     * @param $shareId int
     * @return bool
     */
    public static function MemoHasTagsForShare($memoId, $shareId)
    {
        foreach (self::GetTagIdsInShare($shareId) as $tagId) {
            if (!self::MemoHasTag($memoId, $tagId))
                return false;
        }
        return true;
    }

    /* @param $memoId int
     * @param $tagId int
     * @return bool
     */
    public static function MemoHasTag($memoId, $tagId)
    {
        $memoText = Database::LookupValue('memos', 'id', $memoId, 's', 'memo_text', '');
        $ok = false;
        Database::QueryCallback("SELECT tag FROM tags WHERE id=$tagId", function ($row) use (&$ok, $memoText) {
            if (mb_strpos(mb_strtolower($memoText), $row['tag']) !== false)
                $ok = true;
        });
        return $ok;
    }

    /* @param $memoId int
     * @return int[]
     */
    public static function GetTagIdsInMemo($memoId)
    {
        $tagIds = array();
        Database::QueryCallback("SELECT tag_id FROM memos_tags WHERE memo_id=$memoId AND memos_tags.valid=1", function ($row) use (&$tagIds) {
            $tagIds[] = $row['tag_id'];
        });
        return $tagIds;
    }

    /* @param $shareId int
     * @return int[]
     */
    public static function GetTagIdsInShare($shareId)
    {
        $tagIds = array();
        Database::QueryCallback("SELECT tag_id FROM shares_tags WHERE share_id=$shareId", function ($row) use (&$tagIds) {
            $tagIds[] = $row['tag_id'];
        });
        return $tagIds;
    }

    /* @param $memoId int
     * @return bool
     */
    public static function ComputeMemoIsShared($memoId)
    {
        return Database::RecordCount("memos INNER JOIN shared_memo_status ON memos.id=shared_memo_status.memo_id WHERE memos.id=$memoId AND memos.user_id<>shared_memo_status.user_id AND shared_memo_status.available=1") > 0;
    }


    /* @param $memoId int
     * @return bool
     */
    public static function GetPrivateStatus($memoId)
    {
        return boolval(Database::LookupValue('memos', 'id', $memoId, 'i', 'private', false));
    }

    /* @param $memoId int
     * @return bool
     */
    public static function GetFriendsCanEditStatus($memoId)
    {
        return boolval(Database::LookupValue('memos', 'id', $memoId, 'i', 'friends_can_edit', false));
    }

    /* @param $userId int
     * @param $bucket int
     * @return int
     */
    public static function GetMemoCount($userId, $bucket)
    {
        if ($bucket === Bucket::BUCKET_NONE) {
            return Database::RecordCount("memos WHERE user_id=$userId");
        } else {
            return Database::RecordCount("memos INNER JOIN shared_memo_status ON memos.id=shared_memo_status.memo_id WHERE memos.user_id=$userId AND shared_memo_status.bucket=$bucket");
        }
    }

    /* @param $userId int
     * @param $memoId int
     * @return bool
     */
    public static function MemoBucketHasChanged($userId, $memoId)
    {
        $row = Database::QueryOneRow("SELECT (shared_memo_status.last_bucket_change - memos.created > 5) AS changed FROM memos INNER JOIN shared_memo_status ON memos.id=shared_memo_status.memo_id WHERE shared_memo_status.user_id=$userId AND memo_id=$memoId");
        return ($row['changed'] > 0) ? true : false;
    }

    /* @param $memoId int
     * @return bool
     */
    public static function MemoNotDeleted($memoId)
    {
        $deleted = (int)Database::LookupValue('memos', 'id', $memoId, 'i', 'deleted', 1);
        return ($deleted === 0) ? true : false;
    }

    /**
     * @param $userId
     */
    private static function createSampleMemos($userId)
    {
        $newMemos = array();

        $newMemos[] = ControllerW::CreateMemo($userId, "Welcome to #Memofrog! We've added some sample memos for you to get started. When you're done with a memo, click the Trash icon (below, right) to send it to the Trash.", Bucket::BUCKET_HOT_LIST);
        $newMemos[] = ControllerW::CreateMemo($userId, "This memo is starred. You can turn the star on and off with the star button below.\r\n\r\nMemos with stars show up at the top of the list, before other memos (except in the Journal.)", Bucket::BUCKET_HOT_LIST);
        $newMemos[] = ControllerW::CreateMemo($userId, "This is a tag: #memofrog. Tags make it easy to group and find similar memos, and share groups of memos with your friends. Click on a tag to see other memos with that tag.", Bucket::BUCKET_REFERENCE);

        $text = Session::IsMobile() ?
            "Click the New Memo icon on the upper toolbar to create a new memo." :
            "To create a memo of your own, type it in the quick memo entry box above and hit Enter. You'll then see it in this list.";

        $newMemos[] = ControllerW::CreateMemo($userId, $text, Bucket::BUCKET_REFERENCE);
        $newMemos[] = ControllerW::CreateMemo($userId, "Here's an urgent memo so we've put it on the Hot List (with the fiery icon):\r\n\r\nPick up some milk on the way home. #groceries", Bucket::BUCKET_HOT_LIST);
        $newMemos[] = ControllerW::CreateMemo($userId, "And this one is less urgent so it's on the B-List:\r\n\r\nWash the #car on Saturday.", Bucket::BUCKET_B_LIST);
        $newMemos[] = ControllerW::CreateMemo($userId, "Here's a sample Journal entry:\r\n\r\nIt's a quiet night tonight. Maybe I'll go see a #movie.", Bucket::BUCKET_JOURNAL);

        ControllerW::SetStar($userId, $newMemos[0], true);
        ControllerW::SetStar($userId, $newMemos[1], true);

        /* @var $time DateTime */
        $time = new DateTime();
        foreach ($newMemos as $m) {
            $time = $time->sub(new DateInterval("PT1M"));
            $timeStr = $time->format(Util::DATE_TIME_FORMAT);
            $sql = "UPDATE memos INNER JOIN shared_memo_status ON memos.id = shared_memo_status.memo_id SET memos.created='$timeStr', memos.updated='$timeStr', shared_memo_status.last_bucket_change='$timeStr', shared_memo_status.sort_key=NULL WHERE memos.id=$m";
            Database::ExecuteQuery($sql);
        }
        ControllerW::UpdateSortKeys();
    }
}