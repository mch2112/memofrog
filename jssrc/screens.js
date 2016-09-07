var PLACEHOLDER_MEMO_DETAILS = 40100;
var PLACEHOLDER_SUGGESTED_TAGS = 40200;

var ERROR_NONE = 0;
var ERROR_UNKNOWN = 5;
var ERROR_CONTENT_NOT_FOUND = 1005;
var ERROR_NEED_LOGIN = 6010;

function handleRedirect(originalContentReq, content) {
    switch (content.error) {
        case ERROR_NONE:
            return Dexie.Promise.resolve(content);
        case ERROR_NEED_LOGIN:
            return Dexie.Promise.reject(CONTENT_KEY_LOGIN);
        case ERROR_CONTENT_NOT_FOUND:
            if (!content.isLocal) {
                var newContentReq;
                if (originalContentReq !== content.contentReq) {
                    // didn't find on server but should be available locally. Capture any parameters sent
                    nav.update(content);
                    newContentReq = content.contentReq;
                } else if (localContentRequiresLogin(originalContentReq)) {
                    newContentReq = CONTENT_KEY_LOGIN;
                } else {
                    nav.errorCode = ERROR_RENDER_ERROR;
                    nav.errorReq = originalContentReq;
                    newContentReq = CONTENT_KEY_ERROR;
                }
                return Dexie.Promise.reject(newContentReq);
            }
            break;
    }
    if ("error" in content)
        nav.errorCode = content.error;
    else
        nav.errorCode = ERROR_UNKNOWN;
    nav.errorReq = content.contentReq;
    return Dexie.Promise.reject(CONTENT_KEY_ERROR);
}
function localContentRequiresLogin(contentKey) {
    switch (contentKey) {
        case CONTENT_KEY_HELP:
        case CONTENT_KEY_LOGIN:
        case CONTENT_KEY_POST_LOGIN:
        case CONTENT_KEY_REGISTER_USER:
        case CONTENT_KEY_FORGOT_PASSWORD:
        case CONTENT_KEY_ERROR:
        case CONTENT_KEY_NEED_SERVER:
        case CONTENT_KEY_LOGOUT:
            return false;
        default:
            return true;
    }
}
function generateContentFromData(content) {
    var contentKey = content.contentKey;
    if (content.contentData) {
        var data = content.contentData.data;
        switch (contentKey) {
            case CONTENT_KEY_FRIENDS:
                var friends = data.friends.sort(function (a, b) {
                    return b.memoCount - a.memoCount;
                });
                content[KEY_CONTENT] =
                    div("submenu",
                        a("link_button", "New Share", "navigateTo(" + CONTENT_KEY_NEW_SHARE.toString() + ");")) +
                    div("heading", "Friends");
                if (friends.length)
                    content[KEY_CONTENT] +=
                        div("big_list", friends.map(function (f) {
                            return a("friend big_list_item hoverable",
                                div("friend big_list_item hoverable",
                                    span("screen_name_link",
                                        "@" + f.screenName) +
                                    "<br>" +
                                    span("friend_details",
                                        f.realName + "&nbsp;(" + _memoCountString(f.memoCount) + " shared)")),
                                "navigateToFriend('" + f.screenName + "');");
                        }).join(""));
                else
                    content[KEY_CONTENT] +=
                        div("information", "Friends appear on this list when you share memos with them, or when they share with you. Click New Share to start.");
                return Dexie.Promise.resolve(content);
            case CONTENT_KEY_FRIEND:
                var friend = data.friend;
                nav.friend = friend.screenName;
                content[KEY_CONTENT] =
                    div("submenu",
                        a("link_button", "&lt; Friends", "navigateTo(" + CONTENT_KEY_FRIENDS.toString() + ");") +
                        div("spacer", "") +
                        a("link_button", "New Share", "nav.friend=\"" + friend.screenName + "\");navigateTo(" + CONTENT_KEY_NEW_SHARE.toString() + ";")) +
                    div("heading", "@" + friend.screenName) +
                    div("subheading", friend.realName) +
                    div('instructions',
                        "You are sharing " +
                        a("link_button", _memoCountString(friend.numMemosOut), "filterByScreenName('" + friend.screenName + "', true);") +
                            " with " +
                            span("screen_name", "@" + friend.screenName) +
                            ", who is sharing " +
                            a("link_button", _memoCountString(friend.numMemosIn), "filterByScreenName('" + friend.screenName + "', false);") +
                            " with you.");
                if (friend.shares.length)
                    content[KEY_CONTENT] +=
                        div("big_list share_list", friend.shares.map(function (s) {
                            return a("",
                                div("big_list_item hoverable",
                                    div("share_tags", s.tags) +
                                    div("share_graphic",
                                        span("inline_icon_large img_" + (s.editableIn ? "edit" : "blank"), "") +
                                        div("share_icon " + (s.inAvailable ? (s.sharingIn ? "share_in" : "share_in_disabled") : "share_blank"), "") +
                                        div("share_icon " + (s.sharingOut ? "share_out" : "share_out_disabled"), "") +
                                        span("inline_icon_large img_" + (s.editableOut ? "edit" : "blank"), "") +
                                        div("share_description", s.description))),
                                "nav.shareId=" + s.id.toString() + ";navigateTo(" + CONTENT_KEY_EDIT_SHARE + ");");
                        }).join(""));
                else
                    content[KEY_CONTENT] += div("", "No shares found.");
                return Dexie.Promise.resolve(content);
            case CONTENT_KEY_TAGS:
                var filterBtn;
                var linkProp;
                if (data.tags.some(function (t) {
                        return t.other_count > 0;
                    })) {
                    var showCaption;
                    switch (nav.tagsView) {
                        case TAGS_SHOW_ALL:
                            showCaption = "Show All";
                            linkProp = "";
                            break;
                        case TAGS_SHOW_OWN:
                            showCaption = "Show Mine Only";
                            linkProp = ",specialSearch:" + SPECIAL_SEARCH_BY_ME.toString();
                            break;
                        case TAGS_SHOW_FRIENDS:
                        /* falls through */
                        default:
                            showCaption = "Show Friends Only";
                            linkProp = "specialSearch:" + SPECIAL_SEARCH_NOT_BY_ME.toString();
                            break;
                    }
                    filterBtn =
                        div("spacer", "") +
                        a("link_button", showCaption, "cycleTagsView();");

                } else {
                    nav.tagsView = TAGS_SHOW_ALL;
                    linkProp = "";
                    filterBtn = "";
                }
                var tags = data.tags.map(function (t) {
                    return {
                        tag: t.tag,
                        count: nav.tagsView === TAGS_SHOW_ALL ? t.own_count + t.other_count :
                            nav.tagsView === TAGS_SHOW_OWN ? t.own_count :
                                t.other_count
                    };
                }).filter(function (t) {
                    return t.count > 0;
                });

                tags = tags.sort(
                    nav.tagsAlphabetic ?
                        function (a, b) {
                            return a.tag.localeCompare(b.tag);
                        } :
                        function (a, b) {
                            return b.count - a.count;
                        }
                );
                content[KEY_CONTENT] =
                    div("submenu",
                        a("link_button" + (nav.tagsAlphabetic ? "" : " not_selected"), "Alphabetic", "nav.tagsAlphabetic=true; navigateTo(" + CONTENT_KEY_TAGS + ");") +
                        "&nbsp;&nbsp;" +
                        a("link_button" + (nav.tagsAlphabetic ? " not_selected" : ""), "Most Used", "nav.tagsAlphabetic=false; navigateTo(" + CONTENT_KEY_TAGS + ");") +
                        filterBtn) +
                    div("heading", "Tags") +
                    (data.resultType === QUERY_RESULT_LOCAL_FALLBACK ? div("advisory warning", "Could not connect to Memofrog server. Results may be incomplete. " + a("link_button", "Try Again.", "navigate(null);")) : "") +
                    div("big_list columns", tags.map(function (tag) {
                            return div("big_list_item tag",
                                a("",
                                    div("",
                                        span("hashtag", "#" + tag.tag) +
                                        "&nbsp;" +
                                        span("tag_list_count", "(" + tag.count.toString() + ")")
                                    ),
                                    "showTagMemos('" + tag.tag.toString() + "');") +
                                a("",
                                    span("inline_icon_large img_details", ""),
                                    "nav.clearFilters(BUCKET_EVERYTHING);navigate({contentReq:" + CONTENT_KEY_TAG.toString() + ",tag:'" + tag.tag + "'});"));
                        }).join("")
                    );
                return Dexie.Promise.resolve(content);
            case CONTENT_KEY_ACCOUNT_DETAILS:
                content[KEY_CONTENT] =
                    div("submenu",
                        a("link_button", "&lt; Back", "navigateTo(" + CONTENT_KEY_ACCOUNT.toString() + ");")) +
                    div("heading", "Account Details") +
                    form("standard_form",
                        fieldset(
                            textInput("acctScreenName", "text", "acctScreenName", "Screen Name (lower case, no spaces", "Your screen name", data.acctScreenName, true, "off", "[a-zA-Z0-9]+") +
                            divId("key_validation_target_screen_name", "validation", "") +
                            textInput("acctRealName", "text", "acctRealName", "Your Name", "Your name", data.acctRealName, true, "off", "") +
                            divId("key_validation_target_real_name", "validation", "") +
                            hiddenInput("contentReq", CONTENT_KEY_POST_ACCOUNT_DETAILS.toString())) +
                        fieldset(
                            div("button_row",
                                a("standard_button cancel", "Cancel", "navigateTo(" + CONTENT_KEY_ACCOUNT.toString() + ");") +
                                submitButton("key_submit_button", "standard_button", "Update Account")
                            )));
                content[KEY_SCRIPT] = function () {
                    updateSubmitButton(false);
                    initValidation(VALIDATION_TYPE_ACCOUNT_DETAILS, ["acctScreenName", "acctRealName"], function () {
                        return document.getElementById("acctScreenName").value.trim() !== data.acctScreenName ||
                            document.getElementById("acctRealName").value.trim() !== data.acctRealName;
                    });
                };
                return Dexie.Promise.resolve(content);
            case CONTENT_KEY_CHANGE_EMAIL:
                content[KEY_CONTENT] =
                    div("submenu",
                        a("link_button", "&lt; Back", "navigateTo(" + CONTENT_KEY_ACCOUNT.toString() + ");")) +
                    div("heading", "Change Email Address") +
                    form("standard_form",
                        fieldset(
                            textInput("acctEmail", "text", "acctEmail", "Email Address", "Your email address", data.acctEmail, true, "off", "") +
                            divId("key_validation_target_email", "validation", "") +
                            hiddenInput("contentReq", CONTENT_KEY_POST_CHANGE_EMAIL.toString())) +
                        fieldset(
                            div("button_row",
                                a("standard_button cancel", "Cancel", "navigateTo(" + CONTENT_KEY_ACCOUNT.toString() + ");") +
                                submitButton("key_submit_button", "standard_button", "Update Email")
                            )));
                content[KEY_SCRIPT] = function () {
                    updateSubmitButton(false);
                    initValidation(VALIDATION_TYPE_CHANGE_EMAIL, ["acctEmail"], function () {
                        return document.getElementById("acctEmail").value.trim() !== data.acctEmail;
                    });
                };
                return Dexie.Promise.resolve(content);
            default:
                return Dexie.Promise.reject(content);
        }
    }
    else {
        return Dexie.Promise.resolve(content);
    }
}
function generateLocalContent(args) {

    var contentKey = args[KEY_CONTENT_REQ];

    //if (!session.isLoggedIn() && contentKey !== CONTENT_KEY_LOGIN)
    //    return Dexie.Promise.reject();

    var content;
    var placeholder;
    switch (contentKey) {
        case CONTENT_KEY_HOME:
            content = _getLocalContentDefaults(contentKey);
            content[KEY_CONTENT] =
                divId("memo_panel", "memo_panel",
                    div("new_memo_panel",
                        divId("quick_entry_container", "quick_entry_container",
                            textArea({
                                id: "quick_entry_textarea",
                                class: "quick_entry_textarea",
                                name: "memoText",
                                rows: "4",
                                maxLength: "4096"
                            }, "") +
                            divId("quick_entry_toolbar", "submenu right_align",
                                a("link_button cancel", "Cancel", "activateQuickEntry(false, true);") +
                                aId("quick_entry_submit", "link_button submit disabled", "Post Memo", "createMemoFromQE();"))) +
                        divId("memo_list", "memo_list",
                            divId("memo_advisory", "advisory", ""))));
            content[KEY_SCRIPT] = function () {
                loadMemos(false);
            };
            return Dexie.Promise.resolve(content);
        case CONTENT_KEY_LOGIN:

            var emailPattern = "(?!(^[.-].*|[^@]*[.-]@|.*\\.{2,}.*)|^.{254}.)([a-zA-Z0-9!#$%%&'*+\\/=?^_`{|}~.-]+@)(?!-.*|.*-\\.)([a-zA-Z0-9-]{1,63}\\.)+[a-zA-Z]{2,15}"; // note escaped '%' for sprintf
            var screenNamePattern = "@?[a-zA-Z0-9_]+";
            var pattern = "(" + emailPattern + "|" + screenNamePattern + ")";

            content = _getLocalContentDefaults(contentKey);
            content[KEY_TIPS_SUPPRESSED] = true;
            content.needsValidationSuppressed = true;
            content[KEY_CONTENT] =
                div("submenu",
                    a("link_button", "&lt; Back", "logout();") +
                    div("spacer", "") +
                    a("link_button", "Forgot Password?", "navigateTo(" + CONTENT_KEY_FORGOT_PASSWORD.toString() + ");")) +
                div("heading", "Sign In") +
                form("standard_form",
                    fieldset(
                        textInput("login_screen_name_or_email", "text", "login_screen_name_or_email", "Screen Name or Email", "Your screen name or email address", "", true, true, pattern) +
                        textInput("login_password", "password", "login_password", "Password", "Your password", "", false, false, "") +
                        checkbox("login_remember_me", "login_remember_me", "Stay Signed In", true) +
                        hiddenInput("contentReq", CONTENT_KEY_POST_LOGIN.toString())) +
                    fieldset(
                        div("button_row",
                            a("standard_button cancel", "Cancel", "logout();") +
                            submitButton("key_submit_button", "standard_button", "Sign In")
                        ))) +
                p("", "Don&apos;t have an account? " +
                    a("link_button", "Sign up now!", "navigateTo(" + CONTENT_KEY_REGISTER_USER.toString() + ");"));
            return Dexie.Promise.resolve(content);
        case CONTENT_KEY_NEW_MEMO:
            placeholder = new Placeholder(PLACEHOLDER_SUGGESTED_TAGS, "", true);
            placeholder.setPostProcessFx(suggestedTagsPostProcess);
            content = _getLocalContentDefaults(contentKey);
            content[KEY_CONTENT] =
                div("heading", "New Memo") +
                divId("new_memo_form", "form",
                    textArea({
                        id: "edit_memo_text_area",
                        class: "memo_text_area",
                        placeholder: "Enter your memo. Add #tags anywhere to make it easy to find and share.",
                        maxlength: "4096",
                        autofocus: "autofocus",
                        required: "required"
                    }, "") +
                    div("button_bar btn_container bucket110",
                        div("button_subbar",
                            wrap("btn_img img_star", "Star", "", "") +
                            div("img_separator", "") +
                            wrap("btn_img img_private", "Keep Private", "", "") +
                            wrap("btn_img img_can_edit", "Friends Can Edit", "", "") +
                            wrap("btn_img img_sync_buckets", "Sync Buckets among Friends", "", "") +
                            div("img_separator", "") +
                            wrap("btn_img img_bucket110", "Hot List", "", "") +
                            wrap("btn_img img_bucket120", "B List", "", "") +
                            wrap("btn_img img_bucket220", "Reference", "", "") +
                            wrap("btn_img img_bucket210", "Journal", "", "")) +
                        div("button_subbar",
                            div("text_button cancel enabled", "Cancel", "navigateHome();") +
                            div("text_button submit", "Post Memo", ""))),
                    "") +
                placeholder.getHtml();

            content[KEY_SCRIPT] = function () {
                var form = document.getElementById("new_memo_form");
                var textArea = domManager.getByClass(form, "memo_text_area");
                var btnSubmit = domManager.getByClass(form, "submit");
                var toolbar = domManager.getByClass(form, "button_bar");
                var btnStar = domManager.getByClass(toolbar, "img_star");
                var btnPrivate = domManager.getByClass(toolbar, "img_private");
                var btnFriendsCanEdit = domManager.getByClass(toolbar, "img_can_edit");
                var btnSyncBuckets = domManager.getByClass(toolbar, "img_sync_buckets");

                domManager.getByClass(toolbar, "img_bucket110").onclick = function () {
                    updateBucketClass(toolbar, BUCKET_HOT_LIST);
                };
                domManager.getByClass(toolbar, "img_bucket120").onclick = function () {
                    updateBucketClass(toolbar, BUCKET_B_LIST);
                };
                domManager.getByClass(toolbar, "img_bucket220").onclick = function () {
                    updateBucketClass(toolbar, BUCKET_REFERENCE);
                };
                domManager.getByClass(toolbar, "img_bucket210").onclick = function () {
                    updateBucketClass(toolbar, BUCKET_JOURNAL);
                };

                var submitFn = function () {
                    if (btnSubmit.classList.contains("enabled")) {
                        btnSubmit.classList.remove("enabled");
                        createMemoFromForm(textArea, toolbar).then(function () {
                            nav.contentReq = CONTENT_KEY_HOME;
                            nav.clearFilters(getBucketFromClass(toolbar.className));
                            return navigate();
                        });
                    }
                };

                textArea.onkeyup = function (e) {
                    if ((e.keyCode === 13 || e.keyCode === 10) && e.ctrlKey) {
                        submitFn(e);
                    }
                    if (e.keyCode === 27)
                        navigateHome();
                    return false;
                };
                textArea.oninput = function () {
                    if (textArea.value.length)
                        btnSubmit.classList.add("enabled");
                    else
                        btnSubmit.classList.remove("enabled");
                };
                btnSubmit.onclick = submitFn;
                btnStar.onclick = function () {
                    toolbar.classList.toggle("star");
                };
                btnPrivate.onclick = function () {
                    toolbar.classList.toggle("private");
                    if (toolbar.classList.contains("private")) {
                        toolbar.classList.remove("sync_buckets");
                        toolbar.classList.remove("can_edit");
                    }
                };
                btnFriendsCanEdit.onclick = function () {
                    toolbar.classList.toggle("can_edit");
                    if (toolbar.classList.contains("can_edit")) {
                        toolbar.classList.remove("private");
                    }
                };
                btnSyncBuckets.onclick = function () {
                    toolbar.classList.toggle("sync_buckets");
                    if (toolbar.classList.contains("sync_buckets")) {
                        toolbar.classList.remove("private");
                    }
                };
                textArea.focus();
                return placeholder.execute();
            };
            return Dexie.Promise.resolve(content);
        case CONTENT_KEY_EDIT_MEMO:
            return frogDB.fetchMemo(args[KEY_TARGET_MEMO], true).then(function (memo) {
                nav.targetMemo = memo.id;
                placeholder = new Placeholder(PLACEHOLDER_SUGGESTED_TAGS, "", true);
                placeholder.setPostProcessFx(suggestedTagsPostProcess);
                content = _getLocalContentDefaults(contentKey);
                content[KEY_CONTENT] =
                    div("heading", "Edit Memo") +
                    divId("edit_memo_form", "form",
                        textArea({
                            id: "edit_memo_text_area",
                            class: "memo_text_area",
                            placeholder: "Enter your memo.",
                            maxlength: "4096",
                            autofocus: "autofocus",
                            required: "required"
                        }, memo.text) +
                        div("button_bar btn_container",
                            div("button_subbar",
                                wrap("btn_img img_star", "Star", "", "") +
                                div("img_separator", "") +
                                wrap("btn_img img_private", "Keep Private", "", "") +
                                wrap("btn_img img_can_edit", "Friends Can Edit", "", "") +
                                wrap("btn_img img_sync_buckets", "Sync Buckets among Friends", "", "") +
                                div("img_separator", "") +
                                wrap("btn_img img_bucket110", "Hot List", "", "") +
                                wrap("btn_img img_bucket120", "B List", "", "") +
                                wrap("btn_img img_bucket220", "Reference", "", "") +
                                wrap("btn_img img_bucket210", "Journal", "", "") +
                                (memo.bucket === BUCKET_HIDDEN ? wrap("btn_img img_bucket350", "Hidden", "", "") : "") +
                                wrap("btn_img img_bucket250", "Done", "", "") +
                                wrap("btn_img img_bucket310", "Trash", "", "")) +
                            div("button_subbar",
                                div("text_button cancel enabled", "Cancel", "navigateHome();") +
                                div("text_button submit", "Save Changes", ""))),
                        "") +
                    placeholder.getHtml();

                content[KEY_SCRIPT] = function () {
                    var form = document.getElementById("edit_memo_form");
                    var textArea = domManager.getByClass(form, "memo_text_area");
                    var btnSubmit = domManager.getByClass(form, "submit");
                    var toolbar = domManager.getByClass(form, "button_bar");
                    var btnStar = domManager.getByClass(toolbar, "img_star");
                    var btnPrivate = domManager.getByClass(toolbar, "img_private");
                    var btnFriendsCanEdit = domManager.getByClass(toolbar, "img_can_edit");
                    var btnSyncBuckets = domManager.getByClass(toolbar, "img_sync_buckets");

                    toolbar.classList.add("bucket" + memo.bucket.toString());
                    if (memo.star)
                        toolbar.classList.add("star");
                    if (memo.priv)
                        toolbar.classList.add("private");
                    if (memo.friendsCanEdit)
                        toolbar.classList.add("can_edit");
                    if (memo.syncBuckets)
                        toolbar.classList.add("sync_buckets");

                    domManager.getByClass(toolbar, "img_bucket110").onclick = function () {
                        updateBucketClass(toolbar, BUCKET_HOT_LIST);
                    };
                    domManager.getByClass(toolbar, "img_bucket120").onclick = function () {
                        updateBucketClass(toolbar, BUCKET_B_LIST);
                    };
                    domManager.getByClass(toolbar, "img_bucket220").onclick = function () {
                        updateBucketClass(toolbar, BUCKET_REFERENCE);
                    };
                    domManager.getByClass(toolbar, "img_bucket210").onclick = function () {
                        updateBucketClass(toolbar, BUCKET_JOURNAL);
                    };
                    domManager.getByClass(toolbar, "img_bucket250").onclick = function () {
                        updateBucketClass(toolbar, BUCKET_DONE);
                    };
                    domManager.getByClass(toolbar, "img_bucket310").onclick = function () {
                        updateBucketClass(toolbar, BUCKET_TRASH);
                    };
                    if (memo.bucket === BUCKET_HIDDEN)
                        domManager.getByClass(toolbar, "img_bucket350").onclick = function () {
                            updateBucketClass(toolbar, BUCKET_HIDDEN);
                        };

                    var submitFn = function () {
                        if (btnSubmit.classList.contains("enabled")) {
                            btnSubmit.classList.remove("enabled");
                            editMemoFromForm(memo.id, textArea, toolbar).then(function () {
                                nav.contentReq = CONTENT_KEY_HOME;
                                nav.clearFilters(getBucketFromClass(toolbar.className));
                                return navigate();
                            });
                        }
                    };

                    textArea.onkeyup = function (e) {
                        if ((e.keyCode === 13 || e.keyCode === 10) && e.ctrlKey) {
                            submitFn(e);
                        }
                        if (e.keyCode === 27)
                            navigateHome();
                        return false;
                    };
                    textArea.oninput = function () {
                        if (textArea.value.length && textArea.value !== memo.text)
                            btnSubmit.classList.add("enabled");
                        else
                            btnSubmit.classList.remove("enabled");
                    };
                    btnSubmit.onclick = submitFn;
                    btnStar.onclick = function () {
                        toolbar.classList.toggle("star");
                    };
                    btnPrivate.onclick = function () {
                        toolbar.classList.toggle("private");
                        if (toolbar.classList.contains("private")) {
                            toolbar.classList.remove("sync_buckets");
                            toolbar.classList.remove("can_edit");
                        }
                    };
                    btnFriendsCanEdit.onclick = function () {
                        toolbar.classList.toggle("can_edit");
                        if (toolbar.classList.contains("can_edit")) {
                            toolbar.classList.remove("private");
                        }
                    };
                    btnSyncBuckets.onclick = function () {
                        toolbar.classList.toggle("sync_buckets");
                        if (toolbar.classList.contains("sync_buckets")) {
                            toolbar.classList.remove("private");
                        }
                    };
                    textArea.focus();
                    return placeholder.execute();
                };
                return Dexie.Promise.resolve(content);
            });
        case CONTENT_KEY_ALARM:
            return frogDB.fetchMemo(args[KEY_TARGET_MEMO], true).then(function (memo) {
                content = _getLocalContentDefaults(contentKey);
                var dateFormatString = isMobile ? "ddd, MMM Do YYYY" : "dddd, MMMM Do YYYY";
                var defaultDate = memo.alarmIsSet() ? moment(memo.alarmDate).format("YYYY-MM-DD") : moment().add(1, "days").format("YYYY-MM-DD");
                content[KEY_CONTENT] =
                    div("submenu",
                        a("link_button",
                            "&lt; Home",
                            "navigateHome();return false;") +
                        (memo.alarmIsSet() ? div("spacer", "") + a("link_button img_bucket410", "Remove Alarm", "setAlarmDate(" + memo.id.toString() + ", '', false);") : "")) +
                    div("heading", "Memo Alarm") +
                    div("instructions", "On the alarm date, memos will be <span class=\"inline_icon img_star_on\"></span>Starred and sent to the <span class=\"inline_icon img_bucket110\"></span>Hotlist. If enabled in your Account settings, you will receive a reminder email.") +
                    renderMemo(memo, MEMO_RENDER_TYPE_NO_TOOLS).outerHTML +
                    (memo.alarmDate ? div("advisory info", "The alarm is scheduled for " + moment(memo.alarmDate).format(dateFormatString) + ".") : "") +
                    div("subheading", "Set alarm to:") +
                    div("big_list",
                        div("big_list_item hoverable date_select",
                            div("date_description", "Tomorrow") +
                            div("date_value", moment().add(1, "days").format(dateFormatString)),
                            "setAlarmDate(" + memo.id.toString() + ",'" + moment().add(1, "days").format("YYYY-MM-DD") + "', false);") +
                        div("big_list_item hoverable date_select",
                            div("date_description", "Two Days") +
                            div("date_value", moment().add(2, "days").format(dateFormatString)),
                            "setAlarmDate(" + memo.id.toString() + ",'" + moment().add(2, "days").format("YYYY-MM-DD") + "', false);") +
                        div("big_list_item hoverable date_select",
                            div("date_description", "One Week") +
                            div("date_value", moment().add(1, "weeks").format(dateFormatString)),
                            "setAlarmDate(" + memo.id.toString() + ",'" + moment().add(1, "weeks").format("YYYY-MM-DD") + "', false);") +
                        div("big_list_item hoverable date_select",
                            div("date_description", "Two Weeks") +
                            div("date_value", moment().add(2, "weeks").format(dateFormatString)),
                            "setAlarmDate(" + memo.id.toString() + ",'" + moment().add(2, "weeks").format("YYYY-MM-DD") + "', false);") +
                        div("big_list_item hoverable date_select",
                            div("date_description", "One Month") +
                            div("date_value", moment().add(1, "months").format(dateFormatString)),
                            "setAlarmDate(" + memo.id.toString() + ",'" + moment().add(1, "months").format("YYYY-MM-DD") + "', false);") +
                        div("big_list_item date_select",
                            div("date_description", "Other: " +
                                elementStandAlone("input", {
                                    type: "date",
                                    id: "alarmDate",
                                    name: "alarmDate",
                                    value: defaultDate,
                                    min: moment().add(1, "days").format("YYYY-MM-DD"),
                                    max: moment().add(2, "years").format("YYYY-MM-DD"),
                                    required: "required"
                                }) +
                                a("link_button hoverable", "Set", "setAlarmDate(" + memo.id.toString() + ",document.getElementById('alarmDate').value, false);"))));
                return Dexie.Promise.resolve(content);
            });
        case CONTENT_KEY_DELETE_MEMO:
            return frogDB.fetchMemo(args[KEY_TARGET_MEMO], true).then(function (memo) {
                nav.targetMemo = memo.id;
                content = _getLocalContentDefaults(contentKey);
                content[KEY_CONTENT] =
                    div("submenu",
                        a("link_button",
                            "&lt; Back",
                            "nav.pop();") +
                        div("spacer", "") +
                        a("link_button", span("inline_icon bucket350", "") + " Hide", "hideMemo(" + memo.id.toString() + ");")) +
                    div("heading", "Delete Memo") +
                    div("warning",
                        "Click the button to delete this memo. If you are unsure consider " +
                        span("inline_icon img_bucket350", "") +
                        " Hiding the memo or sending it to the " +
                        span("inline_icon img_bucket310", "") +
                        " Trash and marking it " +
                        span("inline_icon img_private_on", "") +
                        " Private instead. " +
                        strong("This cannot be undone!")) +
                    renderMemo(memo, MEMO_RENDER_TYPE_NO_TOOLS).outerHTML +
                    aId("delete_form_btn", "standard_button big_button delete enabled", span("inline_icon deleted", "") + " Delete", "");
                content[KEY_SCRIPT] = function () {
                    var btnDelete = document.getElementById("delete_form_btn");
                    btnDelete.onclick = function () {
                        if (btnDelete.classList.contains("enabled")) {
                            btnDelete.classList.remove("enabled");
                            deleteMemo(memo.id);
                        }
                    };
                };
                return Dexie.Promise.resolve(content);
            });
        case CONTENT_KEY_MEMO_DETAILS:
            return frogDB.fetchMemo(args[KEY_TARGET_MEMO], true).then(function (memo) {
                nav.targetMemo = memo.id;
                content = _getLocalContentDefaults(contentKey);
                placeholder = new Placeholder(PLACEHOLDER_MEMO_DETAILS, "Loading...", false, true);
                placeholder.addData(KEY_MEMO_ID, memo.id);
                placeholder.setFailureText("Could not download memo details from server. Are you connected to the Internet?");
                content[KEY_CONTENT] =
                    div("submenu",
                        a("link_button", "&lt; Home", "navigateHomeAndShowMemo(" + memo.id + ");") +
                        div("spacer", "") +
                        a("link_button", span("inline_icon img_edit", "") + " Edit", "navigateToEditMemo(" + memo.id.toString() + ");") +
                        a("link_button", span("inline_icon img_bucket350", "") + " Hide", "hideMemo(" + memo.id.toString() + ");") +
                        a("link_button delete", span("inline_icon img_bucket410", "") + " Delete", "navigateToDeleteMemo(" + memo.id.toString() + ");")
                    ) +
                    div("heading", "Memo Details.") +
                    renderMemo(memo, MEMO_RENDER_TYPE_NO_TOOLS).outerHTML +
                    placeholder.getHtml();
                content[KEY_SCRIPT] = function () {
                    return placeholder.execute();
                };
                return Dexie.Promise.resolve(content);
            });
        case CONTENT_KEY_TAGS:
            return frogDB.queryData(DATA_REQ_TAGS).then(function (result) {
                content = _getLocalContentDefaults(contentKey);
                content.contentData = result;
                return Dexie.Promise.resolve(content);
            });
        case CONTENT_KEY_FRIENDS:
            return frogDB.queryData(DATA_REQ_FRIENDS).then(function (result) {
                content = _getLocalContentDefaults(contentKey);
                content.contentData = result;
                return Dexie.Promise.resolve(content);
            });
        case CONTENT_KEY_FIND:
            content = _getLocalContentDefaults(contentKey);
            var findLink = function (caption, key) {
                return a("link_button", div("big_list_item hoverable", caption), "specialSearch(" + key.toString() + ");");
            };
            content[KEY_CONTENT] =
                div("submenu",
                    a("link_button", "&lt; Home", "navigateHome();") +
                    div("spacer", "") +
                    aId("search_button", "link_button disabled", "Search", "doSearch(this);")) +
                div("heading", "Find") +
                div("sub_heading", "Standard Search") +
                p("instructions", "You can search for memos with specific text, or with " + span("hashtag", "#tags") + " or memos from a " + span("screen_name", "@friend") + ".") +
                div("input_container",
                    textInput("filterTextInput", "search", "filterText", "Text Search", "Search for text...", "", true, "off", "")) +
                div("sub_heading", "Special Searches") +
                div("big_list",
                    findLink("Memos You Are Sharing With Friends", SPECIAL_SEARCH_SHARED) +
                    findLink("For Your Eyes Only: Private Memos", SPECIAL_SEARCH_PRIVATE) +
                    findLink("By Me: Memos I Have Written", SPECIAL_SEARCH_BY_ME) +
                    findLink("Not By Me: Memos Written by Friends", SPECIAL_SEARCH_NOT_BY_ME) +
                    findLink("Stars Only", SPECIAL_SEARCH_STARS) +
                    findLink("Unstarred", SPECIAL_SEARCH_UNSTARRED) +
                    findLink("Memos with Alarms Set", SPECIAL_SEARCH_ALARM) +
                    findLink("Edited Memos", SPECIAL_SEARCH_EDITED) +
                    findLink("Old Memos > 30 Days", SPECIAL_SEARCH_OLD) +
                    findLink("Up is Down: Sort Oldest First", SPECIAL_SEARCH_OLDEST_FIRST) +
                    findLink("Hidden Memos", SPECIAL_SEARCH_HIDDEN)
                );
            content[KEY_SCRIPT] = function () {
                var txtFilter = document.getElementById("filterTextInput");
                var btnSubmit = document.getElementById("search_button");
                var submitted = false;
                var f = function (e) {
                    if (txtFilter.value.length === 0)
                        btnSubmit.classList.add('disabled');
                    else
                        btnSubmit.classList.remove('disabled');
                    if (e.keyCode == 13 || e.keyCode === 10) {
                        if (e.preventDefault) e.preventDefault();
                        doSearch(this);
                        return false;
                    }
                };

                function doSearch(e) {
                    if (!submitted && (!e || !e.classList.contains('disabled'))) {
                        submitted = true;
                        clearFilters(BUCKET_EVERYTHING);
                        nav.contentReq = CONTENT_KEY_HOME;
                        nav.updateFilter({filterText: txtFilter.value});
                        navigate();
                    }
                }

                btnSubmit.onclick = function () {
                    doSearch(this);
                    return false;
                };
                txtFilter.onkeyup = f;
                txtFilter.onsearch = f;
                btnSubmit.classList.add('disabled');
            };
            return Dexie.Promise.resolve(content);
        case CONTENT_KEY_SETTINGS:
            content = _getLocalContentDefaults(contentKey);
            content[KEY_CONTENT] =
                div("heading", "Settings") +
                div("big_list",
                    div("big_list_item hoverable",
                        a("link_button", "Account Settings", "navigateTo(" + CONTENT_KEY_ACCOUNT.toString() + ");")) +
                    div("big_list_item hoverable",
                        a("link_button", "Notifications", "navigateTo(" + CONTENT_KEY_NOTIFICATIONS.toString() + ");")) +
                    div("big_list_item hoverable",
                        a("link_button", "Help", "navigateTo(" + CONTENT_KEY_HELP.toString() + ");")) +
                    div("big_list_item hoverable",
                        a("link_button", "Sign Out", "logout();"))) +
                div("footer", "&#x00a9;2015 - 2016 Memofrog. Software version " + session.appVersion);
            return Dexie.Promise.resolve(content);
        case CONTENT_KEY_CHANGE_PASSWORD:
            content = _getLocalContentDefaults(contentKey);
            content[KEY_CONTENT] =
                div("submenu",
                    a("link_button", "&lt; Back", "navigateTo(" + CONTENT_KEY_ACCOUNT.toString() + ");")) +
                div("heading", "Change Password") +
                form("standard_form",
                    fieldset(
                        textInput("key_change_password_old", "text", "key_change_password_old", "Verify Old Password", "Enter your old password", "", true, "off", "") +
                        divId("key_validation_target_old_password", "validation", "") +
                        textInput("key_change_password_new", "text", "key_change_password_old", "New Password", "Enter your new password", "", true, "off", "") +
                        divId("key_validation_target_new_password", "validation", "") +
                        hiddenInput("contentReq", CONTENT_KEY_POST_CHANGE_PASSWORD.toString())) +
                    fieldset(
                        div("button_row",
                            a("standard_button cancel", "Cancel", "navigateTo(" + CONTENT_KEY_ACCOUNT.toString() + ");") +
                            submitButton("key_submit_button", "standard_button", "Change Password")
                        )));
            content[KEY_SCRIPT] = function () {
                updateSubmitButton(false);
                initValidation(VALIDATION_TYPE_CHANGE_PASSWORD, ["key_change_password_old", "key_change_password_new"]);
            };
            return Dexie.Promise.resolve(content);
        case CONTENT_KEY_NEED_SERVER:
            content = _getLocalContentDefaults(contentKey);
            content[KEY_CONTENT] =
                div("submenu",
                    a("link_button", "&lt; Home", "navigateHome();")) +
                div("heading", "Can't connect to server.") +
                p("", "Memofrog needs to connect to the Internet to get that for you.<br>" +
                    a("link_button", "Try again.", "tryNavigateAgain();"));
            return Dexie.Promise.resolve(content);
        case CONTENT_KEY_ERROR:
            content = _getLocalContentDefaults(contentKey);
            if (!nav.isErrorReq())
                failedNavReq = nav.getNavReq();
            content[KEY_CONTENT] =
                div("submenu",
                    a("link_button", "&lt; Home", "navigateHome();")) +
                div("heading", "Error.") +
                div("subheading", "An error has occurred.") +
                div("info_widget",
                    span("info_intro", "Error Code") +
                    span("info_caption", nav.errorCode.toString())) +
                div("info_widget",
                    span("info_intro", "Error Req") +
                    span("info_caption", nav.errorReq.toString())) +
                p("",
                    a("link_button", "Try again.", "tryNavigateAgain();") +
                    "<br>" +
                    a("link_button", "Go Home.", "navigateHome();")
                );
            nav.errorCode = ERROR_NONE;
            nav.errorReq = CONTENT_KEY_NONE;
            return Dexie.Promise.resolve(content);
        default:
            return Dexie.Promise.reject();
    }
}

function checkForCachedContent(data) {
    if (!session.isLoggedIn() && data.contentReq !== CONTENT_KEY_LOGIN)
        return Dexie.Promise.reject();

    if (session.contentCache[data[KEY_CONTENT_REQ]]) {
        var content = session.contentCache[data[KEY_CONTENT_REQ]];
        if (content[KEY_CACHEABLE] === CACHEABLE_ALWAYS) {
            console.log("Retrieved content from cache.");
            return Dexie.Promise.resolve(content);
        }
    }
    return Dexie.Promise.reject();
}
function cacheServerResponse(response) {
    if (response.error === ERROR_NONE && response.cacheable !== CACHEABLE_NEVER) {
        session.contentCache[response.contentKey] = response;
        console.log("Cached content key " + response.contentKey.toString() + ".");
    }
}
function getContentFromServer(data) {
    return new Ajax("/ajax_content.php").get(data).then(function (response) {
        if (response) {
            if (typeof response === "object") {
                try {
                    cacheServerResponse(response);
                    console.log("Rec'd content from server.");
                    return Dexie.Promise.resolve(response);
                }
                catch (ex) {
                    console.error(ex);
                }
            }
            if (!nav.isErrorReq())
                failedNavReq = nav.getNavReq();
            nav.errorCode = ERROR_RENDER_ERROR;
            nav.errorReq = data.contentReq;
            return Dexie.Promise.reject(CONTENT_KEY_ERROR);
        } else {
            if (!nav.isErrorReq())
                failedNavReq = nav.getNavReq();
            return Dexie.Promise.reject(CONTENT_KEY_NEED_SERVER);
        }
    });
}

function _getLocalContentDefaults(contentKey) {
    console.log("Generating content locally for content key " + contentKey.toString());
    var content = {};
    content[KEY_CONTENT_KEY] = contentKey;
    content[KEY_CACHEABLE] = CACHEABLE_NEVER;
    content.isLocal = true;
    content.error = ERROR_NONE;
    content.needsValidationSuppressed = false;
    content[KEY_USER_ID] = session.getUserId();
    return content;
}

function _memoCountString(num) {
    return (num === 1) ? "1 memo" : (num.toString() + " memos");
}