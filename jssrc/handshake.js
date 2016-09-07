/** @var Defer _handshakeDefer */
var _handshakeDefer = null;
var HANDSHAKE_EVERY_MSEC = 3600000; // 1 hour
var _handshakeCount = 0;
function doServerHandshake() {
    var data = {};
    data[KEY_TIME_ZONE_OFFSET] = new Date().getTimezoneOffset();
    console.log("Attempting handshake...");
    return new Ajax("./ajax_handshake.php").get(data).then(function(response) {
        deferHandshake();
        if (response) {
            if (response.ok) {
                console.log("Handshake ok");
            } else {
                console.log("Handshake failed - not logged in.");
            }
            if (_handshakeCount++ % 8 === 0) // every 8 hours
                frogDB.prefetch(0, true);
            return Dexie.Promise.resolve();
        } else {
            return Dexie.Promise.reject();
        }
    });
}
function deferHandshake() {
    console.log("Setting handshake defer.");
    if (_handshakeDefer)
        _handshakeDefer.cancel();
    (_handshakeDefer = new Defer(HANDSHAKE_EVERY_MSEC)).promise.then(function() {
        deferHandshake();
        doServerHandshake();
    });
}

