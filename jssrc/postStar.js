//noinspection JSUnusedGlobalSymbols
function starMemo(memoId) {
    var memoDiv = document.getElementById('memo' + memoId.toString());
    if (memoDiv !== null) {
        var wasStar = memoDiv.classList.contains('star');
        if (wasStar) {
            memoDiv.classList.remove("star");
        }
        else {
            memoDiv.classList.add("star");
            //var ml = document.getElementById("memo_list");
            //if (ml) {
            //    $(memoDiv).fadeOut();
            //    ml.removeChild(memoDiv);
            //    ml.insertBefore(memoDiv, document.getElementById("memo_advisory").nextSibling);
            //    $(memoDiv).fadeIn();
            //}
        }
        memoDiv.classList.add("not_synced");
        doMemoChange(POST_SET_STAR, memoId, wasStar ? 0 : 1, false);
    }
}

