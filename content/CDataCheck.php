<?php

class CDataCheck extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_DATA_CHECK;
    }

    public function AuthLevel()
    {
        return LoginStatus::ADMIN;
    }

    public function Render($userId)
    {
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;

        $html = Html::Div('submenu', self::GetNavLink("&lt;&nbsp;Admin", ContentKey::CONTENT_KEY_ADMIN, 'link_button')) .
                Html::Heading('Data Check');

        $sql = 'SELECT memos.id AS id FROM memos INNER JOIN shared_memo_status ON memos.id = shared_memo_status.memo_id WHERE memos.private=1 AND shared_memo_status.user_id<>memos.user_id AND shared_memo_status.visible=1';
        $html .= $this->renderQueryResults('Private memos visible to others', $this->check($sql));

        $sql = 'SELECT memos_tags.id AS id FROM memos_tags LEFT JOIN memos ON memos_tags.memo_id = memos.id WHERE memos.id IS NULL AND memos_tags.valid=1';
        $html .= $this->renderQueryResults('memos_tags without memos', $this->check($sql));

        $sql = 'SELECT shares_tags.id AS id FROM shares_tags LEFT JOIN shares ON shares_tags.share_id = shares.id WHERE shares.id IS NULL';
        $html .= $this->renderQueryResults('shares_tags without shares', $this->check($sql));

        $sql = 'SELECT shares.id AS id FROM shares LEFT JOIN shares_tags ON shares_tags.share_id = shares.id WHERE shares_tags.share_id IS NULL';
        $html .= $this->renderQueryResults('shares without shares_tags', $this->check($sql));

        $sql = 'SELECT memos.id AS id FROM memos LEFT JOIN users ON memos.user_id = users.id WHERE users.id IS NULL';
        $html .= $this->renderQueryResults('Orphan Memos', $this->check($sql));

        $sql = 'SELECT shares.id AS id FROM shares LEFT JOIN shares_tags ON shares.id = shares_tags.share_id WHERE shares_tags.share_id IS NULL';
        $html .= $this->renderQueryResults('Shares with missing shares_tags', $this->check($sql));

        $sql = 'SELECT shares_tags.id AS id FROM shares_tags LEFT JOIN tags ON shares_tags.tag_id = tags.id WHERE tags.id IS NULL';
        $html .= $this->renderQueryResults('shares_tags with missing tags', $this->check($sql));

        $sql = 'SELECT memos.id AS id FROM memos LEFT JOIN shared_memo_status ON memos.id=shared_memo_status.memo_id AND memos.user_id=shared_memo_status.user_id WHERE (shared_memo_status.visible=0 OR shared_memo_status.visible IS NULL) AND bucket<'.Bucket::BUCKET_DELETED;
        $html .= $this->renderQueryResults('Authors who can&apos;t view own memos', $this->check($sql));

        $sql = 'SELECT memos.id AS id FROM memos LEFT JOIN shared_memo_status ON memos.id=shared_memo_status.memo_id AND memos.user_id=shared_memo_status.user_id WHERE (shared_memo_status.can_edit=0 OR shared_memo_status.can_edit IS NULL) AND bucket<'.Bucket::BUCKET_DELETED;
        $html .= $this->renderQueryResults('Authors who can&apos;t edit own memos', $this->check($sql));

        $sql = 'SELECT memos.id AS id FROM memos INNER JOIN shared_memo_status ON memos.id=shared_memo_status.memo_id WHERE memos.user_id=shared_memo_status.user_id AND available=0 AND bucket<'.Bucket::BUCKET_DELETED;
        $html .= $this->renderQueryResults('Memos unavailable to author', $this->check($sql));

        $sql = 'SELECT shared_memo_status.id AS id FROM shared_memo_status LEFT JOIN memos ON shared_memo_status.memo_id = memos.id WHERE memos.id IS NULL';
        $html .= $this->renderQueryResults('Orphan SMS', $this->check($sql));

        $sql = 'SELECT id FROM shared_memo_status WHERE bucket < ' . Bucket::BUCKET_VALID_RANGE_START . ' OR bucket > ' . Bucket::BUCKET_VALID_RANGE_END;
        $html .= $this->renderQueryResults('Invalid memo buckets', $this->check($sql));

        $sql = 'SELECT id FROM shared_memo_status WHERE alarm_date IS NOT NULL AND alarm_date < \'2015-11-01\'';
        $html .= $this->renderQueryResults('Invalid alarm dates in shared_memo_status', $this->check($sql));

        $sql = 'SELECT memos.id AS id FROM memos INNER JOIN shared_memo_status ON memos.id=shared_memo_status.memo_id WHERE memos.user_id=shared_memo_status.user_id AND memos.deleted<>(shared_memo_status.bucket='.Bucket::BUCKET_DELETED.')';
        $html .= $this->renderQueryResults('memos / shared_memo_status deleted mismatch', $this->check($sql));

        $sql = "SELECT memos.id AS id FROM memos INNER JOIN direct_shares ON memos.id=direct_shares.memo_id INNER JOIN users ON direct_shares.user_id=users.id WHERE direct_shares.valid=1 AND memo_text NOT LIKE CONCAT('%@', users.screen_name, '%')";
        $html .= $this->renderQueryResults('Direct share memos without embedded screen name', $this->check($sql));

        $sql = "SELECT memos.id AS id FROM memos INNER JOIN direct_shares ON memos.id=direct_shares.memo_id WHERE direct_shares.user_id=memos.user_id";
        $html .= $this->renderQueryResults('Direct shares with author', $this->check($sql));

        $sql = 'SELECT shares.id AS id FROM shares WHERE reciprocal = 0';
        $html .= $this->renderQueryResults('Shares without reciprocals', $this->check($sql));

        $sql = 'SELECT memos.id AS id FROM memos INNER JOIN memos_shares ON memos.id=memos_shares.memo_id INNER JOIN shares ON memos_shares.share_id=shares.id WHERE memos.user_id<>shares.source_user_id';
        $html .= $this->renderQueryResults('Inconsistent memos_shares', $this->check($sql));

        $sql = 'SELECT memos.id AS id FROM memos WHERE memos.private=1 AND memos.shared=1';
        $html .= $this->renderQueryResults('Memos marked as both private and shared.', $this->check($sql));

        $sql = 'SELECT shared_memo_status.id AS id FROM shared_memo_status INNER JOIN memos ON shared_memo_status.memo_id = memos.id AND ((shared_memo_status.is_author=1 AND memos.user_id<>shared_memo_status.user_id) OR (shared_memo_status.is_author=0 AND memos.user_id=shared_memo_status.user_id))';
        $html .= $this->renderQueryResults('Inconsistent sms.is_author', $this->check($sql));

        $sql = 'SELECT id FROM memos WHERE updated IS NULL OR updated=0';
        $html .= $this->renderQueryResults('Memos with no update date', $this->check($sql));

        $sql = 'SELECT id FROM history WHERE updated IS NULL OR updated=0';
        $html .= $this->renderQueryResults('History with no update date', $this->check($sql));

        $sql = 'SELECT id FROM shared_memo_status WHERE sort_key IS NULL';
        $html .= $this->renderQueryResults('SMS with NULL sort_key', $this->check($sql));

        $reciprocalCountIds = array();
        Database::QueryCallback('SELECT id FROM shares', function ($row) use (&$reciprocalCountIds) {
            $shareId = (int)$row['id'];
            $sql = Sql::GetReciprocalShareSql($shareId);
            $sql = "SELECT count(*) AS count FROM ($sql) q2";
            $count = (int)Database::QueryOneRow($sql);
            if ($count != 1)
                $reciprocalCountIds[] = $shareId;
        });
        $html .= $this->renderQueryResults('Share reciprocal count not 1', $reciprocalCountIds);

        $sql = 'SELECT memos.id AS id FROM memos LEFT JOIN (SELECT memo_id AS sms_memo_id, shared_memo_status.user_id AS sms_user_id FROM shared_memo_status INNER JOIN memos ON shared_memo_status.memo_id = memos.id WHERE shared_memo_status.available=1 AND shared_memo_status.is_author=0) q ON memos.id = sms_memo_id WHERE memos.shared = 1 AND memos.deleted = 0 AND sms_memo_id IS NULL';
        $html .= $this->renderQueryResults('Inconsistent memos.shared=1', $this->check($sql));

        $sql = 'SELECT memos.id AS id FROM memos LEFT JOIN (SELECT memo_id AS sms_memo_id, shared_memo_status.user_id AS sms_user_id FROM shared_memo_status INNER JOIN memos ON shared_memo_status.memo_id = memos.id WHERE shared_memo_status.available=1 AND shared_memo_status.is_author=0) q ON memos.id = sms_memo_id WHERE memos.shared = 0 AND memos.deleted = 0 AND sms_memo_id IS NOT NULL';
        $html .= $this->renderQueryResults('Inconsistent memos.shared=0', $this->check($sql));

        $sql = <<< EOT
SELECT
    source,
    target,
    tags,
    COUNT(*) AS count,
    GROUP_CONCAT(share_id SEPARATOR '+') AS id
FROM
    (SELECT
        source,
            target,
            GROUP_CONCAT(tag SEPARATOR '+') AS tags,
            share_id
    FROM
        (SELECT users.screen_name AS source,
                u2.screen_name AS target,
                tags.tag AS tag,
                shares.id AS share_id
        FROM
            users
        INNER JOIN shares ON users.id = shares.source_user_id
        INNER JOIN shares_tags ON shares.id = shares_tags.share_id
        INNER JOIN tags ON tags.id = shares_tags.tag_id
        INNER JOIN users u2 ON u2.id = shares.target_user_id
        GROUP BY shares.id , tag
        ORDER BY tags.tag) q
    GROUP BY source , target , share_id) q2
GROUP BY source , target , tags
HAVING count > 1
EOT;
        $html .= $this->renderQueryResults('Redundant Shares', $this->check($sql));

        $this->html = $html;
    }

    private function check($sql)
    {
        $row_ids = array();

        Database::QueryCallback($sql . ' ORDER BY id', function ($row) use (&$row_ids) {
            $row_ids[] = $row['id'];
        });

        return $row_ids;
    }

    /* @param $head string
     * @param $res int[]
     * @return string
     */
    private function renderQueryResults($head, array $res)
    {
        $html = '';
        $any = false;
        foreach ($res as $r) {
            $html .= Html::LI($r);
            $any = true;
        }
        if ($any) {
            $html = Html::UL($html);
            $html = View::RenderInfoWidget($head, $html);
        } else {
            $html = View::RenderInfoWidget($head, 'No errors.');
        }
        return $html;
    }
}
