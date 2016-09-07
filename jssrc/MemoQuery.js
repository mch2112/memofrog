function MemoQuery(userId, key, data, bucket, dataVersion, moreAvailable, isOldestFirst, isUpgradable) {
    this.userId = userId;
    this.key = key;
    this.data = data;
    this.bucket = bucket;
    this.dataVersion = dataVersion;
    this.moreAvailFromServer = moreAvailable;
    this.isOldestFirst = isOldestFirst;
    this.needsSort = false;
    this.isUpgradable = isUpgradable;
    this.isMemoQuery = true;
}
MemoQuery.prototype.isUsable = function(userId, key) {
    return (this.userId === userId && this.key === key);
};
MemoQuery.prototype.isCurrent = function(dataVersion) {
    return this.dataVersion === dataVersion;
};
MemoQuery.prototype.isComplete = function(page) {
    return this.data.length >= (page + 1) * PAGE_SIZE;
};
MemoQuery.prototype.moreAvailable = function(page) {
    return this.moreAvailFromServer || this.data.length > (page + 1) * PAGE_SIZE;
};
function MemoQueryResult(page, memos, moreAvailable, resultType) {
    this.page = page;
    this.memos = memos;
    this.more = moreAvailable;
    this.resultType = resultType;
}
