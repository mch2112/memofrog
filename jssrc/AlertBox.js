var alertBox = new AlertBox();

function AlertBox() {
    this.STANDARD_ALERT_TIME = 4000;
    this.LOADING_MAX_TIME = 60000;
    this.LOADING_DEFER_TIME = 1500;
    this._loadingAlertDelay = null;
    this._alertTimeout = null;
    this._alertBox = null;
    this._alertBoxText = null;
    this._loadingAlertVisible = false;
}
AlertBox.prototype.start = function() {
    this._alertBox = document.getElementById("outer_alert_box");
    this._alertBoxText = document.getElementById("alert_box_text");
    this._loadingIcon = document.getElementById("wt_icn_anim");
};
AlertBox.prototype.deferLoadingAlert = function () {
    var that = this;
    if (this._loadingAlertDelay)
        this._loadingAlertDelay.cancel();
    (this._loadingAlertDelay = new Defer(this.LOADING_DEFER_TIME)).promise.then(function() {
        that.showAlert('Loading...', true);
    });
};
AlertBox.prototype.cancelLoadingAlert = function () {
    if (this._loadingAlertVisible)
        this.clearAlert();
    else if (this._loadingAlertDelay)
        this._loadingAlertDelay.cancel();
};
AlertBox.prototype.showAlert = function(message, isLoadingMessage) {
    if (message.length) {
        var that = this;
        this._alertBoxText.innerHTML = message;
        if (isLoadingMessage)
            this._loadingIcon.classList.add("loading");
        else
            this._loadingIcon.classList.remove("loading");
        this._alertBox.classList.add("visible");
        this._loadingAlertVisible = isLoadingMessage;
        if (this._alertTimeout)
            this._alertTimeout.cancel();
        (this._alertTimeout = new Defer(isLoadingMessage ? this.LOADING_MAX_TIME : this.STANDARD_ALERT_TIME)).promise.then(function () {
            that._loadingAlertVisible = false;
            that._alertTimeout = null;
            that._alertBox.classList.remove("visible");
        });
    }
};
AlertBox.prototype.clearAlert = function() {
    if (this._alertTimeout)
        this._alertTimeout.cancel();
    this._alertTimeout = null;
    this._loadingAlertVisible = false;
    this._alertBox.classList.remove("visible");
    console.log("ALERT CLEARED.");
};