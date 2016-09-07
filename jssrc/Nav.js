var nav = new Nav();

function Nav() {

    this.contentKey = CONTENT_KEY_NONE;
    this.contentReq = CONTENT_KEY_NONE;

    this.targetMemo = 0;
    this.shareId = 0;
    this.friend = "";
    this.tagsAlphabetic = true;
    this.tagsView = TAGS_SHOW_ALL;
    this.errorCode = ERROR_NONE;

    this._screenName = "";
    this._screenNameTo = false;
    this._filterTags = "";
    this._filterTagsOp = FILTER_TAGS_OP_ALL;
    this._filterText = "";
    this._filterTextAsEntered = "";
    //noinspection JSUnresolvedVariable
    this._bucket = this.defaultBucket = __defaultBucket;
    this._specialSearch = SPECIAL_SEARCH_NONE;

    this.isPop = false;
}
Nav.prototype.update = function (parameters) {

    if ("contentKey" in parameters)
        this.contentKey = parameters.contentKey;
    if ("contentReq" in parameters)
        this.contentReq = parameters.contentReq;
    if ("screenName" in parameters)
        this._screenName = parameters.screenName;
    if ("screenNameTo" in parameters)
        this._screenNameTo = parameters.screenNameTo;
    if ("friend" in parameters)
        this.friend = parameters.friend;
    if ("filterTags" in parameters)
        this._filterTags = parameters.filterTags;//.replace(/#/g, "");
    if ("filterTagsOp" in parameters)
        this._filterTagsOp = parameters.filterTagsOp;
    if ("filterText" in parameters)
        this._filterText = parameters.filterText;
    if (parameters.bucket)
        this._bucket = parameters.bucket;
    if ("specialSearch" in parameters)
        this._specialSearch = parameters.specialSearch;
    if ("targetMemo" in parameters)
        this.targetMemo = parameters.targetMemo;
    if ("shareId" in parameters)
        this.shareId = parameters.shareId;
    if ("tag" in parameters)
        this.tag = parameters.tag;
};
Nav.prototype.getNavReq = function () {
    var req = {contentReq: this.contentReq ? this.contentReq : this.contentKey};
    if (this.targetMemo > 0)
        req.targetMemo = this.targetMemo;
    if (this.shareId > 0)
        req.shareId = this.shareId;
    if (this.friend)
        req.friend = this.friend;
    if (this.tag)
        req.tag = this.tag;
    return req;
};
Nav.prototype.isErrorReq = function () {
    return this.contentReq === CONTENT_KEY_ERROR || this.contentReq === CONTENT_KEY_NEED_SERVER;
};
Nav.prototype.updateFilter = function (args, resetFilterText) {

    var oldBucket = this._bucket;
    var oldFilterTags = this._filterTags;
    var oldFilterTagsOp = this._filterTagsOp;
    var oldScreenName = this._screenName;
    var oldScreenNameTo = this._screenNameTo;
    var oldFilterText = this._filterText;
    var oldSpecialSearch = this._specialSearch;

    if (resetFilterText)
        this._canonize(true);

    if (args.clearFilters)
        this.clearFilters(BUCKET_DEFAULT);
    if (args.bucket)
        this._bucket = args.bucket === BUCKET_DEFAULT ? this.defaultBucket : args.bucket;
    if ("filterTags" in args)
        this._filterTags = args.filterTags;
    if ("filterTagsOp" in args)
        this._filterTagsOp = args.filterTagsOp;
    if ("screenName" in args)
        this._screenName = args.screenName;
    if ("screenNameTo" in args)
        this._screenNameTo = args.screenNameTo;
    if ("filterText" in args)
        this._filterTextAsEntered = args.filterText;
    if ("specialSearch" in args)
        this._specialSearch = args.specialSearch;

    this._canonize(false);

    return this._bucket !== oldBucket ||
        this._filterTags !== oldFilterTags ||
        this._filterTagsOp !== oldFilterTagsOp ||
        this._screenName !== oldScreenName ||
        this._screenNameTo !== oldScreenNameTo ||
        this._filterText !== oldFilterText ||
        this._specialSearch !== oldSpecialSearch;
};
Nav.prototype.removeTag = function (tag) {
    if (tag !== tag.toLowerCase())
        console.error("Non lower case tag");
    var tags = this._filterTags.split("+");
    if (tryRemoveFromArray(tags, tag)) {
        this._filterTags = tags.join("+");
        this._canonize(true);
        return true;
    } else {
        return false;
    }
};
Nav.prototype.isClearFilter = function () {
    return this._bucket === BUCKET_EVERYTHING &&
        this._filterTags.length === 0 &&
        this._screenName.length === 0 &&
        this._filterTextAsEntered.length === 0;
};
Nav.prototype.broadenFilter = function () {
    if (this._bucket !== BUCKET_EVERYTHING)
        this._bucket = BUCKET_EVERYTHING;
    else if (this._filterTags.length > 0)
        this._filterTags = "";
    else if (this._screenName.length > 0)
        this.screenName = "";
    else if (this._filterText.length > 0)
        this._filterTextAsEntered = "";
    this._canonize(true);
};
Nav.prototype.cycleFilterTagsOp = function () {
    if (this._filterTags.indexOf("+") >= 0) {
        switch (this._filterTagsOp) {
            case FILTER_TAGS_OP_ALL:
                this._filterTagsOp = FILTER_TAGS_OP_ANY;
                break;
            case FILTER_TAGS_OP_ANY:
            /* falls through */
            default:
                this._filterTagsOp = FILTER_TAGS_OP_ALL;
                break;
        }
        this._canonize(true);
        return true;
    } else {
        return false;
    }
};
Nav.prototype.cycleScreenNameTo = function () {
    var ret = false;
    if (this._screenName.length) {
        this._screenNameTo = !this._screenNameTo;
        ret = true;
    }
    this._canonize(true);
    return ret;
};
Nav.prototype.setBucketToDefault = function () {
    this._bucket = this.defaultBucket;
    this._canonize(true);
};
Nav.prototype.getBucket = function () {
    return this._bucket;
};
Nav.prototype.getFilterTextAsEntered = function () {
    return this._filterTextAsEntered;
};
/* returns true if changed */
Nav.prototype._canonize = function (resetFilterText) {
    var changed = false;
    switch (this._specialSearch) {
        case SPECIAL_SEARCH_BY_ME:
            if (this._screenName.length && !this._screenNameTo) {
                this._specialSearch = SPECIAL_SEARCH_NONE;
                changed = true;
            }
            break;
        case SPECIAL_SEARCH_NOT_BY_ME:
            if (this._screenName.length && this._screenNameTo) {
                this._specialSearch = SPECIAL_SEARCH_NONE;
                changed = true;
            }
            break;
        case SPECIAL_SEARCH_HIDDEN:
            this._bucket = BUCKET_HIDDEN;
            this._specialSearch = SPECIAL_SEARCH_NONE;
            changed = true;
            break;
    }
    if (this._filterTextAsEntered.length) {
        var self = this;
        var filterText =
            this._filterTextAsEntered.toLowerCase().replace(/#([a-z0-9]+)/g, function (match, tag) {
                if (self._filterTags.length) {
                    if (self._filterTags.indexOf("+") >= 0) {
                        if (self._filterTags.split("+").indexOf(tag) < 0) {
                            self._filterTags += "+" + tag;
                        }
                    } else {
                        if (self._filterTags !== tag) {
                            self._filterTags += "+" + tag;
                        }
                    }
                } else {
                    self._filterTags = tag;
                }
                changed = true;
                return "";
            }).replace(/@([a-z0-9]+)/, function (match, sn) {
                self._screenName = sn.toLowerCase();
                self._screenNameTo = false;
                changed = true;
                return "";
            }).replace(/\^([a-z]+)/, function (match, h) {
                switch (h) {
                    case "shared":
                        self._specialSearch = SPECIAL_SEARCH_SHARED;
                        break;
                    case "stars":
                        self._specialSearch = SPECIAL_SEARCH_STARS;
                        break;
                    case "nostars":
                        self._specialSearch = SPECIAL_SEARCH_UNSTARRED;
                        break;
                    case "old":
                        self._specialSearch = SPECIAL_SEARCH_OLD;
                        break;
                    case "edited":
                        self._specialSearch = SPECIAL_SEARCH_EDITED;
                        break;
                    case "byme":
                        self._specialSearch = SPECIAL_SEARCH_BY_ME;
                        break;
                    case "notbyme":
                        self._specialSearch = SPECIAL_SEARCH_NOT_BY_ME;
                        break;
                    case "private":
                        self._specialSearch = SPECIAL_SEARCH_PRIVATE;
                        break;
                    case "alarm":
                        self._specialSearch = SPECIAL_SEARCH_ALARM;
                        break;
                    case "oldestfirst":
                        self._specialSearch = SPECIAL_SEARCH_OLDEST_FIRST;
                        break;
                    case "hidden":
                        self._specialSearch = SPECIAL_SEARCH_HIDDEN;
                        break;
                    default:
                        self._specialSearch = SPECIAL_SEARCH_NONE;
                        break;
                }
                return "";
            });
        var arr;
        var out = [];
        var re = /(\-?\w+)/g;
        while ((arr = re.exec(filterText)) !== null) {
            out.push(arr[0]);
        }
        this._filterText = out.join(" ");

    } else {
        this._filterText = "";
    }
    if (resetFilterText)
        this._filterTextAsEntered = this._filterText;
    return changed;
};
Nav.prototype.getFilterReq = function () {
    this._canonize(false);
    var req = {bucket: this._bucket};
    if (this._filterTags.length) {
        req.filterTags = this._filterTags;
        if (this._filterTags.indexOf("+") >= 0)
            req.filterTagsOp = this._filterTagsOp;
    }
    if (this._filterText.length)
        req.filterText = this._filterText;
    if (this._screenName.length) {
        req.screenName = this._screenName;
        req.screenNameTo = this._screenNameTo;
    }
    if (this._specialSearch !== SPECIAL_SEARCH_NONE)
        req.specialSearch = this._specialSearch;

    console.log("REQ: " + JSON.stringify(req));

    return req;
};
Nav.prototype.getFilterKey = function () {
    var req = this.getFilterReq();

    return JSON.stringify(req);
};
Nav.prototype.getFilterReqFromFilterKey = function (filterKey) {
    return JSON.parse(filterKey);
};
Nav.prototype.clearFilters = function (bucket) {
    this._screenName = '';
    this._screenNameTo = false;
    this._filterTags = '';
    this._filterTagsOp = FILTER_TAGS_OP_ALL;
    this._filterText = '';
    if (bucket !== BUCKET_NONE)
        this._bucket = bucket === BUCKET_DEFAULT ? this.defaultBucket : bucket;
    this._specialSearch = SPECIAL_SEARCH_NONE;
};
Nav.prototype.setDefaultBucket = function (bucket) {
    this._bucket = this.defaultBucket = bucket;
};
Nav.prototype.pop = function () {
    console.log("JS invoked Pop");
    history.back();
};
Nav.prototype.handlePop = function (popVal) {
    if (popVal) {
        this.isPop = true;
        this._deserialize(popVal);
        if (this.contentReq === CONTENT_KEY_LOGIN && session.isLoggedIn())
            this.contentReq = CONTENT_KEY_HOME;
        console.log("Navigating back: " + popVal);
        navigate();
    }
};
Nav.prototype.push = function () {
    var pushVal = this._serialize();
    if (pushVal !== history.state) {
        var uri = this._getUri();
        if (this.isPop)
            this.isPop = false;
        else if (history.state !== null) {
            console.log("Saving history: " + pushVal);
            history.pushState(pushVal, "", uri);
        } else {
            history.replaceState(pushVal, "", uri);
        }
    }
};
Nav.prototype._getUri = function () {

    var cr;
    var ret = [];
    switch (this.contentKey) {
        case CONTENT_KEY_TAGS:
            cr = "/tags";
            break;
        case CONTENT_KEY_NEW_MEMO:
            cr = "/new_memo";
            break;
        case CONTENT_KEY_FRIENDS:
            cr = "/friends";
            break;
        //case CONTENT_KEY_FRIEND:
        //    cr = "/friend";
//            break;
        default:
            cr = "/frog";
            ret = ["contentReq=" + this.contentKey.toString()];
            break;
    }
    if (this.tag)
        ret.push("tag=" + this.tag);
    if (this.friend)
        ret.push("friend=" + this.friend);
    if (this.shareId)
        ret.push("shareId=" + this.shareId.toString());
    if (this.targetMemo && this.contentKey !== CONTENT_KEY_HOME)
        ret.push("targetMemo=" + this.targetMemo.toString());
    if (ret.length)
        return cr + "?" + ret.join("&");
    else
        return cr;
};
Nav.prototype.persist = function () {
    localStorage.navPersist = this._serialize();
};
Nav.prototype.restore = function () {
    var state = localStorage.navPersist;
    if (state) {
        this._deserialize(state);
        localStorage.navPersist = "";
    }
};
Nav.prototype.memoVisible = function (memo) {
    return memo.isCompatible(this.getFilterReq());
};
Nav.prototype._serialize = function () {
    var state = {n: this.getNavReq()};
    if (this.contentKey === CONTENT_KEY_HOME)
        state.f = this.getFilterReq();
    return JSON.stringify(state);
};
Nav.prototype._deserialize = function (json) {
    var state = JSON.parse(json);
    this.update(state.n);
    if (state.f) {
        this.clearFilters(state.f.bucket);
        this.updateFilter(state.f, true);
    }
};