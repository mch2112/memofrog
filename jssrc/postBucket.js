function setBucket(memoId, bucket, isUndo) {
    return doMemoChange(POST_SET_BUCKET, memoId, bucket, isUndo).then(function () {
        var memoDiv = document.getElementById('memo' + memoId.toString());
        if (memoDiv !== null) {
            updateMemoBucketClass(memoDiv, bucket);
            var navBucket = nav.getBucket();
            if (nav.contentKey === CONTENT_KEY_HOME && (
                (bucket === BUCKET_TRASH && navBucket !== BUCKET_TRASH) ||
                (bucket === BUCKET_HIDDEN && navBucket !== BUCKET_HIDDEN) ||
                bucket === BUCKET_DELETED)) {
                $(memoDiv).fadeOut();
                removeMemoFooter();
            } else {
                $(memoDiv).fadeIn();
            }
            memoDiv.classList.add("not_synced");
        }
    });
}
function updateMemoBucketClass(memoDiv, bucket) {
    updateBucketClass(memoDiv, bucket);
    memoDiv.getElementsByClassName("memo_bucket_icon")[0].className = "memo_bucket_icon img_bucket" + bucket.toString();
}
function updateBucketClass(element, bucket) {
    element.className = element.className.replace(/bucket[0-9]+/, "bucket" + bucket.toString());
}
//noinspection JSUnusedGlobalSymbols
function hideMemo(memoId) {
    setBucket(memoId, BUCKET_HIDDEN).then(function () {
        return navigateHome();
    });
}
function deleteMemo(memoId) {
    setBucket(memoId, BUCKET_DELETED).then(function () {
        return navigateHome();
    });
}