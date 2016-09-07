var failedNavReq = CONTENT_KEY_NONE;

function navigate(args) {
    var data;
    if (args) {
        nav.update(args);
        data = nav.getNavReq();
        for (var key in args) {
            switch (key) {
                case KEY_CLEAR_FILTERS:
                    clearFilters(BUCKET_DEFAULT);
                    nav.update(args); // yes, again, so args before clearFilters aren't lost
                    break;
                case KEY_SPECIAL_SEARCH:
                    nav.specialSearch = args[key];
                    break;
                default:
                    data[key] = args[key];
                    break;
            }
        }
    }
    else {
        data = nav.getNavReq();
    }

    alertBox.deferLoadingAlert();

    logSeparator();
    console.log("NAV START");
    console.time("Navigate");
    return generateLocalContent(data).catch(function () {
        return checkForCachedContent(data).catch(function () {
            return getContentFromServer(data);
        });
    }).then(function (content) {
        return handleRedirect(data.contentReq, content);
    }).then(function (content) {
        return generateContentFromData(content);
    }).then(function (content) {
        nav.update(content);
        if (content[KEY_RESET_DB]) {
            return frogDB.clearAll().then(function () {
                alertBox.showAlert("Database Reset.");
                frogDB.prefetch(1000, false);
                return render(content);
            });
        } else {
            return render(content);
        }
    }).catch(function (newContentReq) {
        if (newContentReq !== CONTENT_KEY_ERROR)
            console.log("Redirecting from " + nav.contentReq.toString() + " to " + newContentReq.toString());
        return navigateTo(newContentReq);
    }).finally(function () {
        if (!nav.isErrorReq())
            failedNavReq = null;
        alertBox.cancelLoadingAlert();
        console.timeEnd("Navigate");
        return Dexie.Promise.resolve();
    });
}

function navigateTo(contentReq) {
    nav.contentReq = contentReq;
    if (contentReq === CONTENT_KEY_HOME) {
        if (nav.contentKey === CONTENT_KEY_HOME)
            nav.setBucketToDefault();
    }
    return navigate();
}
function navigateHome() {
    if (frogDB.isNew && session.isOnline())
        return navigateTo(CONTENT_KEY_LOGIN);
    else
        return navigateTo(CONTENT_KEY_HOME);
}
//noinspection JSUnusedGlobalSymbols
function navigateHomeAndShowMemo(memoId) {
    nav.targetMemo = memoId;
    navigateHome();
}
//noinspection JSUnusedGlobalSymbols
function navigateToEditMemo(memoId) {
    nav.targetMemo = memoId;
    return navigateTo(CONTENT_KEY_EDIT_MEMO);
}
//noinspection JSUnusedGlobalSymbols
function navigateToDeleteMemo(memoId) {
    nav.targetMemo = memoId;
    return navigateTo(CONTENT_KEY_DELETE_MEMO);
}
//noinspection JSUnusedGlobalSymbols
function navigateToFriend(screenName) {
    nav.friend = screenName;
    return navigateTo(CONTENT_KEY_FRIEND);
}
//noinspection JSUnusedGlobalSymbols
function tryNavigateAgain() {
    if (failedNavReq) {
        return navigate(failedNavReq);
    } else {
        return navigateHome();
    }
}
//noinspection JSUnusedGlobalSymbols
function navMemo(memoId) {
    nav.targetMemo = memoId;
    return navigateTo(CONTENT_KEY_MEMO_DETAILS);
}
//noinspection JSUnusedGlobalSymbols
function logoClick() {
    return navigateTo(CONTENT_KEY_SETTINGS);
}

//noinspection JSUnusedGlobalSymbols
function logout() {
    frogDB.clearAll().then(function () {
        alertBox.showAlert("Cleared data from browser.");
        return Dexie.Promise.resolve();
    }).finally(function () {
        navigateTo(CONTENT_KEY_LOGOUT);
    });
}
//noinspection JSUnusedGlobalSymbols
function toggleShareCanEdit(shareId) {
    var args = {};
    args[KEY_SHARE_CAN_EDIT_TO_TOGGLE] = shareId;
    return navigate(args);
}
//noinspection JSUnusedGlobalSymbols
function showErrorScreen(data) {
    var errData = {};
    errData[KEY_CONTENT_REQ] = CONTENT_KEY_ERROR;
    errData[KEY_ERROR_CODE] = ERROR_RENDER_ERROR;
    errData[KEY_ERROR_CONTENT_ID] = data[KEY_CONTENT_REQ];
    return navigate(errData);
}
