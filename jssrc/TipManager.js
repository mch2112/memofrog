var tipManager = new TipManager();
function TipManager() {
    this._tipDiv = null;
    this._tipTextDiv = null;
    this._lastTipId = 0;
    this._hasText = false;
}
TipManager.prototype.start = function() {
    this._tipDiv = document.getElementById("tips");
    this._tipTextDiv = document.getElementById('tip_text');
    this.showNextTip();
};
TipManager.prototype.setTipText = function(text) {
    this._tipTextDiv.innerHTML = text;
    if (text.length) {
        this._hasText = true;
        this._tipDiv.classList.remove("dismissed");
    }
};
TipManager.prototype.dismissTips = function(dismiss, showAlertText) {
    if (dismiss)
        this._tipDiv.classList.add("dismissed");
    else
        this._tipDiv.classList.remove("dismissed");
    if (showAlertText)
        alertBox.showAlert('Use the <span class="inline_icon account"></span><a onclick="navigate({\'contentReq\':' + CONTENT_KEY_ACCOUNT.toString() + '}); return false;" href="">Account screen</a> for tips settings.', false);
};
TipManager.prototype.suppressTips = function(suppress) {
    if (suppress)
        this._tipDiv.classList.add("suppressed");
    else if (this._hasText)
        this._tipDiv.classList.remove("suppressed");
};
TipManager.prototype.showNextTip = function() {
    var that = this;
    var data = {};
    data[KEY_TIP_ID] = this._lastTipId;
    new Ajax("/ajax_get_tip.php").get(data).then(function(response) {
        if (response) {
            that._lastTipId = response[KEY_TIP_ID];
            if (KEY_TIPS_DISABLED in response) {
                that.dismissTips(response[KEY_TIPS_DISABLED]);
                if (!response[KEY_TIPS_DISABLED])
                    that.setTipText(response[KEY_TIP]);
            }
        } else {
            console.error("Failed to get tip.");
        }
    });
};