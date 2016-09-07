var PAGE_SIZE = 30;

var frogDB = new FrogDB();

var QUERY_RESULT_NONE = 0;
var QUERY_RESULT_LOCAL = 1;
var QUERY_RESULT_SERVER = 2;
var QUERY_RESULT_LOCAL_FALLBACK = 3;
var QUERY_RESULT_SYNTHETIC = 4;
var QUERY_RESULT_FAIL = 5;
var QUERY_RESULT_ERROR = 6;

var PREFETCH_DELAY_MSEC = 30000;
var CLEANUP_DELAY_MSEC = PREFETCH_DELAY_MSEC * 10;

var DATA_REQ_TAGS = 110;
var DATA_REQ_FRIENDS = 120;

function FrogDB() {
    console.time("IndexedDB Init");
    this.SEND_CHANGES_WAIT_MSEC_DEFAULT = 50;
    this.SEND_CHANGES_WAIT_MSEC_MAX = 600000; // 10 min
    this.MAX_HISTORY_SIZE = 60;

    this.dexie = null;

    this._dbName = "frog5b";
    this._sendChangesWaitMsec = 50;
    this._sendingChanges = false;
    this._prefetchDelay = null;
    this._sendChangesDelay = null;
    this._prefetching = false;
    this._basicQueryKeys = [];

    this.dexie = new Dexie(this._dbName);
    this.dexie.version(1).stores({
        info: "infoId",
        memos: "id,bucket,sortKey,timeStamp,createDate",
        queries: "key",
        posts: "txId,memoId"
    });
    this.dexie.memos.mapToClass(Memo);
    this.dexie.queries.mapToClass(MemoQuery);
    this.dexie.open();
    this.dexie.on("blocked", function () {
        console.error("DB Blocked!");
    });
    console.timeEnd("IndexedDB Init");
}

FrogDB.prototype.start = function () {

    var that = this;

    new Defer(CLEANUP_DELAY_MSEC).promise.then(function () {
        that.cleanup();
    });
    this._basicQueryKeys =
        ([{dataReq: DATA_REQ_TAGS}, {dataReq: DATA_REQ_FRIENDS}].concat([
            BUCKET_EVERYTHING,
            BUCKET_ALL_ACTIVE,
            BUCKET_HOT_LIST,
            BUCKET_B_LIST,
            BUCKET_JOURNAL,
            BUCKET_REFERENCE,
            BUCKET_DONE,
            BUCKET_TRASH,
            BUCKET_HIDDEN].map(function (b) {
            return {bucket: b};
        }))).map(function (k) {
            return JSON.stringify(k);
        });

    that.sendPosts();
    return Dexie.Promise.resolve();
};

FrogDB.prototype.cleanup = function () {
    var that = this;
    console.time("IndexedDB Cleanup");
    return Dexie.getDatabaseNames().then(function (names) {
        console.log("Checking for old DBs to delete...");
        return Dexie.Promise.all(names.map(function (n) {
            if (n !== that._dbName)
                return that._nukeDB(n);
            else
                return Dexie.Promise.resolve();
        }));
    }).then(function () {
        console.log("Checking for old queries to delete...");
        return that._getInfo("queryHistory", []);
    }).then(function (h) {
        if (h.length < that.MAX_HISTORY_SIZE) {
            return Dexie.Promise.resolve();
        } else {
            var skip = [];
            return Dexie.Promise.all(h.slice(0, h.length - that.MAX_HISTORY_SIZE).map(function (key) {
                if (that._basicQueryKeys.indexOf(key) >= 0) {
                    // don't kill the fundamental ones
                    skip.push(key);
                    return Dexie.Promise.resolve();
                } else {
                    console.log("Deleting query " + key + " from db");
                    return that.dexie.queries.delete(key);
                }
            })).then(function () {
                return that._setInfo("queryHistory", skip.concat(h.slice(h.length - that.MAX_HISTORY_SIZE)));
            });
        }
    }).then(function () {
        console.log("Checking for orphan memos to delete.");
        return that.dexie.queries.toArray().then(function (queries) {
            var nonOrphanIds = queries.filter(function (query) {
                return query.isMemoQuery;
            }).map(function (query) {
                return query.data;
            });
            var flatten = function (arr) {
                return arr.reduce(function (output, input) {
                    return output.concat(Array.isArray(input) ? flatten(input) : input);
                }, []);
            };
            nonOrphanIds = flatten(nonOrphanIds);
            //console.log("Non orphan memos: " + JSON.stringify(qIds));
            var orphans = [];
            that.dexie.memos.each(function (m) {
                if (nonOrphanIds.indexOf(m.id) < 0) {
                    orphans.push(m.id);
                    //console.log("Found orphan " + m.id.toString());
                }
            }).then(function () {
                console.log("Found " + orphans.length.toString() + " orphans.");
                orphans.forEach(function (id) {
                    console.log("Deleting orphan memo " + id.toString() + " from DB");
                    that.dexie.memos.delete(id);
                });
            });
        });
    }).catch(function (err) {
        console.error("Error in db cleanup: " + err);
    }).finally(function () {
        // do it all over again in 10 hours if that app is open that long
        new Defer(36000000).promise.then(function () {
            that.cleanup();
        });
        console.timeEnd("IndexedDB Cleanup");
    });
};

FrogDB.prototype._getInfo = function (key, def) {
    return this.dexie.info.get(key).then(function (d) {
        if (d)
            return Dexie.Promise.resolve(d.value);
        else
            return Dexie.Promise.resolve(def);
    }).catch(function (err) {
        console.error(err);
        return Dexie.Promise.resolve(def);
    });
};
FrogDB.prototype._setInfo = function (key, value) {
    //console.log("Setting info key " + key + " to value " + value.toString() + ".");
    return this.dexie.info.put({infoId: key, value: value}).then(function () {
        // aids fluency
        return Dexie.Promise.resolve(value);
    });
};
FrogDB.prototype._processInfo = function (key, def, func) {
    var that = this;
    return this._getInfo(key, def).then(function (value) {
        value = func(value);
        return that._setInfo(key, value);
    });
};

//==================================================================
// DATA VERSIONING
//==================================================================

FrogDB.prototype._getDataVersion = function () {
    return this._getInfo("dataVersion", 0);
};
FrogDB.prototype.setDataVersion = function (newVersion) {
    return this._setInfo("dataVersion", newVersion);
};

//==================================================================
// DATA FETCH
//==================================================================

FrogDB.prototype.fetchMemo = function (id, tryServer) {
    var that = this;
    return this.dexie.memos.get(id).then(function (m) {
        if (m)
            return Dexie.Promise.resolve(m);
        else
            return Dexie.Promise.reject();
    }).catch(function (err) {
        if (tryServer) {
            var data = {};
            data[KEY_TARGET_MEMO] = id;
            return new Ajax("/ajax_fetch_memos.php").get(data).then(function (response) {
                if (response && response.ok && response[KEY_MEMO_DATA]) {
                    var m = new Memo(response[KEY_MEMO_DATA]);
                    return that.dexie.memos.put(m).then(function () {
                        return Dexie.Promise.resolve(m);
                    });
                } else {
                    console.error("Error retrieving memo " + id.toString() + " from DB: " + err);
                    return Dexie.Promise.reject(false);
                }
            });
        } else {
            return Dexie.Promise.reject();
        }
    });
};
FrogDB.prototype.prefetch = function (waitMsec, checkDataVersion) {
    var that = this;

    if (this._prefetchDelay)
        this._prefetchDelay.cancel();

    if (this._prefetching)
        return Dexie.Promise.reject();

    if (!waitMsec)
        waitMsec = PREFETCH_DELAY_MSEC;

    return (this._prefetchDelay = new Defer(waitMsec)).promise.then(function () {
        that._prefetching = true;
        console.log("Prefetching basic queries...");
        return that._getDataVersion();
    }).then(function (ver) {
        return Dexie.Promise.all(that._basicQueryKeys.map(function (key, idx) {
            return that.dexie.queries.get(key).then(function (query) {
                if (!query || !checkDataVersion || query.dataVersion !== ver) {
                    return new Defer(idx * PREFETCH_DELAY_MSEC / 10).promise.then(function () {
                        console.log("Prefetching key " + key + ".");
                        var keyObj = JSON.parse(key);
                        if ("bucket" in keyObj) // memo fetch
                            return that._fetchDataServer(key, 0);
                        else
                            return that.queryData(keyObj.dataReq);
                    });
                } else {
                    console.log("Skipping prefetch of key " + key);
                    return Dexie.Promise.resolve();
                }
            });
        }));
    }).finally(function () {
        that._prefetching = false;
    });
};
FrogDB.prototype.queryData = function (dataReq) {
    var storedDataVersion;
    var that = this;
    var key = JSON.stringify({dataReq: dataReq});
    var qry = null;
    return this._getDataVersion().then(function (ver) {
        storedDataVersion = ver;
        return that.dexie.queries.get(key);
    }).then(function (query) {
        if (query && query.isUsable(session.getUserId(), key)) {
            qry = query;
            if (query.isCurrent(storedDataVersion)) {
                console.log("Returning local data for key: " + key);
                return Dexie.Promise.resolve(new DataQueryResult(query.data, QUERY_RESULT_LOCAL));
            }
        }
        return Dexie.Promise.reject();
    }).catch(function () {
        var data = {dataReq: dataReq};
        return new Ajax("/ajax_fetch_data.php").get(data).then(function (response) {
            if (response && response.ok && response.dataReq === dataReq && response.data) {
                return that._getDataVersion().then(function (ver) {
                    return that.dexie.queries.put(new DataQuery(session.getUserId(), key, ver, response.data)).then(function () {
                        console.log("Returning server response for key: " + key);
                        return Dexie.Promise.resolve(new DataQueryResult(response.data, QUERY_RESULT_SERVER));
                    });
                });
            } else {
                return Dexie.promise.reject();
            }
        });
    }).catch(function () {
        if (qry) {
            console.log("Returning local data (fallback) for key: " + key);
            return Dexie.Promise.resolve(new DataQueryResult(qry.data, QUERY_RESULT_LOCAL_FALLBACK));
        } else {
            console.error("Failed to find data for key: " + key);
            return Dexie.Promise.reject();
        }
    });
};
FrogDB.prototype.query = function (userId, key, page) {
    var self = this;
    var storedDataVersion;
    var qry;
    console.time("DB Query");
    return this._getDataVersion().then(function (ver) {
        storedDataVersion = ver;
        return self.dexie.queries.get(key);
    }).then(function (q) {
        if (q && q.isUsable(userId, key, page)) {
            qry = q;
            if (q.isComplete(page) || !q.moreAvailFromServer) {
                if (q.isCurrent(storedDataVersion))
                    return self.fetchMemoLocal(qry, page, false);
                else
                    console.log("Found old data version query for (" + qry.dataVersion.toString() + ", needed version " + storedDataVersion.toString() + ".)");
            }
        }
        return Dexie.Promise.reject();
    }).catch(function () {
        console.log("No current query found for key " + key);
        return self._fetchDataServer(key, page);
    }).catch(function (fatal) {
        if (fatal)
            throw new Error("Can't continue query.");
        else if (qry) {
            console.log("Falling back to local DB since can't contact server...");
            return self.fetchMemoLocal(qry, page, true);
        } else {
            return Dexie.Promise.reject();
        }
    }).catch(function () {
        return self._doSyntheticQuery(key, page);
    }).catch(function () {
        return Dexie.Promise.resolve(new MemoQueryResult(0, [], false, QUERY_RESULT_FAIL));
    }).finally(function () {
        self._processInfo("queryHistory", [], function (h) {
            tryRemoveFromArray(h, key);
            h.push(key);
            return h;
        });
        console.timeEnd("DB Query");
    });
};

FrogDB.prototype.fetchMemoLocal = function (query, page, isFallback) {
    var that = this;
    return this._sortQuery(query).then(function (q) {
        query = q;
        return Dexie.Promise.all(query.data.slice(page * PAGE_SIZE, (page + 1) * PAGE_SIZE).map(function (id) {
            return that.dexie.memos.get(id);
        }));
    }).then(function (memos) {
        console.log("Fetched " + memos.length.toString() + " memos from DB for key " + query.key);
        // according to spec these should be ordered based on input promises
        return Dexie.Promise.resolve(new MemoQueryResult(page, memos, query.moreAvailable(page), isFallback ? QUERY_RESULT_LOCAL_FALLBACK : QUERY_RESULT_LOCAL));
    });
};
FrogDB.prototype._sortQuery = function (query) {
    var that = this;
    if (query.needsSort) {
        return Dexie.Promise.all(query.data.map(function (id) {
            return that.dexie.memos.get(id);
        })).then(function (memos) {
            memos.sort(that._getSortComparator(query.bucket, query.isOldestFirst));
            query.data = memos.map(function (m) {
                return m.id;
            });
            query.needsSort = false;
            return Dexie.Promise.resolve(query);
        });
    } else {
        return Dexie.Promise.resolve(query);
    }
};
FrogDB.prototype._fetchDataServer = function (filterKey, page) {
    var that = this;
    var data = nav.getFilterReqFromFilterKey(filterKey);
    var numMemosReq = (page + 4) * PAGE_SIZE; // 3 extra pages
    data[KEY_NUM_MEMOS_REQ] = numMemosReq;
    return new Ajax("/ajax_fetch_memos.php").get(data).then(function (response) {
        if (response) {
            if (response.ok) {
                console.log("Received response from server for key " + filterKey + ".");
                return that._updateDB(response, filterKey, page, numMemosReq, data);
            } else if (response[KEY_REDIRECT] && response[KEY_CONTENT_REQ]) {
                navigateTo(response[KEY_CONTENT_REQ]);
                console.log("Redirecting on server instructions during memo query...");
                return Dexie.Promise.reject(true);
            }
        } else {
            console.log("Ajax error.");
            return Dexie.Promise.reject(false);
        }
    });
};
FrogDB.prototype._doSyntheticQuery = function (filterKey, page) {
    // Very heavyweight, last resort
    console.log("Trying synthetic query for key " + filterKey);
    var filterObj = nav.getFilterReqFromFilterKey(filterKey);

    if (filterObj.screenName && filterObj.screenNameTo)
        return Dexie.Promise.reject();

    var table = this.dexie.memos;
    if (filterObj.specialSearch === SPECIAL_SEARCH_OLDEST_FIRST)
        table = table.orderBy("timeStamp");
    else if (filterObj.bucket === BUCKET_JOURNAL)
        table = table.orderBy("createDate").reverse();
    else
        table = table.orderBy("sortKey").reverse();

    var count = 0;
    var skip = page * PAGE_SIZE;
    return table.toArray().then(function (memos) {
        memos = memos.filter(function (m) {
            if (count >= PAGE_SIZE) {
                return false;
            } else {
                if (m.isCompatible(filterObj) && (skip-- <= 0)) {
                    count++;
                    return true;
                } else {
                    return false;
                }
            }
        });
        //memos.sort(that._getSortComparator(filterObj.bucket, filterObj.specialSearch === SPECIAL_SEARCH_OLDEST_FIRST));
        //memos = memos.slice(page * PAGE_SIZE, (page + 1) * PAGE_SIZE);
        return Dexie.Promise.resolve(new MemoQueryResult(page, memos, memos.length >= PAGE_SIZE, QUERY_RESULT_SYNTHETIC));
    });
};
FrogDB.prototype._updateDB = function (response, filterKey, page, numMemosReq, filterObject) {
    var that = this;
    var memos = response[KEY_MEMO_LIST].map(function (m) {
        return new Memo(m, true);
    });

    // save the index of the query where the stars stop and the non-stars begin (for relevant buckets)
    var isOldestFirst = filterObject.specialSearch !== undefined && filterObject.specialSearch === SPECIAL_SEARCH_OLDEST_FIRST;
    var moreAvailable = memos.length >= numMemosReq;
    var query;
    return Dexie.Promise.all(memos.map(function (m) {
        return that.dexie.memos.put(m);
    })).then(function () {
        return that._getDataVersion();
    }).then(function (dataVersion) {
        var ids = memos.map(function (m) {
            return m.id;
        });
        return that.dexie.queries.put(query = new MemoQuery(session.getUserId(), filterKey, ids, filterObject.bucket, dataVersion, moreAvailable, isOldestFirst, filterObject.screenName === undefined || filterObject.screenNameTo === false));
    }).then(function () {
        console.log("Persisted query key " + filterKey + " with " + memos.length.toString() + " memos.");
        return Dexie.Promise.resolve(new MemoQueryResult(page, memos.slice(page * PAGE_SIZE, (page + 1) * PAGE_SIZE), query.moreAvailable(page), QUERY_RESULT_SERVER));
    }).catch(function (err) {
        console.error("Error updating DB:" + err);
        throw err;
    });
};

//==================================================================
// POST CHANGES
//==================================================================

var KEY_POST_TX_ID = "txId";
var KEY_POST_SUCCESS = "keyPostSuccess";

FrogDB.prototype.storePost = function (post, currentFilterKey) {
    console.time("Store Post");
    logSeparator();
    var that = this;
    return this._getTxId().then(function (txId) {
        post.txId = txId;
        console.log("Storing post " + txId);
        return Dexie.Promise.resolve();
    }).then(function () {
        // update the memo if needed
        switch (post.postType) {
            case POST_CREATE_MEMO:
            /* falls through */
            case POST_EDIT_MEMO:
                post.newMemo = post.newValue;
                break;
            case POST_SET_STAR:
                post.newMemo = post.oldMemo.clone();
                post.newMemo.star = post.newValue;
                break;
            case POST_SET_BUCKET:
                post.newMemo = post.oldMemo.clone();
                post.newMemo.setBucket(post.newValue);
                break;
            case POST_SET_ALARM:
                post.newMemo = post.oldMemo.clone();
                post.newMemo.alarmDate = post.newValue;
                break;
            default:
                throw new Error("Invalid post type.");
        }
        post.newMemo._reset();
        post.newMemo.synced = false;
        console.log("Updating memo " + post.newMemo.id.toString() + " in memo DB");
        return that.dexie.memos.put(post.newMemo);
    }).then(function () {
        return that._updateQueriesAfterMemoChange(post, currentFilterKey);
    }).then(function () {
        // store the post itself (it may have been updated since this method started)
        return that.dexie.posts.add(post);
    }).then(function () {
        that.sendPosts(that.SEND_CHANGES_WAIT_MSEC_DEFAULT); // async
        console.log("Post " + post.txId + " stored.");
        console.timeEnd("Store Post");
        return Dexie.Promise.resolve();
    }).catch(function (err) {
        console.error("Error adding post to queue: " + err);
    });
};

FrogDB.prototype.sendPosts = function (delayMsec) {
    var that = this;

    if (delayMsec > 0)
        this._sendChangesWaitMsec = delayMsec;

    console.log("Waiting " + this._sendChangesWaitMsec.toString() + " msec to send posts...");
    if (this._sendChangesDelay)
        this._sendChangesDelay.cancel();
    return (this._sendChangesDelay = new Defer(that._sendChangesWaitMsec)).promise.then(function () {
        return that._doSendPosts().catch(function () {
            that.sendPosts();
            return Dexie.Promise.reject();
        });
    });
};

FrogDB.prototype._getTxId = function () {
    // Need to use a string id because of broken safari indexedDB implementation
    return this._processInfo("lastTxId", 0, function (txId) {
        return txId + 1;
    }).then(function (txId) {
        return Dexie.Promise.resolve("tx" + ("0000000000" + txId.toString()).slice(-10));
    });
};

FrogDB.prototype._doSendPosts = function () {
    if (this._sendingChanges) {
        console.log("Sending posts BLOCKED.");
        return Dexie.Promise.reject();
    } else {
        this._sendingChanges = true;
    }
    var that = this;
    return this.dexie.posts.toCollection().first().then(function (post) {
        if (post)
            if (post.memoId < 0 && post.postType !== POST_CREATE_MEMO) {
                console.error("Can't send non-create post with temp id");
                return Dexie.Promise.reject();
            } else {
                return that._sendPost(post).then(function () {
                    return that.dexie.posts.delete(post.txId).then(function () {
                        that._sendingChanges = false;
                        return that._doSendPosts();
                    });
                });
            }
        else {
            console.log("No more posts.");
            that._sendingChanges = false;
            return Dexie.Promise.resolve();
        }
    }).catch(function (err) {
        that._sendingChanges = false;
        that._sendChangesWaitMsec = Math.min(that._sendChangesWaitMsec * 2, that.SEND_CHANGES_WAIT_MSEC_MAX);
        console.error("Error sending posts: " + err);
        return Dexie.Promise.reject();
    });
};
FrogDB.prototype._sendPost = function (post) {
    console.log("Sending " + post.txId + " (" + post.txGuid + ") to server...");
    var that = this;
    return this._getDataVersion().then(function (oldDataVersion) {
        var data;
        switch (post.postType) {
            case POST_CREATE_MEMO:
            /* falls through */
            case POST_EDIT_MEMO:
                data = {
                    postType: post.postType,
                    txId: post.txId,
                    txGuid: post.txGuid
                };
                data[KEY_NEW_EDIT_MEMO_TEXT] = post.newValue.text;
                data[KEY_MEMO_STAR] = post.newValue.star;
                data[KEY_MEMO_BUCKET] = post.newValue.bucket;
                data[KEY_NEW_EDIT_MEMO_PRIVATE] = post.newValue.priv;
                data[KEY_NEW_EDIT_MEMO_FRIENDS_CAN_EDIT] = post.newValue.friendsCanEdit;
                data[KEY_NEW_EDIT_MEMO_SYNC_BUCKETS] = post.newValue.syncBuckets;
                if (post.postType === POST_CREATE_MEMO) {
                    data[KEY_MEMO_STAR] = post.newValue.star;
                    data[KEY_PENDING_MEMO_TEMP_ID] = post.newValue.id;
                } else {
                    data.memoId = post.newValue.id;
                }
                break;
            default:
                data = post;
                break;
        }
        return new Ajax('/ajax_post.php').post(data).then(function (response) {
            if (response) {
                if (response[KEY_FORCE_DELETE_TX]) {
                    return that.dexie.posts.delete(post.txId).then(function () {
                        console.log("Post " + post.txId + " force deleted.");
                        return Dexie.Promise.resolve();
                    });
                } else if (response[KEY_POST_SUCCESS]) {
                    return that._processPostResponse(response, oldDataVersion).then(function () {
                        if (response[KEY_REDIRECT] && response[KEY_CONTENT_REQ])
                            navigateTo(response[KEY_CONTENT_REQ]);
                        return Dexie.Promise.resolve();
                    });
                }
                else {
                    throw new Error("ERROR: Server responded NOT OK for " + post.txId + " (" + post.txGuid + ").");
                }
            } else {
                throw new Error("[sendPosts]: Could not connect to server.");
            }
        });
    });
};

FrogDB.prototype._processPostResponse = function (response, oldLocalDataVersion) {
    console.log("Processing server response for " + response[KEY_POST_TX_ID] + " (" + response.txGuid + ") OK. Data Version: " + response.dataVersion.toString());
    var that = this;
    var memoId = response.memoId;
    var txId = response[KEY_POST_TX_ID];
    var postType = response[KEY_POST_TYPE];
    var post;
    var newDataVersion = response.dataVersion;
    var oldDataVersion = response.previousDataVersion;
    var noChangeGaps;
    var newMemo;

    markMemoSynced(memoId);

    return this.dexie.posts.get(txId).then(function (p) {
        post = p;
        return that.dexie.posts.delete(txId);
    }).then(function () {
        console.log("Post " + txId.toString() + " deleted.");
        noChangeGaps = oldLocalDataVersion === oldDataVersion;
        switch (postType) {
            case POST_CREATE_MEMO:
                var tempId = response[KEY_PENDING_MEMO_TEMP_ID];
                if (response[KEY_MEMO_DATA]) {
                    newMemo = new Memo(response[KEY_MEMO_DATA], true);
                    return that._replaceTempMemo(tempId, newMemo).then(function () {
                        replaceMemoDiv(tempId, newMemo);
                        return Dexie.Promise.resolve();
                    });
                } else {
                    throw new Error("New memo data not received.");
                }
                break;
            default:
                if (response[KEY_MEMO_DATA]) {
                    newMemo = new Memo(response[KEY_MEMO_DATA], true);
                    return that.dexie.memos.put(newMemo).then(function () {
                        replaceMemoDiv(newMemo.id, newMemo);
                        return Dexie.Promise.resolve();
                    });
                } else {
                    throw new Error("Data not received for changed memo.");
                }
                break;
        }
    }).then(function () {
        if (noChangeGaps)
            return that._upgradeQueries(post, oldDataVersion, newDataVersion);
        else
            return Dexie.Promise.resolve();
    }).catch(function () {
        console.error("Failed to delete post " + txId + ".");
    });
};
FrogDB.prototype._upgradeQueries = function (post, oldVersion, newVersion) {
    var that = this;
    return this.dexie.queries.toArray().then(function (queries) {
        return Dexie.Promise.all(queries.map(function (query) {
            if (query.isMemoQuery && query.isUpgradable && query.dataVersion === oldVersion) {
                //console.log("Upgrading query " + query.key + " to new version " + newVersion.toString());
                return that.dexie.queries.update(query.key, {dataVersion: newVersion});
            } else {
                if (query.isMemoQuery)
                    console.log("Query " + query.key + " can't be upgraded.");
                return Dexie.Promise.resolve();
            }
        }));
    });
};
FrogDB.prototype._updateMemoBasedOnPendingPost = function (memo, post) {
    switch (post.postType) {
        case POST_EDIT_MEMO:
            memo.text = post.newValue.text;
            memo.star = post.newValue.star;
            memo.setBucket(post.newValue.bucket);
            if (memo.isAuthor) {
                memo.priv = post.newValue.priv;
                memo.syncBuckets = post.newValue.syncBuckets;
                memo.friendsCanEdit = post.newValue.friendsCanEdit;
            }
            break;
        case POST_SET_STAR:
            memo.star = post.newValue;
            break;
        case POST_SET_ALARM:
            memo.alarm = post.newValue;
            break;
        case POST_SET_BUCKET:
            memo.setBucket(post.newValue);
            break;
    }
    memo._reset();
    return memo;
};
FrogDB.prototype._replaceTempMemo = function (oldId, newMemo) {
    var that = this;

    return that.dexie.memos.delete(oldId).then(function () {
        // Update any queries which have the old memo id
        return that.dexie.queries.toArray().then(function (queries) {
            return Dexie.Promise.all(queries.map(function (query) {
                var ids = query.data;
                if (query.isMemoQuery && tryReplaceInArray(ids, oldId, newMemo.id)) {
                    console.log("Updated query " + query.key + " for new memo.");
                    return that.dexie.queries.update(query.key, {data: ids});
                } else {
                    return Dexie.Promise.resolve();
                }
            }));
        }).then(function () {
            // Update the new memo based on pending posts that reference it
            // And update the posts themselves to replace the temp memo id
            return that.dexie.posts.where("memoId").equals(oldId).toArray().then(function (posts) {
                return that._updatePendingPosts(posts, oldId, newMemo);
            });
        });
    }).then(function (newMemo) {
        // now we can store the new memos since it has future posts applied to it
        return that.dexie.memos.put(newMemo).then(function () {
            // Store the new memo
            console.log("Added new memo " + newMemo.id.toString() + " to DB.");
            return Dexie.Promise.resolve();
        });
    }).catch(function (err) {
        console.error("Error replacing memo: " + err);
    });
};
FrogDB.prototype._updatePendingPosts = function (posts, oldId, newMemo) {
    // Recursive so the updates are applied in the same order the posts were created
    // Updates both the memo rec'd from the server so the remaining posts are applied on top of it,
    // but also the ids in the posts that have temp ids.
    var that = this;
    if (posts.length) {
        var post = posts[0];
        newMemo.synced = false;
        switch (post.postType) {
            case POST_CREATE_MEMO:
                // should never get here
                return this._updatePendingPosts(posts.slice(1), oldId, newMemo);
            case POST_EDIT_MEMO:
                console.log("Updating post edit of temp memo " + oldId.toString());
                post.newValue.id = newMemo.id;
                newMemo = that._updateMemoBasedOnPendingPost(newMemo, post);
                console.log("Updated pending post " + post.txId + " on memo created on server.");
                return that.dexie.posts.update(post.txId, {
                    memoId: newMemo.id,
                    newValue: post.newValue
                }).then(function () {
                    return that._updatePendingPosts(posts.slice(1), oldId, newMemo);
                });
            default:
                console.log("Updating post change of temp memo " + oldId.toString() + " post type " + post.postType.toString());
                newMemo = that._updateMemoBasedOnPendingPost(newMemo, post);
                console.log("Updated pending post " + post.txId + " on memo created on server.");
                return that.dexie.posts.update(post.txId, {memoId: newMemo.id}).then(function () {
                    return that._updatePendingPosts(posts.slice(1), oldId, newMemo);
                });
        }
    } else {
        return Dexie.Promise.resolve(newMemo);
    }
};
FrogDB.prototype._updateQueriesAfterMemoChange = function (post, currentFilterKey) {
    var that = this;
    // only do the current filter key synchronously
    return this.dexie.queries.get(currentFilterKey).then(function (query) {
        if (query)
            return that._updateQueryAfterMemoChange(post, query);
        else
            return Dexie.Promise.resolve();
    }).then(function () {
        that.dexie.queries.toArray().then(function (queries) {
            return Dexie.Promise.all(queries.map(function (query) {
                if (query.isMemoQuery && query.key !== currentFilterKey)
                    return that._updateQueryAfterMemoChange(post, query);
            }));
        });
        return Dexie.Promise.resolve();
    }).catch(function (err) {
        throw new Error("Error in _updateQueriesAfterMemoChange: " + err);
    });
};
FrogDB.prototype._updateQueryAfterMemoChange = function (post, query) {
    var filterObj = nav.getFilterReqFromFilterKey(query.key);
    var ids = query.data;
    var qChanged = false;
    var needsSort = query.needsSort;

    if (post.newMemo.isCompatible(filterObj)) {
        tryAddToArray(ids, post.newMemo.id);
        needsSort = true;
        qChanged = true;
    } else {
        qChanged = tryRemoveFromArray(ids, post.newMemo.id);
    }
    if (qChanged) {
        return this.dexie.queries.update(query.key, {data: ids, needsSort: needsSort}).then(function () {
            console.log("Updated query with key " + query.key);
            return Dexie.Promise.resolve();
        });
    } else {
        return Dexie.Promise.resolve();
    }
};

//==================================================================
// MISC
//==================================================================

FrogDB.prototype.getTempMemoId = function () {
    // Temp ids are negative
    return this._processInfo("lastTempMemoId", -1, function (id) {
        return id - 1;
    }).catch(function (err) {
        console.error("Error generating temp memo id: " + err);
    });
};

FrogDB.prototype.clearAll = function () {
    var that = this;
    return Dexie.Promise.all([
        that.setDataVersion(0),
        that.dexie.posts.clear(),
        that.dexie.memos.clear(),
        that.dexie.queries.clear(),
        that.dexie.info.clear()
    ]).catch(function () {
        console.error("[clearAll] Error clearing cache.");
    });
};

FrogDB.prototype._nukeDB = function (dbName) {
    return new Dexie(dbName).delete().then(function () {
        console.log("Database " + dbName + " deleted.");
    }).catch(function () {
        console.error("Failed to delete DB " + dbName);
    });
};
FrogDB.prototype._getSortComparator = function (bucket, oldestFirst) {
    return oldestFirst ? Memo.sortComparatorOldestFirst :
        bucket === BUCKET_JOURNAL ? Memo.sortComparatorJournal :
            Memo.sortComparator;
};