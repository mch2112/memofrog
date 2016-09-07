<?php

class Sql
{
    const FILTER_TAGS_OP_ALL = 0;
    const FILTER_TAGS_OP_ANY = 1;

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
     * @return string
     */
    public static function GetMemosSql($numMemosReq, $memosToSkip, $userId, $shareUserId, $shareTo, $tagsAsString, $tagsOp, $filterText, $bucket, $special)
    {
        if ($special === Search::SPECIAL_SEARCH_HIDDEN)
            $bucket = Bucket::BUCKET_HIDDEN;

        switch ($bucket) {
            case Bucket::BUCKET_EVERYTHING:
                if (($shareUserId > 0 && $shareTo) || $special === Search::SPECIAL_SEARCH_ALARM)
                    $bucketClause = 'AND shared_memo_status.bucket BETWEEN ' . strval(Bucket::BUCKET_EVERYTHING_START) . ' AND ' . strval(Bucket::BUCKET_HIDDEN);
                else
                    $bucketClause = 'AND shared_memo_status.bucket BETWEEN ' . strval(Bucket::BUCKET_EVERYTHING_START) . ' AND ' . strval(Bucket::BUCKET_EVERYTHING_END);
                break;
            case Bucket::BUCKET_ALL_ACTIVE:
                $bucketClause = 'AND shared_memo_status.bucket<=' . strval(Bucket::BUCKET_B_LIST    );
                break;
            default:
                $bucketClause = "AND shared_memo_status.bucket=$bucket";
                break;
        }

        // TAGS
        if (strlen($tagsAsString) > 0) {
            $tagIds = array();
            $tags = explode('+', $tagsAsString);
            foreach ($tags as $tag)
                $tagIds[] = Database::LookupValue('tags', 'tag', $tag, 's', 'id', -1);
            list($tagJoinClause, $tagWhereClause) = self::getClausesForTags($tagIds, $tagsOp);
        } else {
            $tagJoinClause = $tagWhereClause = '';
        }

        // SEARCH TEXT

        $textFilterClause = '';
        if (strlen($filterText) > 0) {
            $any = false;
            $allNeg = true;
            $useLike = false;
            if (preg_match_all('/(\-?\w+)/', $filterText, $words)) {
                $any = true;
                $words = $words[0];
                foreach ($words as $w) {
                    if (mb_strlen($w) < 4 || self::isStopWord($w))
                        $useLike = true;
                    if ($w[0] !== "-")
                        $allNeg = false;
                }
            }

            if ($allNeg)
                $useLike = true;

            if ($any) {
                if ($useLike) {
                    $textFilterClause = join('', array_map(function ($w) {
                        if ($w[0] === '-')
                            return ' AND memos.memo_text NOT LIKE \'%' . mb_substr($w, 1) . '%\'';
                        else
                            return ' AND memos.memo_text LIKE \'%' . $w . '%\'';
                    }, $words));
                } else {
                    $textFilterClause = join('', array_map(function ($w) {
                        return ($w[0] === '-') ? $w : ('+' . $w);
                    }, $words));
                    $textFilterClause = "AND MATCH (memos.memo_text) AGAINST ('$textFilterClause' IN BOOLEAN MODE)";
                }
            }
        }
        if ($memosToSkip === 0)
            $limitClause = "LIMIT $numMemosReq";
        else
            $limitClause = "LIMIT $numMemosReq OFFSET " . strval($memosToSkip);

        // AUTHOR
        if ($shareUserId > 0) {
            if ($shareUserId === $userId) {
                $authorClause = $authorClause = "AND memos.user_id=$userId";
                $smsClause = "AND shared_memo_status.user_id=$userId";
                $shareToJoinClause = '';
                $smsCriterion = 'shared_memo_status.visible=1';
            } else
                if ($shareTo) {
                    $authorClause = "AND memos.user_id=$userId";
                    $smsClause = "AND shared_memo_status.user_id=$userId AND sms2.user_id=$shareUserId";
                    $shareToJoinClause = 'INNER JOIN shared_memo_status sms2 ON sms2.memo_id=memos.id';
                    $smsCriterion = 'sms2.available=1';
                } else {
                    $authorClause = "AND memos.user_id=$shareUserId ";
                    $smsClause = "AND shared_memo_status.user_id=$userId";
                    $shareToJoinClause = '';
                    $smsCriterion = 'shared_memo_status.visible=1';
                }
        } else {
            $authorClause = '';
            $smsClause = "AND shared_memo_status.user_id=$userId";
            $shareToJoinClause = '';
            $smsCriterion = 'shared_memo_status.visible=1';
        }

// SPECIAL

        switch ($special) {
            case Search::SPECIAL_SEARCH_SHARED:
                $specialWhereClause = 'AND memos.shared=1';
                $authorClause = "AND memos.user_id = $userId";
                $shareToJoinClause = '';
                break;
            case Search::SPECIAL_SEARCH_STARS:
                $specialWhereClause = 'AND shared_memo_status.star=1';
                break;
            case Search::SPECIAL_SEARCH_UNSTARRED:
                $specialWhereClause = 'AND shared_memo_status.star=0';
                break;
            case Search::SPECIAL_SEARCH_OLD:
                $specialWhereClause = 'AND memos.updated < DATE_SUB(NOW(), INTERVAL 30 DAY)';
                break;
            case Search::SPECIAL_SEARCH_EDITED:
                $specialWhereClause = 'AND memos.previous_version IS NOT NULL';
                break;
            case Search::SPECIAL_SEARCH_BY_ME:
                $specialWhereClause = "AND shared_memo_status.is_author=1";
                break;
            case Search::SPECIAL_SEARCH_NOT_BY_ME:
                $specialWhereClause = "AND shared_memo_status.is_author=0";
                break;
            case Search::SPECIAL_SEARCH_PRIVATE:
                $specialWhereClause = "AND memos.private=1";
                break;
            case Search::SPECIAL_SEARCH_ALARM:
                $specialWhereClause = "AND shared_memo_status.alarm_date IS NOT NULL";
                break;
            case Search::SPECIAL_SEARCH_OLDEST_FIRST:
                $specialWhereClause = '';
                break;
            default:
                $specialWhereClause = '';
                break;
        }

// SORT

        if ($special === Search::SPECIAL_SEARCH_OLDEST_FIRST) {
            $orderByClause = 'CASE WHEN shared_memo_status.bucket=' . Bucket::BUCKET_JOURNAL . ' THEN memos.created ELSE memos.updated END';
        } else if ($bucket === Bucket::BUCKET_JOURNAL) {
            $orderByClause = 'memos.created DESC';
        } else {
            $orderByClause = 'shared_memo_status.sort_key DESC';
        }

        $journal = Bucket::BUCKET_JOURNAL;

        return <<< EOT
SELECT
    memos.id AS memo_id,
    memos.user_id AS memo_user_id,
    users.screen_name AS screen_name,
    memos.memo_text AS memo_text,
    memos.private AS private,
    memos.shared AS shared,
    memos.sync_buckets AS sync_buckets,
    memos.friends_can_edit AS friends_can_edit,
    shared_memo_status.alarm_date AS alarm_date,
    shared_memo_status.bucket AS bucket,
    shared_memo_status.star AS star,
    shared_memo_status.can_edit AS can_edit,
    memos.edited_by AS edited_by,
    #CASE shared_memo_status.bucket WHEN $journal THEN memos.created ELSE memos.updated END AS date,
    memos.created AS create_date,
    memos.updated AS edit_date,
    shared_memo_status.last_bucket_change AS move_date,
    shared_memo_status.is_author AS is_author
FROM
    users
        INNER JOIN
    memos ON users.id = memos.user_id
        INNER JOIN
    shared_memo_status ON memos.id = shared_memo_status.memo_id
    $tagJoinClause
    $shareToJoinClause
WHERE
    $smsCriterion
    $smsClause
    $bucketClause
    $authorClause
    $tagWhereClause
    $specialWhereClause
    $textFilterClause
ORDER BY $orderByClause
    $limitClause
EOT;
    }

    /* @param $memoId int
     * @param $userId int
     * @param $isHistoric bool
     * @return string
     */
    public
    static function GetSingleMemoSql($memoId, $userId, $isHistoric = false)
    {
        if ($isHistoric) {
            return <<< EOT
SELECT
    history.id AS memo_id,
    history.user_id AS memo_user_id,
    users.screen_name AS screen_name,
    history.memo_text AS memo_text,
    false AS private,
    false AS shared,
    false AS sync_buckets,
    false AS friends_can_edit,
    null AS alarm_date,
    510 AS bucket,
    false AS star,
    false AS can_edit,
    history.edited_by AS edited_by,
    history.created AS create_date,
    history.updated AS edit_date,
    history.updated AS move_date,
    false AS is_author
FROM
    history
        INNER JOIN
    users ON history.user_id = users.id
WHERE
    history.id = $memoId
EOT;
        } else {
            return <<< EOT
SELECT
    memos.id AS memo_id,
    memos.user_id AS memo_user_id,
    users.screen_name AS screen_name,
    memos.private AS private,
    memos.shared AS shared,
    memos.sync_buckets AS sync_buckets,
    memos.friends_can_edit AS friends_can_edit,
    shared_memo_status.alarm_date AS alarm_date,
    memos.memo_text AS memo_text,
    shared_memo_status.bucket AS bucket,
    shared_memo_status.star AS star,
    shared_memo_status.can_edit AS can_edit,
    memos.edited_by AS edited_by,
    memos.created AS create_date,
    memos.updated AS edit_date,
    shared_memo_status.last_bucket_change AS move_date,
    shared_memo_status.is_author AS is_author
FROM
    memos
        INNER JOIN
    users ON memos.user_id = users.id
        INNER JOIN
    shared_memo_status ON memos.id = shared_memo_status.memo_id
WHERE
    memos.id=$memoId AND shared_memo_status.user_id=$userId
EOT;
        }
    }

    private static function isStopWord($word)
    {
        switch ($word) {
            case 'about':
            case 'an':
            case 'are':
            case 'as':
            case 'at':
            case 'be':
            case 'by':
            case 'com':
            case 'de':
            case 'en':
            case 'for':
            case 'from':
            case 'how':
            case 'i':
            case 'in':
            case 'is':
            case 'it':
            case 'la':
            case 'of':
            case 'on':
            case 'or':
            case 'that':
            case 'the':
            case 'this':
            case 'to':
            case 'was':
            case 'what':
            case 'when':
            case 'where':
            case 'who':
            case 'will':
            case 'with':
            case 'und':
            case 'www':
                return true;
            default:
                return false;
        }
    }

    /* @param $userId int
     * @return string
     */
    public
    static function GetTagsForUserSql($userId)
    {
        $maxBucket = Bucket::BUCKET_EVERYTHING_END;
        $sql = <<<EOT
SELECT
    q1.tag AS tag,
    SUM(q1.is_own) AS own_count,
    SUM(q1.is_other) AS other_count
FROM
    (SELECT
        tags.tag AS tag,
        memos.user_id=$userId AS is_own,
        memos.user_id<>$userId AS is_other
    FROM
        shared_memo_status
            INNER JOIN
        memos ON shared_memo_status.memo_id=memos.id
            INNER JOIN
        memos_tags ON memos.id=memos_tags.memo_id
            INNER JOIN
        tags ON memos_tags.tag_id=tags.id
    WHERE
        memos_tags.valid=1 AND
        shared_memo_status.user_id=$userId AND
        shared_memo_status.visible=1 AND
        shared_memo_status.bucket<=$maxBucket) q1
GROUP BY tag
EOT;

        Util::Log($sql);
        return $sql;
    }

    /* @param $tagIds array[int]
     * @param $tagOp int
     * @return array[string]
     */
    private
    static function getClausesForTags($tagIds, $tagOp)
    {
        $clause1 = $clause2 = '';
        if (count($tagIds) == 1 || $tagOp == self::FILTER_TAGS_OP_ALL) {
            $i = 1;
            foreach ($tagIds as $tagId) {
                $clause1 .= " INNER JOIN memos_tags mt$i ON shared_memo_status.memo_id = mt$i.memo_id ";
                $clause2 .= " AND mt$i.valid=1 AND mt$i.tag_id = $tagId ";
                $i++;
            }
        } else {
            $c2 = array();
            foreach ($tagIds as $tagId) {
                $c2[] = strval($tagId);
            }

            $clause1 = '';
            $clause2 = ' AND EXISTS (SELECT * FROM memos_tags mt1 WHERE mt1.valid=1 AND memos.id=mt1.memo_id AND mt1.tag_id IN (' . join(',', $c2) . '))';
        }
        return array($clause1, $clause2);
    }

    public
    static function GetReciprocalShareSql($shareId)
    {
        return <<< EOT
SELECT s1_id, s2_id, tags_needed, count(tag_id) AS count FROM
(
SELECT s1.id AS s1_id, s2.id AS s2_id, s1.tag_count AS tags_needed, st1.tag_id AS tag_id FROM
shares s1 INNER JOIN shares s2
ON s1.source_user_id = s2.target_user_id AND s1.target_user_id = s2.source_user_id
INNER JOIN shares_tags st1 ON s1.id = st1.share_id
INNER JOIN shares_tags st2 ON s2.id = st2.share_id AND st1.tag_id = st2.tag_id
WHERE s1.tag_count = s2.tag_count
AND s1.id = $shareId
) q
GROUP BY s1_id, s2_id
HAVING count >= tags_needed
EOT;
    }
}
