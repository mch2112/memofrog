<?php

require_once('global.php');

const DATA_REQ_NONE = 0;
const DATA_REQ_TAGS = 110;
const DATA_REQ_FRIENDS = 120;

$userId = Session::DoAjaxSession(true);

try {
    $ok = false;
    $dataReq = UserInput::Extract(Key::KEY_DATA_REQ, DATA_REQ_NONE);
    if ($userId > 0) {
        $data = null;
        switch ($dataReq) {
            case DATA_REQ_TAGS:
                $data = Database::QueryArray(Sql::GetTagsForUserSql($userId));
                // needed to convert counts to int
                $data = array_map(function ($t) {
                    return array('tag' => $t['tag'], 'own_count' => (int)$t['own_count'], 'other_count' => (int)$t['other_count']);
                }, $data);
                $data = array('tags' => $data);
                $ok = true;
                break;
            case DATA_REQ_FRIENDS:
                $data = array();
                Controller::GetFriendsCallback($userId, function ($row) use (&$data, $userId) {
                    $numMemos = Controller::GetMemoCountWithFriend($userId, (int)$row['user_id']);
                    $data[] = array(
                        Key::KEY_MEMO_COUNT => $numMemos,
                        Key::KEY_SCREEN_NAME => $row['screen_name'],
                        Key::KEY_REAL_NAME => $row['real_name']
                    );
                });
                $data = array('friends' => $data);
                $ok = true;
                break;
        }
    }
    if ($ok) {
        $response =
            array(
                Key::KEY_USER_ID => $userId,
                Key::KEY_DATA_REQ => $dataReq,
                Key::KEY_DATA => $data,
                Key::KEY_OK => true,
                Key::KEY_DATA_VERSION => Database::LookupValue('users', 'id', $userId, 'i', 'data_version', 1));
    } else {
        $response =
            array(
                Key::KEY_OK => false,
                Key::KEY_USER_ID => 0,
                Key::KEY_REDIRECT => true,
                Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_LOGIN
            );
    }
} catch (Exception $e) {
    Session::SetException($e);
    $response =
        array(
            Key::KEY_OK => false,
            Key::KEY_REDIRECT => true,
            Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_ERROR);
}
Response::RenderJsonResponse($response);
