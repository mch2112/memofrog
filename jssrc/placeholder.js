var _placeholder_cache = {};

function Placeholder(contentKey, placeholderHtml, cache, useWarningStyles) {
    this._contentKey = contentKey;
    this._cache = cache;
    this._txId = getGUID();
    this._placeholderHtml = placeholderHtml;
    this._failureText = "";
    this._placeholderDiv = null;
    this._data = {};
    this._data[KEY_PLACEHOLDER_KEY] = contentKey;
    this._data[KEY_PLACEHOLDER_TX_ID] = this._txId;
    this._postProcess = postProcess;
    this._useWarningStyles = useWarningStyles;
}
Placeholder.prototype.addData = function(_dataKey, _dataValue) {
    this._data[_dataKey] = _dataValue;
};
Placeholder.prototype.setPostProcessFx = function(func) {
    this._postProcess = func;
};
Placeholder.prototype.getHtml = function() {
    return divId(this._txId, "ajax_placeholder", this._placeholderHtml);
};
Placeholder.prototype._div = function() {
    this._placeholderDiv = this._placeholderDiv || document.getElementById(this._txId);
    return this._placeholderDiv;
};
Placeholder.prototype.setFailureText = function(_failureText) {
    this._failureText = _failureText;
};
Placeholder.prototype._succeed = function(content) {
    if (this._cache)
        _placeholder_cache[this._contentKey] = content;
    this._div().innerHTML = this._postProcess(content);
    return Dexie.Promise.resolve();
};
Placeholder.prototype._fail = function() {
    if (this._useWarningStyles) {
        if (this._failureText.length > 0)
            this._div().innerHTML = p('placeholder fail', this._failureText);
        else
            this._div().innerHTML = "";
        this._div().classList.add("advisory");
        this._div().classList.add("warning");
    } else {
        this._div().innerHTML = this._failureText;
    }
    return Dexie.Promise.reject();
};
Placeholder.prototype.execute = function() {
    if (this._cache) {
        var prevResponse = _placeholder_cache[this._contentKey];
        if (prevResponse) {
            return this._succeed(prevResponse);
        }
    }
    var that = this;
    return new Ajax("/ajax_placeholder_content.php").get(this._data).then(function(response) {
        if (response && response.ok && response[KEY_PLACEHOLDER_TX_ID] === that._txId)
            return that._succeed(response[KEY_CONTENT]);
        else
            return that._fail();
    }).catch(function() {
        return that._fail();
    });
};

function suggestedTagsPostProcess(input) {
    var tags = input.map(function(t) {
        return a("suggested_tag hashtag", "#" + t, "appendTag('" + t + "');");
    });
    return div("suggested_tags", (isMobile ? "" : "Tag Ideas: ") + tags.join("&nbsp; "));
}