var KEY_POST_TYPE = "postType";
var KEY_MEMO_DATA = "memoData";
var KEY_POST_NEW_VALUE = "newValue";

var POST_SET_STAR = 4010;
var POST_SET_BUCKET = 4020;
var POST_EDIT_MEMO = 4030;
var POST_CREATE_MEMO = 4040;
var POST_SET_ALARM = 4050;

function doMemoChange(postType, memoId, newValue, isUndo) {
    // cache change in DB (async)
    // DB will send changes to server

    nav.targetMemo = memoId;

    var post = {};
    post[KEY_MEMO_ID] = memoId;
    post[KEY_POST_TYPE] = postType;
    post[KEY_POST_NEW_VALUE] = newValue;
    post.txGuid = getGUID();

    return frogDB.fetchMemo(memoId, false).catch(function () {
        //noinspection JSUnresolvedVariable
        if (postType === POST_CREATE_MEMO)
            return Dexie.Promise.resolve();
        else
            return Dexie.Promise.reject();
    }).then(function (prevMemo) {
        post.oldMemo = prevMemo;
        return frogDB.storePost(post, nav.getFilterKey()).then(function () {
            switch (post.postType) {
                case POST_SET_STAR:
                    alertBox.showAlert(post.newValue ? "Memo starred" : "Memo unstarred");
                    break;
                case POST_SET_BUCKET:
                    if (newValue !== prevMemo.bucket)
                        alertBox.showAlert(getBucketMessage(memoId, newValue, prevMemo.bucket, isUndo));
                    break;
                case POST_SET_ALARM:
                    alertBox.showAlert(getAlarmMessage(memoId, newValue, prevMemo.alarmDate, isUndo));
                    break;
            }
            return Dexie.Promise.resolve();
        });
    });
}
function getAlarmMessage(memoId, newDate, prevAlarmDate, isUndo) {
    var icon = "<span class=\"inline_icon_large img_alarm_on\"></span>";
    var msg;
    if (newDate === null || newDate.length === 0)
        msg = "Alarm removed.";
    else if (newDate === prevAlarmDate)
        msg = "Alarm not changed.";
    else
        msg = "Alarm set to " + moment(newDate).format("dddd, MMMM Do YYYY") + ".";

    msg = icon + "&nbsp;" + msg;

    if (!isUndo && newDate !== prevAlarmDate)
        msg += "&nbsp;&nbsp;&nbsp;&nbsp;" + a("link_button", "Undo", "setAlarmDate(" + memoId.toString() + ", '" + (prevAlarmDate || "") + "', true);");

    return msg;
}
function getBucketMessage(memoId, bucket, prevBucket, isUndo) {
    var icon = "<span class=\"inline_icon_large img_bucket" + bucket.toString() + "\"></span>";
    var msg;
    switch (bucket) {
        case BUCKET_HOT_LIST:
            msg = "Memo sent to Hot List.";
            break;
        case BUCKET_B_LIST:
            msg = "Memo sent to B List.";
            break;
        case BUCKET_TRASH:
            msg = "Memo sent to Trash.";
            break;
        case BUCKET_DONE:
            msg = "Memo marked as &quot;Done.&quot;";
            break;
        case BUCKET_JOURNAL:
            msg = "Memo sent to Journal.";
            break;
        case BUCKET_REFERENCE:
            msg = "Memo sent to Reference.";
            break;
        case BUCKET_HIDDEN:
            msg = "Memo is hidden.";
            break;
        case BUCKET_DELETED:
            msg = "Memo Deleted.";
            break;
        default:
            msg = 'Error setting bucket.';
            break;
    }
    msg = icon + "&nbsp;" + msg;
    if (!isUndo && bucket !== BUCKET_DELETED) {
        msg += "&nbsp;&nbsp;&nbsp;&nbsp;" + a("link_button", "Undo", "setBucket(" + memoId.toString() + ", " + prevBucket.toString() + ", true);");
    }
    return msg;
}
