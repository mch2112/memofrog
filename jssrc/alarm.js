//noinspection JSUnusedGlobalSymbols
function setAlarmDate(memoId, date, isUndo) {
    console.log("Setting Alarm to " + date);
    doMemoChange(POST_SET_ALARM, memoId, date, isUndo).then(function() {
        nav.targetMemo = memoId;
        navigateHome();
    });
}