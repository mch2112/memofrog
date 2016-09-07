<?php

require_once('global.php');

$userId = Session::DoAjaxSession(true);

try {
    if ($userId > 0) {

        $targetMemoId = UserInput::Extract(Key::KEY_TARGET_MEMO, 0);
        if ($targetMemoId > 0) {
            // REQ for a single memo
            if (Controller::UserCanViewMemo($userId, $targetMemoId)) {
                $data = Controller::GetSingleMemoData($userId, $targetMemoId, false);
                Response::RenderJsonResponse(
                    array(
                        Key::KEY_USER_ID => $userId,
                        Key::KEY_MEMO_DATA => $data,
                        Key::KEY_OK => true,
                        Key::KEY_DATA_VERSION => Database::LookupValue('users', 'id', $userId, 'i', 'data_version', 1),
                        Key::KEY_TARGET_MEMO => $targetMemoId));
                exit(0);
            }
        } else {

            // A list of memos based on a filter key

            $friendScreenName = UserInput::Extract(Key::KEY_SCREEN_NAME, '');
            $to = UserInput::Extract(Key::KEY_SCREEN_NAME_TO, false);

            if (strlen($friendScreenName) > 0)
                $shareUserId = Account::GetUserIdFromScreenName($friendScreenName);
            else
                $shareUserId = 0;

            $bucket = UserInput::Extract(Key::KEY_BUCKET, Bucket::BUCKET_HOT_LIST);

            $memosToReq = UserInput::Extract(Key::KEY_NUM_MEMOS_REQ, 0);
            $special = UserInput::Extract(Key::KEY_SPECIAL_SEARCH, Search::SPECIAL_SEARCH_NONE);
            $tags = UserInput::Extract(Key::KEY_FILTER_TAGS, '');
            $tagsOp = UserInput::Extract(Key::KEY_FILTER_TAGS_OP);
            $filterText = UserInput::Extract(Key::KEY_FILTER_TEXT, '');

            $valid = $shareUserId > 0 || strlen($friendScreenName) === 0;

            if ($valid)
                $memos = Controller::GetMemoData($memosToReq, 0, $userId, $shareUserId, $to, $tags, $tagsOp, $filterText, $bucket, $special);
            else
                $memos = array();

            $dataVersion = Database::LookupValue('users', 'id', $userId, 'i', 'data_version', 1);

            Response::RenderJsonResponse(
                array(
                    Key::KEY_USER_ID => $userId,
                    Key::KEY_OK => true,
                    Key::KEY_MEMO_LIST => $memos,
                    Key::KEY_DATA_VERSION => $dataVersion,
                    Key::KEY_ALERT => Session::GetAlert())
            );
            exit(0);
        }
    }
    Response::RenderJsonResponse(
        array(
            Key::KEY_OK => false,
            Key::KEY_REDIRECT => true,
            Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_LOGIN
        ));

} catch (Exception $e) {
    Session::SetException($e);
    Response::RenderJsonResponse(
        array(
            Key::KEY_OK => false,
            Key::KEY_REDIRECT => true,
            Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_ERROR));
}