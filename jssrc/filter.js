function filterByBucket(bucket) {
    nav.updateFilter({bucket: bucket}, true);
    loadMemos(false);
}

//noinspection JSUnusedGlobalSymbols
function filterByScreenName(screenName, to) {

    var filter = {screenName: screenName, screenNameTo: to, bucket: BUCKET_EVERYTHING};

    if (nav.contentKey !== CONTENT_KEY_HOME) {
        filter.clearFilters = true;
        filter.bucket = BUCKET_EVERYTHING;
    }
    nav.updateFilter(filter, true);
    loadMemos(false);
}
function filterByTag(tag) {
    var tags = nav._filterTags;
    var tagOp = nav._filterTagsOp;

    if (tag.length > 0) {
        tag = tag.toLowerCase();

        if (tags.split("+").indexOf(tag) < 0)
            if (tags.length > 0) {
                tags += "+" + tag;
            } else {
                tags = tag;
                tagOp = FILTER_TAGS_OP_ALL;
            }

        var filter = {filterTags: tags, filterTagsOp: tagOp};
        if (nav.contentKey !== CONTENT_KEY_HOME) {
            filter.clearFilters = true;
            filter.bucket = BUCKET_EVERYTHING;
        }

        nav.updateFilter(filter, true);
        loadMemos(false);
    }
}
//noinspection JSUnusedGlobalSymbols
function filterByTagAndBucket(tag, bucket) {
    var filter = {filterTags: tag, bucket: bucket, clearFilters: true};
    nav.updateFilter(filter, true);
    loadMemos(false);
}
//noinspection JSUnusedGlobalSymbols
function filterByTagsAndScreenName(tags, screenName, to) {
    var filter = {filterTags: tags, screenName: screenName, screenNameTo: to, clearFilters: true, bucket: BUCKET_EVERYTHING};
    nav.updateFilter(filter, true);
    loadMemos(false);
}
//noinspection JSUnusedGlobalSymbols
function showTagMemos(tag) {
    var filter = {clearFilters: true, filterTags: tag, bucket: BUCKET_EVERYTHING};
    switch (nav.tagsView) {
        case TAGS_SHOW_OWN:
            filter.specialSearch = SPECIAL_SEARCH_BY_ME;
            break;
        case TAGS_SHOW_FRIENDS:
            filter.specialSearch = SPECIAL_SEARCH_NOT_BY_ME;
            break;
    }
    nav.updateFilter(filter, true);
    loadMemos(false);
}
//noinspection JSUnusedGlobalSymbols
function filterRemoveTag(tag) {
    if (nav.removeTag(tag))
        loadMemos(false);
}
//noinspection JSUnusedGlobalSymbols
function cycleFilterTagOp() {
    if (nav.cycleFilterTagsOp())
        loadMemos(false);
}
//noinspection JSUnusedGlobalSymbols
function cycleFilterScreenNameTo() {
    if (nav.cycleScreenNameTo())
        loadMemos(false);
}
function specialSearch(specialSearchKey) {

    var filter = {specialSearch: specialSearchKey};
    if (specialSearchKey !== SPECIAL_SEARCH_NONE) {
        filter.clearFilters = true;
        if (nav.contentKey !== CONTENT_KEY_HOME)
            filter.bucket = BUCKET_EVERYTHING;
    }

    nav.updateFilter(filter);
    loadMemos(false);
}
function filterByText(keyword) {
    keyword = keyword.toLowerCase();
    var filter = {filterText: keyword, clearFilters: keyword.length > 0, bucket: BUCKET_EVERYTHING};
    if (nav.updateFilter(filter)) {
        updateSearchBox();
        loadMemos(false);
    }
}
function clearFilters(bucket) {
    nav.updateFilter({clearFilters: true, bucket: bucket});
    clearSearchBox();
}
function clearSearchBox() {
    // will not load memos
    nav.updateFilter({filterText: ""});
    if (domManager.searchBox !== null)
        domManager.searchBox.value = "";
}
function updateSearchBox() {
    if (domManager.searchBox)
        domManager.searchBox.value = nav.getFilterTextAsEntered();
}
function canBroadenSearch() {
    return lastQueryResultType !== QUERY_RESULT_FAIL && !nav.isClearFilter();
}
//noinspection JSUnusedGlobalSymbols
function broadenSearch() {
    nav.broadenFilter();
    updateSearchBox();
    loadMemos(false);
}
//noinspection JSUnusedGlobalSymbols
function cycleTagsView() {
    switch (nav.tagsView) {
        case TAGS_SHOW_ALL:
            nav.tagsView = TAGS_SHOW_OWN;
            break;
        case TAGS_SHOW_OWN:
            nav.tagsView = TAGS_SHOW_FRIENDS;
            break;
        case TAGS_SHOW_FRIENDS:
        /* falls through */
        default:
            nav.tagsView = TAGS_SHOW_ALL;
            break;
    }
    navigateTo(CONTENT_KEY_TAGS);
}