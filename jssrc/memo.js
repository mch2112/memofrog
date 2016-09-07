function Memo(args, synced) {
    /** @type {number} */
    this.id = args.memoId;
    this.text = args[KEY_MEMO_TEXT];
    this.priv = args[KEY_MEMO_PRIVATE];
    this.shared = args[KEY_MEMO_SHARED];
    this.syncBuckets = args[KEY_MEMO_SYNC_BUCKETS];
    this.friendsCanEdit = args[KEY_MEMO_FRIENDS_CAN_EDIT];
    this.alarmDate = args[KEY_MEMO_ALARM];
    this.bucket = args[KEY_MEMO_BUCKET];
    this.edited = args[KEY_MEMO_EDITED];
    this.star = args[KEY_MEMO_STAR];
    this.canEdit = args[KEY_MEMO_CAN_EDIT];
    this.authorName = args[KEY_MEMO_AUTHOR_NAME];
    this.isAuthor = args[KEY_MEMO_IS_AUTHOR];
    this.createDate = args.createDate;
    this.moveDate = args.moveDate;
    this.editDate = args.editDate;
    this.isHistoric = args[KEY_MEMO_IS_HISTORIC];

    if (synced === null)
        this.synced = args[KEY_MEMO_SYNCED];
    else
        this.synced = synced;

    this._reset();
}
Memo.prototype.clone = function () {
    // would be nice to sync the key names
    var args = {};
    args[KEY_MEMO_ID] = this.id;
    args[KEY_MEMO_TEXT] = this.text;
    args[KEY_MEMO_PRIVATE] = this.priv;
    args[KEY_MEMO_SHARED] = this.shared;
    args[KEY_MEMO_SYNC_BUCKETS] = this.syncBuckets;
    args[KEY_MEMO_FRIENDS_CAN_EDIT] = this.friendsCanEdit;
    args[KEY_MEMO_ALARM] = this.alarmDate;
    args[KEY_MEMO_BUCKET] = this.bucket;
    args[KEY_MEMO_EDITED] = this.edited;
    args[KEY_MEMO_STAR] = this.star;
    args[KEY_MEMO_CAN_EDIT] = this.canEdit;
    args[KEY_MEMO_AUTHOR_NAME] = this.authorName;
    args[KEY_MEMO_IS_AUTHOR] = this.isAuthor;
    args[KEY_MEMO_TIMESTAMP] = this.timeStamp;
    args.createDate = this.createDate;
    args.editDate = this.editDate;
    args.moveDate = this.moveDate;
    args[KEY_MEMO_IS_HISTORIC] = this.isHistoric;
    args[KEY_MEMO_SYNCED] = this.synced;
    return new Memo(args, false);
};
Memo.prototype.setBucket = function (bucket) {
    this.bucket = bucket;
    this.moveDate = utcDateString();
};
Memo.prototype._reset = function () {

    if (this.bucket === BUCKET_JOURNAL)
        this.timeStamp = this.createDate;
    else if (this.bucket < BUCKET_DONE)
        this.timeStamp = this.editDate;
    else
        this.timeStamp = this.moveDate;

    if (!this.star || this.bucket === BUCKET_DONE || this.bucket === BUCKET_TRASH)
        this.sortKey = "100|" + this.editDate;
    else
        switch (this.bucket) {
            case BUCKET_HOT_LIST:
                this.sortKey = "400|" + this.editDate;
                break;
            case BUCKET_B_LIST:
                this.sortKey = "300|" + this.editDate;
                break;
            default:
                this.sortKey = "200|" + this.editDate;
        }
};
Memo.prototype.alarmIsSet = function () {
    return !!this.alarmDate;
};

/** @param {Memo} memo
 *  @param {number} memoRenderType
 *  @return {Element}
 */
function renderMemo(memo, memoRenderType) {

    var memoIdString = memo.id.toString();
    var bucketStr = memo.bucket.toString();

    var className = "memo btn_container bucket" + bucketStr;

    if (!memo.isHistoric) {
        if (memo.priv) {
            className += " private";
        } else {
            if (memo.syncBuckets)
                className += " sync_buckets";
            if (memo.isAuthor && memo.shared)
                className += " shared";
        }
        if (memo.star)
            className += " star";
        if (memo.edited)
            className += " edited";
        if (!memo.synced)
            className += " not_synced";
        if (memo.canEdit)
            className += " can_edit";
        if (memo.id < 0)
            className += " temp";
        if (memo.alarmDate)
            className += " alarm";
    } else {
        className += " historic";
    }
    //noinspection HtmlUnknownTarget
    var memoText = memo.text.replace(/\r?\n/g, "<br>")
                            .replace(tagRE, TAG_REPLACE_PATTERN)
                            .replace(screenNameRE, SCREEN_NAME_REPLACE_PATTERN)
                            .replace(urlRE, "$1<a class='memo_text_url external' href=\"$2\" target=\"_blank\">$2</a>")
                            .replace(moreRE, "</div><div class=\"more_text\" ><span class=\"get_more\" onclick=\"this.parentElement.classList.add('expanded');expandHiddenText(this, true);\">More</span><span class=\"get_less\" onclick=\"this.parentElement.classList.remove('expanded');expandHiddenText(this, false);\">Less</span><div class=\"expandable_text\">$1</div>");

    var tools;
    if (memo.isHistoric || memoRenderType === MEMO_RENDER_TYPE_NO_TOOLS)
        tools = "";
    else
        tools = toolTemplate;

    var authorLink;
    if (memo.isAuthor)
        authorLink = "";
    else
        authorLink = div("memo_author", a("screen_name_link", "@" + memo.authorName, "filterByScreenName('" + memo.authorName + "', false);"));

    var memoDiv = document.createElement("div");
    memoDiv.id = "memo" + memoIdString;
    memoDiv.innerHTML =
        div("memo_header",
            divTitle("memo_bucket_icon img_bucket" + bucketStr, bucketText[memo.bucket], "") +
            authorLink +
            indicatorsTemplate) +
        div("memo_text", memoText) +
        tools +
        div("timestamp", replaceTimestamp(memo.timeStamp, true));

    memoDiv.className = className;
    return memoDiv;
}

var indicatorsTemplate =
    divTitle("memo_star_icon img_star_indicator indicator", "Starred", "") +
    divTitle("memo_private_icon img_private_indicator indicator", "Private", "") +
    divTitle("memo_shared_icon img_shared_indicator indicator", "Shared", "") +
    divTitle("memo_alarm_icon img_alarm_indicator indicator", "Alarm Set", "") +
    divTitle("memo_sync_buckets_icon img_bucket_indicator indicator", "Buckets Synced", "") +
    divTitle("memo_edited_icon img_edited_indicator indicator", "Edited", "") +
    divTitle("memo_sync_icon img_sync_indicator indicator", "Not Synced with Server", "");

var toolTemplate = div("memo_tools",
    wrap("btn_img img_star", "Star", "starMemo(getMemoId(this));") +
    wrap("btn_img img_alarm", "Set Alarm", "navigate({'contentReq':" + CONTENT_KEY_ALARM + ",'" + KEY_TARGET_MEMO + "':getMemoId(this)});") +
    wrap("btn_img img_edit", "Edit Memo", "navigate({'contentReq':" + CONTENT_KEY_EDIT_MEMO + ",'" + KEY_TARGET_MEMO + "':getMemoId(this)});") +
    wrap("btn_img img_details", "More Details", "navMemo(getMemoId(this));") +
    div("spacer", "") +
    wrap("btn_img img_bucket110", "Move to Hot List", "setBucket(getMemoId(this), 110);") +
    wrap("btn_img img_bucket120", "Move to B List", "setBucket(getMemoId(this), 120);") +
    div("img_separator", "") +
    wrap("btn_img img_bucket220", "Move to Reference", "setBucket(getMemoId(this), 220);") +
    wrap("btn_img img_bucket210", "Move to Journal", "setBucket(getMemoId(this), 210);") +
    div("img_separator", "") +
    wrap("btn_img img_bucket250", "Move to Done", "setBucket(getMemoId(this), 250);") +
    wrap("btn_img img_bucket310", "Move to Trash", "setBucket(getMemoId(this), 310);")
);

function replaceMemoDiv(oldMemoId, newMemo) {
    var divId = "memo" + oldMemoId.toString();
    var newMemoDiv = renderMemo(newMemo, MEMO_RENDER_TYPE_NORMAL);
    var oldMemoDiv = document.getElementById(divId);

    if (nav.targetMemo === oldMemoId)
        nav.targetMemo = newMemo.id;

    if (oldMemoDiv === null) {
        if (nav.memoVisible(newMemo))
            newMemo.insertIntoMemoList();
    } else {
        oldMemoDiv.innerHTML = newMemoDiv.innerHTML;
        oldMemoDiv.className = newMemoDiv.className;
        oldMemoDiv.id = newMemoDiv.id;
        if (oldMemoId !== newMemo.id)
            console.log("Replaced memo " + oldMemoId.toString() + " with new memo id " + newMemo.id.toString() + " in UI.");
        else
            console.log("Updated memo " + oldMemoId.toString() + " in UI.");
    }
}
Memo.sortComparator = function (m1, m2) {
    return m1.sortKey > m2.sortKey ? -1 : 1;
};
Memo.sortComparatorOldestFirst = function (m1, m2) {
    return m1.timeStamp > m2.timeStamp ? 1 : -1;
};
Memo.sortComparatorJournal = function (m1, m2) {
    return m1.createDate > m2.createDate ? -1 : 1;
};
Memo.prototype.fitsInBucket = function (filterObj) {
    return (filterObj.bucket === this.bucket) ||
           (filterObj.bucket === BUCKET_EVERYTHING &&
                (this.bucket <= BUCKET_DONE || (filterObj.specialSearch === SPECIAL_SEARCH_ALARM) || filterObj.screenNameTo)) ||
           (filterObj.bucket === BUCKET_ALL_ACTIVE && this.bucket <= BUCKET_B_LIST);
};
Memo.prototype.isCompatible = function (filterObj) {

    var that = this;
    var ok = this.fitsInBucket(filterObj);

    if (ok && filterObj.screenName) {
        if (filterObj.screenNameTo) {
            // Best we can do??
            ok &= this.shared;
            ok &= this.isAuthor;
        } else {
            ok &= this.authorName === filterObj.screenName;
        }
    }
    if (ok && filterObj.filterTags) {
        var tags = filterObj.filterTags.split("+");
        switch (filterObj.filterTagsOp) {
            case FILTER_TAGS_OP_ANY:
                ok = false;
                tags.forEach(function (tag) {
                    if (that.text.indexOf("#" + tag) >= 0)
                        ok = true;
                });
                break;
            default:
                ok = true;
                tags.forEach(function (tag) {
                    if (that.text.indexOf("#" + tag) < 0)
                        ok = false;
                });
                break;
        }
        if (!ok)
            ok = false;
    }
    if (ok && filterObj.filterText) {
        ok = true;
        var chunks = filterObj.filterText.split(" ");
        chunks.forEach(function (chunk) {
            if (chunk[0] === "-") {
                if (that.text.indexOf(chunk.slice(1)) >= 0)
                    ok = false;
            } else {
                if (that.text.indexOf(chunk) < 0)
                    ok = false;
            }
        });
        if (!ok)
            ok = false;
    }
    if (ok && filterObj.specialSearch) {
        switch (filterObj.specialSearch) {
            case SPECIAL_SEARCH_ALARM:
                ok &= this.alarmIsSet();
                break;
            case SPECIAL_SEARCH_BY_ME:
                ok &= this.isAuthor;
                break;
            case SPECIAL_SEARCH_EDITED:
                ok &= this.edited;
                break;
            case SPECIAL_SEARCH_HIDDEN:
                ok &= this.bucket === BUCKET_HIDDEN;
                break;
            case SPECIAL_SEARCH_NOT_BY_ME:
                ok &= !this.isAuthor;
                break;
            case SPECIAL_SEARCH_OLD:
                ok &= this.isOld();
                break;
            case SPECIAL_SEARCH_PRIVATE:
                ok &= this.priv;
                break;
            case SPECIAL_SEARCH_OLDEST_FIRST:
                break;
            case SPECIAL_SEARCH_SHARED:
                ok &= this.shared;
                break;
            case SPECIAL_SEARCH_STARS:
                ok &= this.star;
                break;
            case SPECIAL_SEARCH_UNSTARRED:
                ok &= !this.star;
                break;
        }
    }
    return ok;
};
Memo.prototype.insertIntoMemoList = function () {
    if (nav.contentKey === CONTENT_KEY_HOME) {
        var memoList = document.getElementById('memo_list');
        if (memoList !== null) {
            var memoDiv = renderMemo(this, MEMO_RENDER_TYPE_NORMAL);
            memoList.insertBefore(memoDiv, memoList.firstChild.nextSibling);
        }
    }
};
Memo.oldThreshold = null;
Memo.prototype.isOld = function () {
    if (Memo.oldThreshold === null) {
        Memo.oldThreshold = moment().subtract(30, "days").format("YYYY-MM-DD");
        new Defer(24 * 3600 * 1000, function () {
            Memo.oldThreshold = null;
        });
    }
    return this.timeStamp < Memo.oldThreshold;
};
function markMemoSynced(memoId) {
    var div = document.getElementById("memo" + memoId.toString());
    if (div !== null)
        div.classList.remove("not_synced");
}
//noinspection JSUnusedGlobalSymbols
function getMemoId(elem) {
    if (elem) {
        if (elem.classList.contains("memo"))
            return parseInt(elem.id.slice(4));
        else
            return getMemoId(elem.parentElement);
    } else {
        return 0;
    }
}