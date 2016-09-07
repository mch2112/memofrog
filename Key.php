<?php

class Key
{
    const KEY_CONTENT_REQ = 'contentReq';
    const KEY_CONTENT_KEY = 'contentKey';
    const KEY_CONTENT = 'key_content';

    const KEY_EXTERNAL_CONTENT_REQ = 'CReq';
    const KEY_EXTERNAL_MEMO_ID = 'MReq';
    const KEY_EXTERNAL_SHARE_ID = 'SReq';

    const KEY_PENDING_MEMO_TEMP_ID = 'memoTempId';
    const KEY_NEW_EDIT_MEMO_TEXT = 'memoText';
    const KEY_NEW_EDIT_MEMO_PRIVATE = 'memoPrivate';
    const KEY_NEW_EDIT_MEMO_FRIENDS_CAN_EDIT = 'friendsCanEdit';
    const KEY_NEW_EDIT_MEMO_SYNC_BUCKETS = 'syncBuckets';
    const KEY_ALARM_DATE = 'key_alarm_date';
    const KEY_ALARM_ENABLED = 'key_alarm_enabled';

    const KEY_TAG = 'tag';
    
    const KEY_DELETE_MEMO_CONFIRMED = 'key_delete_memo_confirmed';

    const KEY_POST_TYPE = 'postType';
    const KEY_POST_TX_ID = 'txId';
    const KEY_POST_TX_GUID = 'txGuid';
    const KEY_FORCE_DELETE_TX = 'forceDeleteTx';
    const KEY_POST_USER_ID = 'userId';
    const KEY_POST_MEMO_ID = 'memoId';
    const KEY_POST_NEW_VALUE = 'newValue';
    const KEY_POST_SUCCESS = 'keyPostSuccess';

    const KEY_TOGGLE_MOVE_DONE_TO_TRASH = 'key_toggle_move_done_to_trash';

    const KEY_VALIDATION_KEY = 'validation_key';

    const KEY_CACHEABLE = 'cacheable';
    const KEY_IS_LOCAL = 'isLocal';
    const KEY_CONTENT_DATA = 'contentData';

    const KEY_RESET_DB = 'resetDB';

    const KEY_MEMO_COUNT = 'memoCount';
    const KEY_MEMO_LIST = 'key_memo_list';
    const KEY_PREVIOUS_DATA_VERSION = 'previousDataVersion';
    const KEY_DATA_VERSION = 'dataVersion';
    const KEY_DATA_REQ = 'dataReq';
    const KEY_DATA = 'data';
    const KEY_MEMO_TEXT = 'key_memo_text';
    const KEY_MEMO_PRIVATE = 'key_memo_private';
    const KEY_MEMO_SHARED = 'key_memo_shared';
    const KEY_MEMO_SYNC_BUCKETS = 'key_memo_sync_buckets';
    const KEY_MEMO_FRIENDS_CAN_EDIT = 'key_memo_friends_can_edit';
    const KEY_MEMO_ALARM = 'key_memo_alarm';
    const KEY_MEMO_BUCKET = 'memoBucket';
    const KEY_MEMO_EDITED = 'key_memo_edited';
    const KEY_MEMO_STAR = 'key_memo_star';
    const KEY_MEMO_CAN_EDIT = 'key_memo_can_edit';
    const KEY_MEMO_AUTHOR_NAME = 'key_memo_author_name';
    const KEY_MEMO_IS_AUTHOR = 'isAuthor';
    const KEY_MEMO_TIMESTAMP = 'timeStamp';
    const KEY_MEMO_MOVE_DATE = 'moveDate';
    const KEY_MEMO_CREATE_DATE = 'createDate';
    const KEY_MEMO_EDIT_DATE = 'editDate';
    const KEY_MEMO_ID = 'memoId';
    const KEY_MEMO_IS_HISTORIC = 'key_memo_is_historic';
    const KEY_IS_UNDO = 'key_is_undo';
    const KEY_PLACEHOLDER_KEY = "placeholderKey";
    const KEY_PLACEHOLDER_TX_ID = "placeholderTxId";
    const KEY_SUGGESTED_TAGS = 'key_suggested_tags';
    const KEY_MEMO_DATA = "memoData";

    const KEY_FILTER_TAGS = 'filterTags';
    const KEY_FILTER_TAGS_OP = 'filterTagsOp';
    const KEY_BUCKET = 'bucket';
    const KEY_SCREEN_NAME_TO = 'screenNameTo';
    const KEY_FILTER_TEXT = 'filterText';
    const KEY_CLEAR_FILTERS = 'clearFilters';
    const KEY_FILTER_TEXT_INPUT = 'filterTextInput';
    const KEY_TARGET_MEMO = 'targetMemo';
    const KEY_NUM_MEMOS_REQ = 'numMemosReq';
    const KEY_VALIDATED = 'validated';
    const KEY_USER_ID = 'userId';
    const KEY_OK = 'ok';
    const KEY_ERROR = 'error';
    const KEY_ALERT = 'alert';
    const KEY_SCRIPT = 'key_script';
    const KEY_REDIRECT = 'key_redirect';
    const KEY_FORCE_RESTART = 'forceRestart';
    const KEY_DEFAULT_BUCKET = 'defaultBucket';
    const KEY_SHARE_ENABLED = 'key_share_enabled';
    const KEY_SHARE_ID = 'shareId';
    const KEY_ENABLE_SHARE_SOURCE = 'key_enable_share_source';
    const KEY_DISABLE_SHARE_SOURCE = 'key_disable_share_source';
    const KEY_ENABLE_SHARE_TARGET = 'key_enable_share_target';
    const KEY_DISABLE_SHARE_TARGET = 'key_disable_share_target';
    const KEY_SHARE_TO_DELETE = 'key_share_to_delete';
    const KEY_SHARE_TO_UNDELETE = 'key_share_to_undelete';
    const KEY_SHARE_CAN_EDIT_TO_TOGGLE = 'key_share_can_edit_to_toggle';

    const KEY_TAGS_SCREEN_SORTING = 'key_tags_screen_sorting';
    const KEY_TAGS_SCREEN_FILTER = 'key_tags_screen_filter';

    const KEY_RENAME_TAG_OLD_TAG = 'key_rename_tag_old_tag';
    const KEY_RENAME_TAG_NEW_TAG = 'key_rename_tag_new_tag';

    const KEY_REGISTER_EMAIL = 'key_register_email';
    const KEY_REGISTER_SCREEN_NAME = 'key_register_screen_name';
    const KEY_REGISTER_REAL_NAME = 'key_register_real_name';
    const KEY_REGISTER_PASSWORD = 'key_register_password';

    const KEY_CHANGE_PASSWORD_OLD = 'key_change_password_old';
    const KEY_CHANGE_PASSWORD_NEW = 'key_change_password_new';

    const KEY_LOGIN_AS_USER_ID = 'key_login_as_user_id';

    const KEY_ACCOUNT_DETAILS_SCREEN_NAME = 'acctScreenName';
    const KEY_ACCOUNT_DETAILS_REAL_NAME = 'acctRealName';
    const KEY_ACCOUNT_DETAILS_EMAIL = 'acctEmail';

    const KEY_SHARE_WITH_SCREEN_NAME = 'key_share_with_screen_name';
    const KEY_SHARE_TAGS = 'key_share_tags';
    const KEY_SHARE_CAN_EDIT = 'key_share_can_edit';

    const KEY_FRIEND = 'friend';
    const KEY_FRIEND_ID = 'friendId';
    const KEY_SCREEN_NAME = 'screenName';
    const KEY_REAL_NAME = 'realName';

    const KEY_LOGIN_SCREEN_NAME_OR_EMAIL = 'login_screen_name_or_email';
    const KEY_LOGIN_PASSWORD = 'login_password';
    const KEY_LOGIN_REMEMBER_ME = 'login_remember_me';

    const KEY_FORGOT_PASSWORD_SCREEN_NAME_OR_EMAIL = 'key_forgot_password_email_or_screen_name';
    const KEY_PASSWORD_RESET_KEY = 'password_reset_key';

    const KEY_TAG_ID = 'tag';

    const KEY_EMPTY_TRASH = 'key_empty_trash';

    const KEY_VALIDATION = 'key_validation';
    const KEY_VALIDATION_SOURCE = 'key_validation_source';
    const KEY_VALIDATION_TARGET = 'key_validation_target';
    const KEY_VALIDATION_MESSAGE = 'key_validation_message';
    const KEY_VALIDATION_ERROR = 'key_validation_error';
    const KEY_VALIDATION_WARNING = 'key_validation_warning';
    const KEY_VALIDATION_TYPE = 'validationType';
    const KEY_VALIDATION_TARGET_REAL_NAME = 'key_validation_target_real_name';
    const KEY_VALIDATION_TARGET_EMAIL = 'key_validation_target_email';
    const KEY_VALIDATION_TARGET_SCREEN_NAME = 'key_validation_target_screen_name';
    const KEY_VALIDATION_TARGET_PASSWORD = 'key_validation_target_password';
    const KEY_VALIDATION_TARGET_TAGS = 'key_validation_target_tags';
    const KEY_VALIDATION_TARGET_OLD_PASSWORD = 'key_validation_target_old_password';
    const KEY_VALIDATION_TARGET_NEW_PASSWORD = 'key_validation_target_new_password';

    const KEY_SUBMIT_BUTTON = 'key_submit_button';
    const KEY_SUBMIT_OK = 'key_submit_ok';

    const KEY_SPECIAL_SEARCH = 'specialSearch';

    const KEY_TIP = 'key_tip';
    const KEY_TIP_ID = 'key_tip_id';
    const KEY_TIPS_DISABLED = 'key_tips_disabled';
    const KEY_TIPS_SUPPRESSED = 'key_tips_suppressed';
    const KEY_NEEDS_VALIDATION_SUPPRESSED = 'needsValidationSuppressed';
    const KEY_TOGGLE_SHOW_TIPS = 'key_toggle_show_tips';
    const KEY_TOGGLE_EMAIL_ON_ALARM = 'key_toggle_email_on_alarm';
    const KEY_TOGGLE_EMAIL_ON_SHARE = 'key_toggle_email_on_share';

    const KEY_TIME_ZONE_OFFSET = 'key_time_zone_offset';
    const KEY_APP_VERSION = 'appVersion';

    const KEY_ADMIN_VALIDATE_USER_ID = 'key_admin_validate_user_id';

    const KEY_ERROR_CODE = 'error_code';
    const KEY_ERROR_CONTENT_ID = 'error_content_id';
}