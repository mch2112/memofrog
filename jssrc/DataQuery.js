function DataQuery(userId, key, dataVersion, data) {
    this.userId = userId;
    this.key = key;
    this.dataVersion = dataVersion;
    this.data = data;
    this.isUpgradable = false;
    this.isMemoQuery = false;
}
function DataQueryResult(data, resultType) {
    this.data = data;
    this.resultType = resultType;
}