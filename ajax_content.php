<?php

const MAX_REDIRECTS = 10;

require_once('global.php');

$userId = Session::DoAjaxSession(false);
$wasLoggedIn = $userId > 0;
$response = array();
$error = ErrorCode::ERROR_NONE;

if (UserInput::IsKeySet(Key::KEY_VALIDATION_KEY))
    $contentReq = ContentKey::CONTENT_KEY_VALIDATE_EMAIL_COMPLETE;
else if (UserInput::IsKeySet(Key::KEY_PASSWORD_RESET_KEY))
    $contentReq = ContentKey::CONTENT_KEY_RESET_PASSWORD;
else if (UserInput::IsKeySet(Key::KEY_EXTERNAL_CONTENT_REQ))
    $contentReq = UserInput::Extract(Key::KEY_EXTERNAL_CONTENT_REQ);
else
    $contentReq = UserInput::Extract(Key::KEY_CONTENT_REQ, ContentKey::CONTENT_KEY_NONE);

$clientVersion = UserInput::Extract(Key::KEY_APP_VERSION, APP_VERSION);

$response[Key::KEY_CONTENT_REQ] = $contentReq;
$content = null;

switch ($contentReq) {
    case ContentKey::CONTENT_KEY_NONE:
        if ($userId > 0)
            $contentReq = ContentKey::CONTENT_KEY_HOME;
        else
            $contentReq = ContentKey::CONTENT_KEY_LOGIN;
        break;
    case ContentKey::CONTENT_KEY_REDIRECT:
        // Should have been rendered locally
        $error = ErrorCode::ERROR_INVALID_REDIRECT;
        break;
}

if ($error === ErrorCode::ERROR_NONE) {
    $content = getContent($userId, $contentReq, $error, 0);
    $userId = Account::GetUserId();
    if ($userId > 0) {
        // Check for Get parameters that are now valid since we're logged in
        $externalShareId = UserInput::Extract(Key::KEY_EXTERNAL_SHARE_ID, 0);
        $externalMemoId = UserInput::Extract(Key::KEY_EXTERNAL_MEMO_ID, 0);
        if ($externalShareId > 0) {
            if (Controller::UserIsAssociatedWithShare($userId, $externalShareId)) {
                if (Controller::GetShareTarget($externalShareId) !== $userId)
                    $externalShareId = Controller::GetReciprocalShareId($externalShareId);
                if ($externalShareId > 0 && Controller::ShareIsEnabled($externalShareId)) {
                    $response[Key::KEY_CLEAR_FILTERS] = true;
                    $response[Key::KEY_BUCKET] = Bucket::BUCKET_EVERYTHING;
                    $response[Key::KEY_FILTER_TAGS] = Controller::GetTagsAsString($externalShareId, false);
                    $response[Key::KEY_SCREEN_NAME] = Account::GetScreenName(Database::LookupValue('shares', 'id', $externalShareId, 'i', 'source_user_id', 0));
                    $response[Key::KEY_SCREEN_NAME_TO] = false;
                    $contentReq = ContentKey::CONTENT_KEY_HOME;
                }
            } else {
                $error = ErrorCode::ERROR_INADEQUATE_AUTH;
            }
            UserInput::Clear(Key::KEY_EXTERNAL_SHARE_ID);
        } else if ($externalMemoId > 0) {
            if (Controller::UserCanViewMemo($userId, $externalMemoId)) {
                $contentReq = ContentKey::CONTENT_KEY_MEMO_DETAILS;
                $response[Key::KEY_TARGET_MEMO] = $externalMemoId;
            } else {
                $error = ErrorCode::ERROR_INADEQUATE_AUTH;
            }
            UserInput::Clear(Key::KEY_EXTERNAL_MEMO_ID);
        }
        if (!$wasLoggedIn)
            $response[Key::KEY_DEFAULT_BUCKET] = Account::GetDefaultBucket($userId);
    }
    if ($error === ErrorCode::ERROR_NONE) {
        $response = $content->GetResponseVars($response);
        $response[Key::KEY_CONTENT] = $content->GetHtml();
        $response[Key::KEY_CACHEABLE] = $content->GetCacheable();
        $response[Key::KEY_CONTENT_KEY] = $content->GetContentId();
        $response[Key::KEY_NEEDS_VALIDATION_SUPPRESSED] = $content->GetNeedsEmailValSuppressed();
        if ($content->GetRedirect() !== ContentKey::CONTENT_KEY_NONE) {
            $response[Key::KEY_CONTENT_REQ] = $content->GetRedirect();
            $response[Key::KEY_REDIRECT] = true;
        }
        if ($content->GetForceRestart())
            $response[Key::KEY_FORCE_RESTART] = true;
        if (strlen($content->GetScript()) > 0)
            $response[Key::KEY_SCRIPT] = $content->GetScript();
        if (count($content->getContentData()))
            $response[Key::KEY_CONTENT_DATA] = $content->getContentData();
        switch ($content->GetSuppressTips()) {
            case Content::SUPPRESS_TIPS_MOBILE:
                $response[Key::KEY_TIPS_SUPPRESSED] = Session::IsMobile();
                break;
            case Content::SUPPRESS_TIPS_ALL:
                $response[Key::KEY_TIPS_SUPPRESSED] = true;
                break;
            default:
                $response[Key::KEY_TIPS_SUPPRESSED] = false;
                break;
        }
    }
    $userId = Account::GetUserId(); // could have changed in render
}

if (strlen(Content::GetAlert()) > 0)
    $response[Key::KEY_ALERT] = Content::GetAlert();

if (strcmp($clientVersion, APP_VERSION) < 0)
    $response[Key::KEY_FORCE_RESTART] = true;

$response[Key::KEY_ERROR] = $error;
$response[Key::KEY_USER_ID] = $userId;
$response[Key::KEY_IS_LOCAL] = false;
$response[Key::KEY_VALIDATED] = Session::IsValidated();
$response[Key::KEY_CONTENT_REQ] = $contentReq;

if (UserInput::Extract(Key::KEY_RESET_DB, false))
    $response[Key::KEY_RESET_DB] = true;

if ($userId > 0)
    $response[Key::KEY_DATA_VERSION] = Database::LookupValue('users', 'id', $userId, 'i', 'data_version', 1);

Response::RenderJsonResponse($response);

exit(0);

function getContent($userId, &$contentKey, &$error, $tries)
{
    if ($tries > MAX_REDIRECTS) {
        $error = ErrorCode::REDIRECT_CYCLE_ERROR;
        return null;
    }

    $content = Content::GetContent($contentKey);

    if (is_null($content)) {
        $error = ErrorCode::ERROR_CONTENT_NOT_FOUND;
        return null;
    }

    if (!Session::UserAuthorizedFor($content->AuthLevel())) {
        if ($userId <= 0)
            $error = ErrorCode::ERROR_NEED_LOGIN;
        else
            $error = ErrorCode::ERROR_INADEQUATE_AUTH;
        return null;
    }

    $content->Render($userId);

    if ($content->GetError() !== ErrorCode::ERROR_NONE) {
        $error = $content->GetError();
        return null;
    }

    if ($content->GetRedirect() !== ContentKey::CONTENT_KEY_NONE) {
        $contentKey = $content->GetRedirect();
        return getContent($userId, $contentKey, $error, $tries + 1);
    }

    return $content;
}

