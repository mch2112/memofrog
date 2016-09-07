<?php

require_once('global.php');

$userId = Session::DoAjaxSession(true);

switch (UserInput::Extract(Key::KEY_VALIDATION_TYPE, 0)) {
    case Validation::VALIDATION_TYPE_NEW_SHARE:
        $validation = Validation::GetNewShareValidation(
            $userId,
            UserInput::Extract(Key::KEY_SHARE_WITH_SCREEN_NAME, ''),
            UserInput::Extract(Key::KEY_SHARE_TAGS, ''));
        break;
    case Validation::VALIDATION_TYPE_REGISTER:
        $validation = Validation::GetRegisterValidation(
            UserInput::Extract(Key::KEY_REGISTER_REAL_NAME, ''),
            UserInput::Extract(Key::KEY_REGISTER_SCREEN_NAME, ''),
            UserInput::Extract(Key::KEY_REGISTER_EMAIL, ''),
            UserInput::Extract(Key::KEY_REGISTER_PASSWORD, ''));
        break;
    case Validation::VALIDATION_TYPE_CHANGE_PASSWORD:
        $validation = Validation::GetChangePasswordValidation(
            UserInput::Extract(Key::KEY_CHANGE_PASSWORD_OLD, ''),
            UserInput::Extract(Key::KEY_CHANGE_PASSWORD_NEW, ''));
        break;
    case Validation::VALIDATION_TYPE_ACCOUNT_DETAILS:
        $validation = Validation::GetAccountDetailsValidation(
            $userId,
            UserInput::Extract(Key::KEY_ACCOUNT_DETAILS_SCREEN_NAME, ''),
            UserInput::Extract(Key::KEY_ACCOUNT_DETAILS_REAL_NAME, ''));
        break;
    case Validation::VALIDATION_TYPE_CHANGE_EMAIL:
        $validation = Validation::GetChangeEmailValidation(
            $userId,
            UserInput::Extract(Key::KEY_ACCOUNT_DETAILS_EMAIL, ''));
        break;
    default:
        $validation = array();
        break;
}
Response::RenderJsonResponse($validation);
