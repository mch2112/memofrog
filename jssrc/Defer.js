function Defer(delayInMsec, cb) {
    var that = this;
    this.cancelled = false;
    this.active = true;
    //noinspection JSUnusedGlobalSymbols
    this.fired = false;
    //noinspection JSUnresolvedVariable
    this.promise = new Dexie.Promise(function(resolve, reject) {
        that._timeoutId = setTimeout(function() {
            that.active = false;
            if (that.cancelled) {
                reject();
            } else {
                that.fired = true;
                if (cb)
                    cb();
                resolve();
            }
        }, delayInMsec);
    });
}
Defer.prototype.cancel = function() {
    this.cancelled = true;
    this.active = false;
};