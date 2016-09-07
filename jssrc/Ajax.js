function Ajax(url) {
    this.url = url;
}
Ajax.prototype.get = function (data) {
    return this._ajax("POST", data);
};
Ajax.prototype.post = function (data) {
    return this._ajax("GET", data);
};
Ajax.prototype._ajax = function (mode, data) {
    var that = this;
    return new Dexie.Promise(function (resolve, reject) {
        data.appVersion = session.appVersion;
        try {
            $.ajax({
                type: mode,
                data: data,
                timeout: 15000, // 15 seconds to succeed or fail
                cache: false,
                success: function (response) {
                    if (response) {
                        if ("forceRestart" in response)
                            session.forceRestart = response.forceRestart;
                        if ("alert" in response)
                            alertBox.showAlert(response.alert, false);
                        if ("userId" in response)
                            session.setUserId(response.userId);
                        if ("validated" in response)
                            session.setIsValidated(response.validated);
                        if ("defaultBucket" in response)
                            nav.setDefaultBucket(response.defaultBucket);
                        if ("tag" in response)
                            nav.tag = response.tag;
                        if ("friend" in response)
                            nav.friend = response.friend;
                        if (response.shareId)
                            nav.shareId = response.shareId;
                        if ("dataVersion" in response)
                            frogDB.setDataVersion(response.dataVersion).then(function () {
                                resolve(response);
                            });
                        else
                            resolve(response);
                    } else {
                        resolve(response);
                    }
                },
                error: function () {
                    resolve(null);
                },
                url: that.url
            });
        } catch (e) {
            reject(e);
        }
    });
};
