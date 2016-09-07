<?php

require_once('global.php');

$userId = Session::DoAjaxSession(true);

/**
 * @param $tzOffset
 * @param $userId
 */

if ($userId > 0) {
    $tzOffset = UserInput::Extract(Key::KEY_TIME_ZONE_OFFSET, 0);
    Account::SetTimeZoneOffset($userId, $tzOffset);
    Response::RenderJsonResponse(
        array(
            Key::KEY_DATA_VERSION => Database::LookupValue('users', 'id', $userId, 'i', 'data_version', 1),
            Key::KEY_VALIDATED => Session::IsValidated(),
            Key::KEY_USER_ID => $userId,
            Key::KEY_OK => true));
} else {
    Response::RenderJsonResponse(
        array(
            Key::KEY_USER_ID => 0,
            Key::KEY_VALIDATED => false,
            Key::KEY_OK => false));
}
