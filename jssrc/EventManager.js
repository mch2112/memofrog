var eventManager = new EventManager();

function EventManager() {
    this._clickedElement = null;
}
EventManager.prototype.start = function() {
    var that = this;
    domManager.listHeader.onclick = function () {
        $(domManager.contentContainer).animate({scrollTop: 0}, 300);
    };
    window.addEventListener("popstate", function (e) {
        if (e.state)
            nav.handlePop(e.state);
    });
    domManager.contentFrame.onmousedown = function (e) {
        that._clickedElement = e.target || e.srcElement;
    };
    window.addEventListener('contextmenu', function (e) {
        if (e.preventDefault) {
            var a = that._clickedLink();
            if (a && !a.classList.contains('external') && session.isProduction())
                e.preventDefault();
        }
    }, false);
    document.onkeyup = function (e) {
        switch (e.keyCode) {
            case 27: // ESC
                activateQuickEntry(false, false);
                break;
            case 35: // END
                domManager.contentContainer.scrollTop = 100000;
                break;
            case 36: // HOME
                domManager.contentContainer.scrollTop = 0;
                break;
            case 70: // F
                if (e.altKey) navigateTo(CONTENT_KEY_FRIENDS); else return;
                break;
            case 78: // N
                if (e.altKey) navigateTo(CONTENT_KEY_NEW_MEMO); else return;
                break;
            case 81: // Q
                if (e.altKey) {
                    var qeBox = document.getElementById("quick_entry_textarea");
                    if (qeBox !== null)
                        qeBox.focus();
                } else {
                    return;
                }
                break;
            case 49: // 1
                if (e.altKey) filterByBucket(BUCKET_EVERYTHING); else return;
                break;
            case 50: // 2
                if (e.altKey) filterByBucket(BUCKET_ALL_ACTIVE); else return;
                break;
            case 51: // 3
                if (e.altKey) filterByBucket(BUCKET_HOT_LIST); else return;
                break;
            case 52: // 4
                if (e.altKey) filterByBucket(BUCKET_B_LIST); else return;
                break;
            case 53: // 5
                if (e.altKey) filterByBucket(BUCKET_REFERENCE); else return;
                break;
            case 54: // 6
                if (e.altKey) filterByBucket(BUCKET_JOURNAL); else return;
                break;
            case 55: // 7
                if (e.altKey) filterByBucket(BUCKET_DONE); else return;
                break;
            case 56: // 8
                if (e.altKey) filterByBucket(BUCKET_TRASH); else return;
                break;
            default:
                return;
        }
        e.preventDefault();
    };
};
EventManager.prototype._clickedLink = function() {
    function findLinkElement(element) {
        if (!element)
            return null;
        if (element.tagName === "A")
            return element;
        return findLinkElement(element.parentElement);
    }
    return findLinkElement(this._clickedElement);
};