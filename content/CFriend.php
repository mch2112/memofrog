<?php

class CFriend extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_FRIEND;
    }

    public function AuthLevel()
    {
        return LoginStatus::OK;
    }

    public function Render($userId)
    {
        $screenName = UserInput::Extract(Key::KEY_FRIEND, '');
        $friendId = Account::GetUserIdFromScreenName($screenName);
        if ($friendId === $userId) {
            $this->setError(ErrorCode::ERROR_GENERAL);
            return;
        } else if ($friendId < 0) {
            $this->setError(ErrorCode::ERROR_FRIEND_NOT_FOUND);
            return;
        }
        $row = Database::QueryOneRow("SELECT users.screen_name AS screen_name, accounts.real_name AS real_name FROM users INNER JOIN accounts ON users.id=accounts.user_id WHERE users.id=$friendId");
        $screenName = $row['screen_name'];
        $realName = $row['real_name'];
        $shareOutCount = Controller::GetNumberOfMemosShared($userId, $friendId, true);
        $shareInCount = Controller::GetNumberOfMemosShared($friendId, $userId, false);

        $sql = <<< EOT
SELECT DISTINCT
    SUM(direction) AS direction, tags, MAX(source_share_id) AS sourceId, MAX(target_share_id) AS targetId, SUM(enabled) AS enabled, MAX(editableIn) AS editableIn, MAX(editableOut) AS editableOut
FROM
    (SELECT
        1 AS direction,
        GROUP_CONCAT(CONCAT('#',tags.tag) SEPARATOR '+') AS tags,
        shares.id as target_share_id,
        null AS source_share_id,
        shares.target_enabled * 1 AS enabled,
        0 AS editableOut,
        shares.can_edit AS editableIn
    FROM
        users
    INNER JOIN shares ON users.id = shares.source_user_id
    INNER JOIN shares_tags ON shares.id = shares_tags.share_id
    INNER JOIN tags ON tags.id = shares_tags.tag_id
    WHERE
        shares.target_user_id=$userId AND
        shares.source_user_id=$friendId AND
        shares.deleted = 0 AND
        shares.source_enabled=1
    GROUP BY shares.id

UNION

SELECT
    2 AS direction,
    GROUP_CONCAT(CONCAT('#', tags.tag) SEPARATOR '+') AS tags,
    null AS target_share_id,
    shares.id as source_share_id,
    shares.source_enabled * 2 AS enabled,
    shares.can_edit AS editableOut,
    0 AS editableI
FROM
    users
        INNER JOIN
    shares ON users.id = shares.target_user_id
        INNER JOIN
    shares_tags ON shares.id = shares_tags.share_id
        INNER JOIN
    tags ON tags.id = shares_tags.tag_id
WHERE
    shares.deleted = 0 AND
    shares.source_user_id=$userId AND
    shares.target_user_id=$friendId
GROUP BY shares.id) q

GROUP BY tags
ORDER BY tags
EOT;

        $this->contentData =
            array('data' =>
                array('friend' =>
                    array(Key::KEY_SCREEN_NAME => $screenName,
                        Key::KEY_REAL_NAME => $realName,
                        'numMemosIn' => $shareInCount,
                        'numMemosOut' => $shareOutCount,
                        'shares' => array_map(function ($s) {
                            if (is_null($s['sourceId']))
                                $shareId = (int)$s['targetId'];
                            else
                                $shareId = (int)$s['sourceId'];

                            // bitmaps (0x02 -> out, 0x01 -> in)
                            $dir = (int)$s['direction'];
                            $enabled = (int)$s['enabled'];

                            $sharingIn = boolval($dir & $enabled & 0x01);
                            $sharingOut = boolval($dir & $enabled & 0x02);

                            if ($sharingIn)
                                if ($sharingOut)
                                    $description = 'Full Share';
                                else
                                    $description = 'In Only';
                            else if ($sharingOut)
                                $description = 'Out Only';
                            else
                                $description = 'Disabled';

                            return array(
                                'id' => $shareId,
                                'tags' => $s['tags'],
                                'inAvailable' => boolval($dir & 0x01),
                                'sharingIn' => $sharingIn,
                                'sharingOut' => $sharingOut,
                                'editableIn' => $sharingIn && $s['editableIn'],
                                'editableOut' => $sharingOut && $s['editableOut'],
                                'description' => $description
                            );
                        }, Database::QueryArray($sql)))));
    }

//    private function renderShare($row)
//    {
//        $rawTags = $row['tags'];
//
//
//        $userCanEditIcon = $userCanEdit ? Html::LargeIcon('edit') : Html::LargeIcon('blank');
//        $friendCanEditIcon = $friendCanEdit ? Html::LargeIcon('edit') : Html::LargeIcon('blank');
//
//        if ($sharingIn)
//            if ($sharingOut)
//                $description = 'Full Share';
//            else
//                $description = 'In Only';
//        else if ($sharingOut)
//            $description = 'Out Only';
//        else
//            $description = 'Disabled';
//
//        if ($canShareIn)
//            $inClass = $sharingIn ? 'share_in' : 'share_in_disabled';
//        else
//            $inClass = 'share_none';
//
//        $outClass = $sharingOut ? 'share_out' : 'share_out_disabled';
//
//        $link_args = array(Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_EDIT_SHARE, Key::KEY_SHARE_ID => $shareId);
//
//        return self::GetNavLinkWithArgs(
//            Html::Div('share big_list_item hoverable', Html::Div('share_tags', $rawTags) .
//                Html::Div('share_graphic',
//                    $userCanEditIcon .
//                    Html::Div('share_icon ' . $inClass, '') .
//                    Html::Div('share_icon ' . $outClass, '') .
//                    $friendCanEditIcon .
//                    Html::Div('share_description', $description))), $link_args);
//    }
}