var session = new Session();

var searchTimeoutId = 0;

function Session() {
    //noinspection JSUnresolvedVariable
    this.useGA = __useGA;
    //noinspection JSUnresolvedVariable
    this.appVersion = __appVersion;

    this.contentCache = {};
    this.forceRestart = false;
    this._isValidated = false;
    this._userId = 0;

    //noinspection JSUnresolvedVariable
    if (!window.indexedDB) {
        this.failed = true;
        window.location = "/old";
    } else {
        this.failed = false;
    }
}
Session.prototype.start = function () {
    if (this.failed) {
        return Dexie.Promise.reject();
    } else {
        doServerHandshake();

        domManager.start();
        eventManager.start();

        nav.restore();

        onStandardReady();
        onTouchReady();

        var that = this;
        return frogDB.start().then(function () {
            if (that.useGA)
                googleAnalytics.start();
            return navigate().then(function () {
                adjustForScrollBars();
                console.timeEnd("Page Load");
                return Dexie.Promise.resolve();
            });
        });
    }
};
Session.prototype.informUserActionComplete = function() {
    if (this.forceRestart)
        this._restart();
};
Session.prototype.isValidated = function () {
    return this._isValidated;
};
Session.prototype.isLoggedIn = function () {
    return this._userId > 0;
};
Session.prototype.getUserId = function () {
    return this._userId;
};
Session.prototype.setUserId = function (userId) {
    if (this._userId !== userId) {
        console.log("Set user id to " + userId.toString());
        this.contentCache = {};
        this._userId = userId;
        this._setValidationInDOM();
        if (this.isLoggedIn()) {
            doServerHandshake();
            domManager.menuBar.classList.remove("disabled");
        }
    }
};

Session.prototype.setIsValidated = function (validated) {
    if (this._isValidated !== validated) {
        console.log("Validated: " + validated.toString());
        this._isValidated = validated;
        this._setValidationInDOM();
    }
};
Session.prototype._setValidationInDOM = function () {
    if (this.isValidated() || !this.isLoggedIn())
        domManager.needsValidation.classList.add("dismissed");
    else
        domManager.needsValidation.classList.remove("dismissed");
};
Session.prototype.isOnline = function () {
    return window.navigator.onLine;
};
Session.prototype.isProduction = function () {
    return location.protocol === 'https:';
};
Session.prototype.goToIntro = function() {
    window.location.replace('/intro');
};
Session.prototype._restart = function () {
    nav.persist();
    window.location.reload();
};