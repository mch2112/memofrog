<?php

class ShareDbUpdater
{
    const UPDATE_MEMO_NEW = 110;
    const UPDATE_MEMO_EDIT = 120;
    const UPDATE_MEMO_DELETED = 130;
    const UPDATE_SHARE_NEW = 210;
    const UPDATE_SHARE_ENABLE = 220;
    const UPDATE_SHARE_DISABLE = 230;
    const UPDATE_MEMO_PRIVATE = 310;
    const UPDATE_MEMO_NOT_PRIVATE = 320;
    const UPDATE_MEMO_FRIENDS_CAN_ALWAYS_EDIT = 330;
    const UPDATE_MEMO_FRIENDS_CAN_NOT_ALWAYS_EDIT = 340;

    public static function UpdateShareData($type, $dataId)
    {
        switch ($type) {
            case self::UPDATE_MEMO_NEW:
                self::updateMemosSharesForMemoChange($dataId, true);
                //self::computeDirectShares($dataId, true);
                self::updateSharedMemoStatus($dataId, 0);
                self::updateMemoSharedForMemoChange($dataId);
                break;
            case self::UPDATE_MEMO_EDIT:
                self::updateMemosSharesForMemoChange($dataId, false);
                //self::computeDirectShares($dataId, false);
                self::updateSharedMemoStatus($dataId, 0);
                self::updateMemoSharedForMemoChange($dataId);
                break;
            case self::UPDATE_MEMO_DELETED:
                Database::ExecuteQuery("UPDATE memos_shares SET valid=0 WHERE memo_id=$dataId");
                Database::ExecuteQuery("UPDATE direct_shares SET valid=0 WHERE memo_id=$dataId");
                Database::ExecuteQuery("UPDATE shared_memo_status SET available=0, visible=0, can_edit=0 where memo_id=$dataId");
                Database::ExecuteQuery("UPDATE memos SET shared=0 WHERE id=$dataId");
                break;
            case self::UPDATE_SHARE_NEW:
                self::updateMemosSharesForNewShare($dataId);
                self::updateSharedMemoStatus(0, $dataId);
                self::updateMemoSharedForShareChange($dataId);
                break;
            case self::UPDATE_SHARE_ENABLE:
                self::updateMemosSharesForShareEnable($dataId);
                self::updateSharedMemoStatus(0, $dataId);
                self::updateMemoSharedForShareChange($dataId);
                break;
            case self::UPDATE_SHARE_DISABLE:
                self::updateForShareDisable($dataId);
                self::updateSharedMemoStatus(0, $dataId, true);
                self::updateMemoSharedForShareChange($dataId);
                break;
            case self::UPDATE_MEMO_PRIVATE:
                Database::ExecuteQuery("UPDATE shared_memo_status INNER JOIN memos ON shared_memo_status.memo_id=memos.id SET available=0, visible=0, can_edit=0 where shared_memo_status.memo_id=$dataId AND shared_memo_status.is_author=0");
                Database::ExecuteQuery("UPDATE memos SET shared=0 WHERE id=$dataId");
                break;
            case self::UPDATE_MEMO_NOT_PRIVATE:
                self::updateSharedMemoStatus($dataId, 0);
                self::updateMemoSharedForMemoChange($dataId);
                break;
            case self::UPDATE_MEMO_FRIENDS_CAN_ALWAYS_EDIT:
                Database::ExecuteQuery("Update shared_memo_status SET can_edit=1 WHERE memo_id=$dataId");
                break;
            case self::UPDATE_MEMO_FRIENDS_CAN_NOT_ALWAYS_EDIT:
                self::updateSharedMemoStatus($dataId, 0);
                break;
        }
    }

    private static function updateSharedMemoStatus($memoId, $shareId, $isShareDisable = false)
    {
        if ($memoId > 0)
            $whereClause = "memos_shares.memo_id=$memoId";
        else if ($shareId > 0) {
            if ($isShareDisable) {
                $row = Database::QueryOneRow('SELECT source_user_id, target_user_id FROM shares WHERE id='.$shareId);
                $sourceUserId = (int)$row['source_user_id'];
                $targetUserId = (int)$row['target_user_id'];
                $whereClause = "shares.source_user_id=$sourceUserId AND shares.target_user_id=$targetUserId";
            } else {
                $whereClause = "memos_shares.share_id=$shareId";
            }
        }
        else
            return;

        $bucket=Bucket::BUCKET_HOT_LIST;
        $sql = <<< EOT
INSERT INTO
	shared_memo_status (memo_id, user_id, is_author, bucket, available, visible, can_edit)
    SELECT
        memo_id,
        user_id,
        0 as is_author,
        bucket,
        MAX(available) AS available,
        MAX(visible) AS visible,
        MAX(can_edit) AS can_edit
    FROM
    (
        SELECT
            memos_shares.memo_id AS memo_id,
            shares.target_user_id AS user_id,
            $bucket AS bucket,
            !memos.private & memos_shares.valid & !shares.deleted & shares.source_enabled AS available,
            !memos.private & memos_shares.valid & !shares.deleted & shares.source_enabled & shares.target_enabled AS visible,
            !memos.private & ((memos_shares.valid & !shares.deleted & shares.source_enabled & shares.target_enabled & shares.can_edit) OR memos.friends_can_edit)AS can_edit
        FROM
            shares
        INNER JOIN
            memos_shares ON shares.id=memos_shares.share_id
        INNER JOIN
            memos ON memos_shares.memo_id=memos.id AND shares.source_user_id=memos.user_id
        WHERE
            $whereClause
            AND memos.deleted=0
        UNION
        SELECT
            direct_shares.memo_id AS memo_id,
            direct_shares.user_id AS user_id,
            $bucket AS bucket,
            direct_shares.valid AS available,
            direct_shares.valid AS visible,
            memos.friends_can_edit AS can_edit
        FROM
            direct_shares INNER JOIN
            memos ON direct_shares.memo_id=memos.ID
        WHERE
            direct_shares.memo_id=$memoId AND
            memos.deleted=0
            ) q
        GROUP BY memo_id, user_id
ON DUPLICATE KEY UPDATE available = VALUES(available), visible=VALUES(visible), can_edit=VALUES(can_edit)
EOT;
        Database::ExecuteQuery($sql);
    }

    private static function updateMemoSharedForMemoChange($memoId)
    {
        $sql = <<< EOT
UPDATE memos
    LEFT JOIN
    (SELECT
        memo_id, available
    FROM
        shared_memo_status
    WHERE
        available=1 AND memo_id=$memoId AND is_author=0) sms ON memos.id=sms.memo_id
SET
    memos.shared=COALESCE(sms.available, 0)
WHERE
    memos.id=$memoId
EOT;
        Database::ExecuteQuery($sql);
    }

    private static function updateMemoSharedForShareChange($shareId)
    {
        $sql = <<< EOT
UPDATE
    memos
INNER JOIN
    (SELECT
        memos.id AS id, MAX(shared_memo_status.available) AS available
    FROM
        memos_shares
    INNER JOIN
        memos ON memos_shares.memo_id=memos.id
    INNER JOIN
        shared_memo_status ON memos.id = shared_memo_status.memo_id
    WHERE
        memos_shares.share_id=$shareId AND
        shared_memo_status.is_author=0
    GROUP BY memos.id
    ) q ON memos.id = q.id
SET
    memos.shared=q.available
EOT;
        Database::ExecuteQuery($sql);
    }

    private static function updateMemosSharesForNewShare($shareId)
    {
        Database::ExecuteQuery(self::getInsertMemosSharesSql(0, $shareId));
    }
    private static function updateMemosSharesForShareEnable($shareId)
    {
        //Database::ExecuteQuery("UPDATE memos_shares SET valid=0 WHERE share_id=$shareId");
        Database::ExecuteQuery(self::getInsertMemosSharesSql(0, $shareId));
    }

    private static function updateForShareDisable($shareId)
    {
        //Database::ExecuteQuery("UPDATE memos_shares SET valid=0 WHERE share_id=$shareId");
    }

    /*
     * @param $memoId int
     * @param $isNew bool
     */
    private static function updateMemosSharesForMemoChange($memoId, $isNew)
    {
        if (!$isNew)
            Database::ExecutePreparedStatement('UPDATE memos_shares SET valid=0 WHERE memo_id=?', 'i', array($memoId));

        Database::ExecuteQuery(self::getInsertMemosSharesSql($memoId, 0));
    }

    public static function UpdateMemoShareStatus($authorId)
    {
        $sql = <<< EOT
UPDATE
    memos
INNER JOIN
    (SELECT
        memo_id,
        MAX(available) AS max_avail
    FROM
        memos
    INNER JOIN
        shared_memo_status ON memos.id=shared_memo_status.memo_id
    WHERE
        memos.user_id<>shared_memo_status.user_id
    GROUP BY memo_id)
    sms
ON memos.id=sms.memo_id
SET
    memos.shared=max_avail
WHERE memos.user_id=?
EOT;
        Database::ExecutePreparedStatement($sql, 'i', array($authorId));
    }

    /**
     * @param $memoId int
     * @param $shareId int
     * @return string
     */
    private static function getInsertMemosSharesSql($memoId, $shareId)
    {
        if ($memoId > 0)
            $whereClause = "memos.id=$memoId";
        else if ($shareId > 0)
            $whereClause = "shares.id=$shareId";
        else
            return '';

        $sql = <<< EOT
INSERT INTO memos_shares (memo_id, share_id, valid)
    SELECT memo_id, share_id, 1 FROM
		(SELECT
			shares.id AS share_id,
            memos.id AS memo_id,
			shares.tag_count,
			COUNT(*) AS tags_matching
		FROM
			shares
				INNER JOIN
			memos ON shares.source_user_id = memos.user_id
				INNER JOIN
			memos_tags ON memos.id = memos_tags.memo_id
				INNER JOIN
			shares_tags ON memos_tags.tag_id = shares_tags.tag_id
				AND shares_tags.share_id = shares.id
		WHERE
			$whereClause AND memos_tags.valid=1
		GROUP BY memos.id, shares.id
		HAVING shares.tag_count <= tags_matching) q
ON DUPLICATE KEY UPDATE valid=1
EOT;
        //Util::Log($sql);
        return $sql;
    }
}