var isMobile = true;

function onStandardReady() {}

function replaceTimestamp(dtStr, inMemo) {
    var localMoment = moment(moment.utc(dtStr).subtract(10, 's').toDate());
    if (inMemo) {
        var age = Date.now() / 1000 - localMoment.unix();
        var pattern;
        if (age < 3600) // 1 hour
            pattern = "";
        else if (age < 43200)  // 12 hours
            pattern = "h:mma";
        else if (age < 432000)  // 5 days
            pattern = "ddd [at] h:mma";
        else
            pattern = "ddd MMM Do";

        var str = (pattern.length > 0) ? localMoment.format(pattern) : localMoment.fromNow();

        return "<span class='nobr'>" + str + "</span>";
    }
    else {
        return "<span class='nobr'>" + localMoment.format("ddd MMM Do h:mma") + "</span> <span class='nobr'>(" + localMoment.fromNow(false) + ")</span>";
    }
}

function adjustForScrollBars() {

}