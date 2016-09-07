//noinspection JSUnusedGlobalSymbols
var isTouch = true;

var hammer = null;
var swipeDiv = null;

function onTouchReady() {
    document.addEventListener("touchstart", function () {
    }, true); // allow :active styles to work
    //initSwipeSupport();
    new FastClick(document.body);
}
//noinspection JSUnusedGlobalSymbols
function initSwipeSupport() {
    var cc = domManager.contentContainer;
    $(cc).on('touchstart', function (e) {
        swipeDiv = e.target;
    });
    hammer = new Hammer(cc);
    //noinspection JSCheckFunctionSignatures
    hammer.on("swipeleft", function () {
        while (swipeDiv !== null) {
            if (swipeDiv.classList.contains('memo'))
                break;
            if (swipeDiv.classList.contains('tag'))
                break;
            swipeDiv = swipeDiv.parentElement;
        }

        if (swipeDiv !== null) {
            var id = swipeDiv.id;

            var data = null;
            if (id.slice(0, 3) === "tag") {
                var tag = parseInt(id.slice(3));
                data = {'contentReq': CONTENT_KEY_TAG, 'tag': tag};
            }
            else if (id.slice(0, 4) === "memo") {
                var memoId = parseInt(id.slice(4));
                data = {'contentReq': CONTENT_KEY_MEMO_DETAILS, 'targetMemo': memoId};
            }
            if (data !== null) {
                var ml = cc.style.marginLeft;
                var mr = cc.style.marginRight;
                var animationDone = false;
                var navigationDone = false;

                navigate(data).then(function () {
                    navigationDone = true;
                    if (animationDone) {
                        cc.style.marginLeft = ml;
                        cc.style.marginRight = mr;
                    }
                });
                $(cc).animate({marginLeft: "-=350px", marginRight: "+=350px"}, 300, function () {
                    animationDone = true;
                    if (navigationDone) {
                        cc.style.marginLeft = ml;
                        cc.style.marginRight = mr;
                    }
                });
            }
        }
    });
}
