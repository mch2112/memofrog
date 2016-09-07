<?php

const POST_NONE = 0;
const POST_SET_STAR = 4010;
const POST_SET_BUCKET = 4020;
const POST_EDIT_MEMO = 4030;
const POST_CREATE_MEMO = 4040;
const POST_SET_ALARM = 4050;

require_once('global.php');

$userId = Session::DoAjaxSession(true);
$memoId = UserInput::Extract(Key::KEY_MEMO_ID, 0);
$postType = UserInput::Extract(Key::KEY_POST_TYPE, POST_NONE);
$securityOK = $userId > 0;

if ($securityOK) {
    switch ($postType) {
        case POST_SET_STAR:
        case POST_SET_BUCKET:
        case POST_SET_ALARM:
            $securityOK &= Controller::UserCanViewMemo($userId, $memoId);
            break;
        case POST_EDIT_MEMO:
            $securityOK &= Controller::UserCanEditMemo($userId, $memoId);
            break;
        case POST_CREATE_MEMO:
            // do nothing
            break;
        default:
            $securityOK = false;
            break;
    }
}

$txId = UserInput::Extract(Key::KEY_POST_TX_ID, '');
$txGuid = UserInput::Extract(Key::KEY_POST_TX_GUID, '');
$newValue = UserInput::Extract(Key::KEY_POST_NEW_VALUE, null);
$prevDataVersion = Database::LookupValue('users', 'id', $userId, 'i', 'data_version', 1);
$ok = $securityOK;
$msg = '';
$response = null;
$isTempMemo = $memoId < 0;
$forceDelete = false;

$ok &= (!$isTempMemo || $postType === POST_CREATE_MEMO);

if ($ok && strlen($txGuid)) {
    $txRecordId = Database::LookupValue('transactions', 'guid', $txGuid, 's', 'id', 0);
    if ($txRecordId > 0) {
        $ok = false;
        Database::ExecuteQuery("UPDATE transactions SET tries=tries+1 WHERE id=$txRecordId");
        $forceDelete = true;
    }
}

if ($ok) {
    try {
        Database::StartTransaction();

        switch ($postType) {
            case POST_SET_STAR:
                ControllerW::SetStar($userId, $memoId, boolval($newValue));
                break;
            case POST_SET_BUCKET:
                ControllerW::SetBucket($userId, $memoId, intval($newValue), null);
                break;
            case POST_SET_ALARM:
                if (is_null($newValue))
                    $newValue = '';
                ControllerW::EditAlarm($userId, $memoId, strlen($newValue) >= 10 ? new DateTime($newValue) : null);
                break;
            case POST_CREATE_MEMO:
                $memoText = UserInput::Extract(Key::KEY_NEW_EDIT_MEMO_TEXT, '');
                $tempMemoId = UserInput::Extract(Key::KEY_PENDING_MEMO_TEMP_ID, 0);
                $private = UserInput::Extract(Key::KEY_NEW_EDIT_MEMO_PRIVATE, false);
                $friendsCanEdit = UserInput::Extract(Key::KEY_NEW_EDIT_MEMO_FRIENDS_CAN_EDIT, false);
                $syncBuckets = UserInput::Extract(Key::KEY_NEW_EDIT_MEMO_SYNC_BUCKETS, false);
                $star = UserInput::Extract(Key::KEY_MEMO_STAR, false);
                if ($memoText === 'HAMSTER') {
                    $memoId = 0;
                    $response = array(
                        Key::KEY_REDIRECT => true,
                        Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_EASTER_EGG,
                        Key::KEY_PENDING_MEMO_TEMP_ID => $tempMemoId);
                } else {
                    $bucket = UserInput::Extract(Key::KEY_MEMO_BUCKET, Bucket::BUCKET_HOT_LIST);
                    $memoId = ControllerW::CreateMemo($userId, $memoText, $bucket, $private, $friendsCanEdit, $syncBuckets);
                    if ($star)
                        ControllerW::SetStar($userId, $memoId, true);
                    $response = array(
                        Key::KEY_PENDING_MEMO_TEMP_ID => $tempMemoId);
                }
                break;
            case POST_EDIT_MEMO:
                $memoText = UserInput::Extract(Key::KEY_NEW_EDIT_MEMO_TEXT, '');
                ControllerW::SetBucket($userId, $memoId, UserInput::Extract(Key::KEY_MEMO_BUCKET), UserInput::Extract(Key::KEY_MEMO_STAR));
                if (Controller::GetMemoAuthor($memoId) === $userId) {
                    $private = UserInput::Extract(Key::KEY_NEW_EDIT_MEMO_PRIVATE, false);
                    $friendsCanEdit = UserInput::Extract(Key::KEY_NEW_EDIT_MEMO_FRIENDS_CAN_EDIT, false);
                    $syncBuckets = UserInput::Extract(Key::KEY_NEW_EDIT_MEMO_SYNC_BUCKETS, false);
                    ControllerW::EditMemo($userId, $memoId, $memoText, $private, $friendsCanEdit, $syncBuckets);
                } else {
                    ControllerW::EditMemo($userId, $memoId, $memoText, null, null, null);
                }
                break;
            default:
                $msg = "[ajax_post]: Invalid Post Type: $postType";
                $forceDelete = true;
                $ok = false;
                break;
        }
        $ok &= Database::CommitTransaction();
    } catch (Exception $e) {
        Database::Rollback();
        $ok = false;
    }
}

if ($ok)
    Database::ExecutePreparedStatement(
        "INSERT INTO transactions (guid, user_id, tries) VALUES (?,?,?)", "sii", array($txGuid, $userId, 1));

$response2 = array(
    Key::KEY_USER_ID => $userId,
    Key::KEY_POST_TYPE => $postType,
    Key::KEY_POST_TX_ID => $txId,
    Key::KEY_POST_TX_GUID => $txGuid,
    Key::KEY_MEMO_ID => $memoId,
    Key::KEY_DATA_VERSION => Database::LookupValue('users', 'id', $userId, 'i', 'data_version', 1),
    Key::KEY_PREVIOUS_DATA_VERSION => $prevDataVersion,
    Key::KEY_POST_SUCCESS => $ok);

$forceDelete |= (!$securityOK && !$isTempMemo && $userId > 0);
if ($forceDelete)
    $response2[Key::KEY_FORCE_DELETE_TX] = true;

if ($ok)
    $response2[Key::KEY_MEMO_DATA] = Controller::GetSingleMemoData($userId, $memoId, false);

if (strlen($msg))
    $response2[Key::KEY_ALERT] = $msg;

if (is_null($response))
    $response = $response2;
else
    $response = array_merge($response, $response2);

Response::RenderJsonResponse($response);

exit(0);
