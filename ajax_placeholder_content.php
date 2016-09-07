<?php
const PLACEHOLDER_MEMO_DETAILS = 40100;
const PLACEHOLDER_SUGGESTED_TAGS = 40200;

require_once('global.php');

$userId = Session::DoAjaxSession(true);

$ok = false;
$content = '';
$txId = UserInput::Extract(Key::KEY_PLACEHOLDER_TX_ID, "");
$response = array(Key::KEY_PLACEHOLDER_TX_ID => $txId);

if (strlen($txId) > 0) {
    switch (UserInput::Extract(Key::KEY_PLACEHOLDER_KEY, 0)) {
        case PLACEHOLDER_MEMO_DETAILS:
            if ($userId > 0) {
                $memoId = UserInput::Extract(Key::KEY_MEMO_ID, 0);
                if ($memoId > 0) {
                    if (Controller::UserCanViewMemo($userId, $memoId)) {
                        $content = getMemoDetailsContent($userId, $memoId);
                        $ok = true;
                    }
                }
            }
            break;
        case PLACEHOLDER_SUGGESTED_TAGS:
            if ($userId > 0) {
                $start = Bucket::BUCKET_EVERYTHING_START;
                $end = Bucket::BUCKET_EVERYTHING_END;
                $sql = <<<EOT

SELECT
    tags.tag AS tag, COUNT(*) AS count
FROM
	shared_memo_status
		INNER JOIN
    memos ON shared_memo_status.memo_id = memos.id
        INNER JOIN
    memos_tags ON memos.id = memos_tags.memo_id
        INNER JOIN
    tags ON memos_tags.tag_id = tags.id
WHERE
    memos_tags.valid = 1 AND
    shared_memo_status.user_id = $userId AND
    shared_memo_status.bucket BETWEEN $start AND $end
GROUP BY tag
ORDER BY count DESC, tag
LIMIT 16
EOT;
                $defaultNum = 3;
                foreach (array('chores', 'fun', 'gifts', 'grocery', 'homework', 'idea', 'kids', 'opportunity', 'shopping', 'today', 'todo', 'travel', 'wishlist', 'work') as $tag)
                    $tags[$tag] = $defaultNum;

                $count = Database::QueryCallback($sql, function ($row) use (&$tags) {
                    $tag = $row['tag'];
                    $count = (int)$row['count'];
                    if (array_key_exists($tag, $tags))
                        $tags[$tag] = max($tags[$tag], $count);
                    else
                        $tags[$tag] = $count;
                });
                arsort($tags);
                $tagList = array_slice(array_keys($tags), 0, 16);
                sort($tagList);
                $content = $tagList;
                $ok = true;
            }
            break;
    }
}

$response[Key::KEY_OK] = $ok;
if ($ok)
    $response[Key::KEY_CONTENT] = $content;

Response::RenderJsonResponse($response);

exit(0);

function getMemoDetailsContent($userId, $memoId) {

    $sql = <<< EOT
SELECT
    memos.user_id AS user_id,
    memos.created AS created,
    memos.updated AS updated,
    memos.private AS private,
    memos.friends_can_edit AS friends_can_edit,
    memos.sync_buckets AS sync_buckets,
    memos.edited_by AS edited_by,
    users.screen_name AS screen_name,
    shared_memo_status.star AS star
FROM
    memos
        INNER JOIN
    users ON memos.user_id = users.id
        INNER JOIN
    shared_memo_status ON memos.id=shared_memo_status.memo_id AND users.id=shared_memo_status.user_id
WHERE
    memos.id = $memoId

EOT;
    $row = Database::QueryOneRow($sql);

    $memoAuthorId = (int)$row['user_id'];

    $previousVersions = Controller::GetHistory($memoId);
    $versionNum = count($previousVersions) + 1;
    $private = $row['private'] ? true : false;
    $friendsCanEdit = $row['friends_can_edit'] ? true : false;
    $syncBuckets = boolval($row['sync_buckets']);
    $star = boolval($row['star']);
    $editedBy = (int)$row['edited_by'];
    $createdDateAsString = $row['created'];

    $sharedUsers = array();
    if (!$private) {
        $sql = "SELECT user_id FROM shared_memo_status WHERE memo_id=$memoId AND available=1 AND user_id<>$userId AND is_author=0";
        Database::QueryCallback($sql, function ($row) use (&$sharedUsers, $memoId) {
            $sharedUsers[] = (int)$row['user_id'];
        });
    }

    $isShared = count($sharedUsers) > 0;

    $memoAuthorBlock = ($memoAuthorId !== $userId) ? View::RenderUserBucketWidget($memoAuthorId, $memoId, false, true) : '';
    $versionBlock = ($versionNum > 1) ? View::RenderInfoWidget('Last Edited', '<displaydate>' . Controller::GetMemoDate($memoId, false) . '</displaydate>') : '';
    $editedByBlock = (($editedBy > 0) && ($editedBy !== $memoAuthorId)) ? View::RenderInfoWidget('Edited by', Content::GetFullNameLink($editedBy)) : '';

    $html =
        $memoAuthorBlock .
        View::RenderInfoWidget('Created', Html::Tag('displaydate', $createdDateAsString)) .
        $versionBlock .
        $editedByBlock .
        View::RenderInfoWidget('Version', $versionNum === 1 ? 'Original' : '#' . strval($versionNum));

    $flagsHtml = '';

    if (Controller::MemoBucketHasChanged($userId, $memoId)) {
        $bucket = Controller::GetMemoBucket($userId, $memoId);
        $bucketName = Bucket::GetShortBucketName($bucket);
        $bucketClass = Bucket::GetBucketClass($bucket);
        $flagsHtml .= View::RenderInfoWidget(Html::Icon($bucketClass) . "&nbsp;Moved to $bucketName", Html::Tag('displaydate', Controller::GetLastBucketChangeDateString($userId, $memoId)));
    }

    if ($star)
        $flagsHtml .= View::RenderInfoWidget(Html::Icon('star_on') . '&nbsp;Star', 'You have Starred this memo.');

    if ($private)
        $flagsHtml .= View::RenderInfoWidget(Html::Icon('private') . '&nbsp;Private', ' This memo will not be shared, regardless of any shares that might apply.');

    $alarmDate = Controller::GetAlarmForMemo($userId, $memoId);
    if (!is_null($alarmDate))
        $flagsHtml .= View::RenderInfoWidget(Html::Icon('alarm_on') . '&nbsp;Alarm Set For', Html::Tag('displaydate', $alarmDate->format(Util::DISPLAY_DATE_FORMAT)));

    if ($isShared) {
        if ($friendsCanEdit)
            $flagsHtml .= View::RenderInfoWidget(Html::Icon('can_edit_on') . '&nbsp;Editing', 'Friends who can view this memo can also edit it.');
        if ($syncBuckets)
            $flagsHtml .= View::RenderInfoWidget(Html::Icon('bucket') . '&nbsp;Buckets Sync&apos;d', 'This memo&apos;s buckets will by synchronized between all users that share it.');
    }
    if (strlen($flagsHtml))
        $html .= Html::HR() . $flagsHtml;

    if ($isShared) {
        $html .=
            Html::HR() .
            Html::SubHeading($userId === $memoAuthorId ? 'Shared With' : 'Also Shared With');
        foreach ($sharedUsers as $sharedUser)
            $html .= View::RenderUserBucketWidget($sharedUser, $memoId, true);
    }

    if (count($previousVersions) > 0 && Controller::UserCanEditMemo($userId, $memoId)) {
        $html .=
            Html::HR() .
            Html::SubHeading('Previous Versions');
        foreach ($previousVersions as $pv) {
            $html .= Html::Tag('memo', json_encode(Controller::GetSingleMemoData($userId, $pv, true)));
            $editedBy = (int)Database::LookupValue('history', 'id', $pv, 'i', 'edited_by', 0);
            if (($editedBy > 0) && ($editedBy != $memoAuthorId)) {
                $html .= View::RenderInfoWidget('Edited by', Content::GetFullNameLink($editedBy));
                $html .= Html::HR();
            }
        }
    }
    return $html;
}