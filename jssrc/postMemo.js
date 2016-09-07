function createMemoFromQE() {
    var textArea = document.getElementById('quick_entry_textarea');
    var text = textArea.value.trim();
    if (text.length === 0)
        return;
    var bucket;
    switch (nav.getBucket()) {
        case BUCKET_JOURNAL:
            bucket = BUCKET_JOURNAL;
            break;
        default:
            bucket = BUCKET_HOT_LIST;
            break;
    }
    createMemo(text, false, bucket, false, false, false).then(function() {
        activateQuickEntry(false, true);
    });
}
function getBucketFromClass(className) {
    var res = className.match(/bucket([0-9]+)/);
    if (res)
        return parseInt(res[1]);
    else
        return BUCKET_NONE;
}
function createMemoFromForm(textArea, toolbar) {
    return createMemo(
        textArea.value,
        toolbar.classList.contains("star"),
        getBucketFromClass(toolbar.className),
        toolbar.classList.contains("private"),
        toolbar.classList.contains("can_edit"),
        toolbar.classList.contains("sync_buckets")
    ).catch(function () {
        alertBox.showAlert("Error creating memo.");
    });
}
function editMemoFromForm(id, textArea, toolbar) {
    return editMemo(
        id,
        textArea.value,
        toolbar.classList.contains("star"),
        getBucketFromClass(toolbar.className),
        toolbar.classList.contains("private"),
        toolbar.classList.contains("can_edit"),
        toolbar.classList.contains("sync_buckets")
    ).catch(function () {
        alertBox.showAlert("Error editing memo.");
    });
}
function createMemo(text, star, bucket, priv, friendsCanEdit, syncBuckets) {
    // Check for shortcut chars
    if (text.length > 3) {
        var origMemoText = text;
        var charsToTrim = 0;
        for (var i = 0; i < 3; ++i) {
            switch (text.slice(i, i + 1)) {
                case "*":
                    star = true;
                    ++charsToTrim;
                    break;
                case "^":
                    priv = true;
                    ++charsToTrim;
                    break;
                case "&":
                    friendsCanEdit = true;
                    ++charsToTrim;
                    break;
                default:
                    i = 3;
                    break;
            }
        }
        text = text.slice(charsToTrim).trim();
        if (text.length === 0) {
            text = origMemoText;
            star = false;
        }
    }

    return frogDB.getTempMemoId().then(function(id) {
        var newMemoData = {};
        newMemoData.memoId = id;
        newMemoData[KEY_MEMO_TEXT] = text;
        newMemoData[KEY_MEMO_PRIVATE] = priv;
        newMemoData[KEY_MEMO_SHARED] = false;
        newMemoData[KEY_MEMO_SYNC_BUCKETS] = syncBuckets;
        newMemoData[KEY_MEMO_FRIENDS_CAN_EDIT] = friendsCanEdit;
        newMemoData[KEY_MEMO_ALARM] = null;
        newMemoData[KEY_MEMO_BUCKET] = bucket;
        newMemoData[KEY_MEMO_EDITED] = false;
        newMemoData[KEY_MEMO_STAR] = star;
        newMemoData[KEY_MEMO_CAN_EDIT] = true;
        newMemoData[KEY_MEMO_AUTHOR_NAME] = "";
        newMemoData[KEY_MEMO_IS_AUTHOR] = true;
        newMemoData.createDate = newMemoData.moveDate = newMemoData.editDate = utcDateString();
        newMemoData[KEY_MEMO_IS_HISTORIC] = false;

        var newMemo = new Memo(newMemoData, false);

        // update UI
        newMemo.insertIntoMemoList();

        // Update data
        return doMemoChange(POST_CREATE_MEMO, id, newMemo, false);
    });
}
function editMemo(id, text, star, bucket, priv, friendsCanEdit, syncBuckets) {
    return frogDB.fetchMemo(id, false).then(function (m) {
        if (m) {
            m.text = text;
            m.star = star;
            m.setBucket(bucket);
            m.priv = priv;
            m.friendsCanEdit = friendsCanEdit;
            m.syncBuckets = syncBuckets;
            m.edited = true;
            m.editDate = utcDateString();
            m._reset();
            return doMemoChange(POST_EDIT_MEMO, m.id, m, false);
        } else {
            console.error("Could not retrieve memo " + id.toString() + " for editing.");
            return Dexie.Promise.reject();
        }
    });
}
