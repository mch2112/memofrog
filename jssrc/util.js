//noinspection JSUnusedGlobalSymbols
function parseQuery(queryStr) {
    var query = {};
    var a = queryStr.substr(1).split('&');
    for (var i = 0; i < a.length; i++) {
        var b = a[i].split('=');
        query[decodeURIComponent(b[0])] = decodeURIComponent(b[1] || '');
    }
    return query;
}
function    getGUID() {
    var d = new Date().getTime();
    //noinspection JSUnresolvedVariable
    if (window.performance && typeof window.performance.now === "function") {
        d += window.performance.now(); //use high-precision timer if available
    }
    //noinspection SpellCheckingInspection
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        var r = (d + Math.random() * 16) % 16 | 0;
        d = Math.floor(d / 16);
        return (c == 'x' ? r : (r & 0x3 | 0x8)).toString(16);
    });
}
function tryReplaceInArray(arr, oldValue, newValue) {
    var idx = arr.indexOf(oldValue);
    if (idx >= 0) {
        arr[idx] = newValue;
        return true;
    } else {
        return false;
    }
}
function tryRemoveFromArray(arr, value) {
    var idx = arr.indexOf(value);
    if (idx > -1) {
        arr.splice(idx, 1);
        return true;
    } else {
        return false;
    }
}
function tryAddToArray(arr, value) {
    if (arr.indexOf(value) < 0) {
        arr.unshift(value);
        return true;
    } else {
        return false;
    }
}
function utcDateString() {
    return moment.utc().format("YYYY-MM-DD HH:mm:ss");
}
function logSeparator() {
    console.log("==================================================");
}
