document.addEventListener('DOMContentLoaded', function () {
    console.info("Starting App Version " + session.appVersion + "...");
    console.timeEnd("DOM Ready");
    session.start();
});

/*// APPCACHE RELOAD

window.addEventListener('load', function () {
    if (window.applicationCache) {
        window.applicationCache.addEventListener('updateready', function (e) {
            if (window.applicationCache.status == window.applicationCache.UPDATEREADY) {
                console.log("APPCACHE Update Ready");
                // Browser downloaded a new app cache. Swap it in and reload the page to get new content.
                window.applicationCache.swapCache();
                window.location.reload();
            }
        }, false);
    }
}, false);*/

function loadMemos(more) {
    var page;
    console.time("Load Memos");
    if (more) {
        page = pageLoaded + 1;
    } else {
        pageLoaded = -1;
        page = 0;
    }

    alertBox.deferLoadingAlert();

    logSeparator();
    console.log("Querying db for memos...");
    return ((nav.contentKey === CONTENT_KEY_HOME) ?
        Dexie.Promise.resolve() :
        navigateTo(CONTENT_KEY_HOME))
        .then(function () {
            return frogDB.query(session.getUserId(), nav.getFilterKey(), page);
        }).then(function (result) {
            console.log("Query result rec'd");
            displayMemos(result);
        }).catch(function (err) {
            console.log("Error loading memos: " + err);
        }).finally(function() {
            nav.targetMemo = 0;
            session.informUserActionComplete();
            console.timeEnd("Load Memos");
        });
}
function displayMemos(result) {
    nav.push();
    updateListHeader();
    renderMemos(result);
    alertBox.cancelLoadingAlert();
}

function showMemo(memoDiv) {
    if (memoDiv !== null) {
        //domManager.contentContainer.scrollTop = getShowMemoOffset(memoDiv);
        memoDiv.scrollIntoView(true);
    }
}

function showTargetMemo() {
    if (nav.targetMemo > 0) {
        var memoDiv = document.getElementById('memo' + nav.targetMemo.toString());
        nav.targetMemo = 0;
        showMemo(memoDiv);
    }
}
