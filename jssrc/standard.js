var isMobile = false;

function onStandardReady() {
    var searchBox = document.getElementById('search-box');
    if (searchBox !== null) {
        searchBox.onkeyup = function (e) {
            if (e.keyCode === 13) {
                if (searchTimeoutId > 0)
                    clearTimeout(searchTimeoutId);
                filterByText(searchBox.value);
            }
            else {
                if (searchTimeoutId > 0)
                    clearTimeout(searchTimeoutId);

                searchTimeoutId = setTimeout(function () {
                    searchTimeoutId = 0;
                    filterByText(searchBox.value);
                }, 300);
            }
        };
        searchBox.onsearch = function () {
            filterByText(searchBox.value);
        };
    }
}
function replaceTimestamp(dtSTr, inMemo) {
    var localMoment = moment(moment.utc(dtSTr).subtract(10, 's').toDate());
    if (inMemo) {
        var age = Date.now() / 1000 - localMoment.unix();
        var pattern;
        if (age < 43200)  // 12 hours
            pattern = "h:mma";
        else if (age < 432000)  // 5 days
            pattern = "dddd MMM Do [at] h:mma";
        else
            pattern = "ddd MMM Do";

        var str = (pattern.length > 0) ? localMoment.format(pattern) : localMoment.fromNow();
        return "<span class='nobr'>" + str + "</span><br><span class='nobr'>" + localMoment.fromNow(false) + "</span>";
    }
    else {
        return "<span class='nobr'>" + localMoment.format("ddd MMM Do h:mma") + "</span> <span class='nobr'>(" + localMoment.fromNow(false) + ")</span>";
    }
}
var _scrollBarAdjustTries = 0;
function adjustForScrollBars() {
    if (++_scrollBarAdjustTries < 10) {
        console.log("adjust for scroll bars...");
        var cp = domManager.contentPanel;
        if (cp.clientHeight) {
            var cc = domManager.contentContainer;
            var adj = ((cc.offsetWidth - cc.clientWidth) / 2);
            if (adj > 0) {
                var adjStr = adj.toString() + "px";
                cp.style.marginLeft = adjStr;
                cp.style.marginRight = "-" + adjStr;
            }
        } else {
            console.log("trying again, cp height is " + cp.clientHeight.toString());
            setTimeout(adjustForScrollBars, 100);
        }
    }
}