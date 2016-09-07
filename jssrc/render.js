var TAG_REPLACE_PATTERN = "$1<a class=\"hashtag\" href='' onclick='filterByTag(\"$2\");return false;'>#$2</a>";
var SCREEN_NAME_REPLACE_PATTERN = "$1<a class=\"screen_name\" href='' onclick='navigate({\"contentReq\":42,\"friend\":\"$2\"}); return false;'>@$2</a>";
var tagRE = /(^|\s+)#([a-zA-Z0-9_]+)/g;
var screenNameRE = /(^|\s+)@([a-zA-Z0-9_]+)/g;
//var moreRE = /(?:[^|\n\r])====*[\n\r]([\s\S]*)/;
var moreRE = /<br>=====*<br>([\s\S]*)/;
var urlRE = /(^|\s)((https?:\/\/)[\w-]+(\.[\w-]+)+\.?(:\d+)?(\/\S*)?)/gi;
var memoRE = /<memo>(.+?)<\/memo>/g;
var displayDateRE = /<displaydate>(.+?)<\/displaydate>/g;

var MEMO_RENDER_TYPE_NORMAL = 0;
var MEMO_RENDER_TYPE_NO_TOOLS = 1;

var pageLoaded = -1;
var _numMemosLoaded = 0;
var lastQueryResultType = QUERY_RESULT_NONE;
function renderMemos(result) {
    var memoList = document.getElementById('memo_list');
    if (memoList !== null) {
        try {
            console.time("Memo List Render");
            if (result.page === 0) {
                clearAll(memoList, "memo");
                _numMemosLoaded = 0;
                domManager.contentContainer.scrollTop = 0;
            }

            showMemoAdvisory(result.resultType);
            lastQueryResultType = result.resultType;

            result.memos.forEach(function (m) {
                memoList.appendChild(renderMemo(m, MEMO_RENDER_TYPE_NORMAL));
                _numMemosLoaded++;
            });

            pageLoaded = result.page;

            removeMemoFooter();

            var footer = document.createElement("div");
            footer.id = 'memo_list_footer';
            footer.className = "memo_list_footer";
            var text;
            if (_numMemosLoaded === 0) {
                text = "No memos found.";
                if (canBroadenSearch())
                    text += " " + a("link_button", "Broaden Search", "broadenSearch();");
                footer.innerHTML = text;
            } else {
                text = _numMemosLoaded.toString() + " memo" + (_numMemosLoaded === 1 ? "" : "s") + " found.";
                if (result.more)
                    text += " " + a("link_button", "Load more...", "loadMemos(true);");
                footer.innerHTML = text;
            }
            memoList.appendChild(footer);
            console.timeEnd("Memo List Render");
            showTargetMemo();
        }
        catch (ex) {
            memoList.innerHTML = '<h2>Error loading memos.</h2><p>' + ex.toString() + '</p>';
        }
    }
}

function render(content) {

    var contentKey = content[KEY_CONTENT_KEY];

    if (contentKey === CONTENT_KEY_LOGOUT) {
        session.setUserId(0);
        session.goToIntro();
        return Dexie.Promise.resolve();
    }

    nav.push();
    nav.tag = "";
    nav.friend = "";
    nav.shareId = 0;
    if (nav.contentKey !== CONTENT_KEY_HOME)
        nav.targetMemo = 0;

    var html = postProcess(content[KEY_CONTENT]);
    var script = content[KEY_SCRIPT];

    updateUI(html);

    if (script)
        if (typeof script === "function")
            script();
        else
            eval(script); // jshint ignore:line

    var suppressTips = content[KEY_TIPS_SUPPRESSED];
    tipManager.suppressTips(suppressTips);

    if (content.needsValidationSuppressed !== undefined)
        if (content.needsValidationSuppressed === true)
            domManager.needsValidation.classList.add("suppressed");
        else if (content.needsValidationSuppressed === false)
            domManager.needsValidation.classList.remove("suppressed");

    googleAnalytics.track(nav.contentKey);
    session.informUserActionComplete();

    return Dexie.Promise.resolve();
}
function postProcess(content) {
    return content.replace(displayDateRE, function (match, capture) {
        return replaceTimestamp(capture, false);
    }).replace(memoRE, function (match, capture) {
        // Not used for main memo list
        var renderType = nav.contentKey === CONTENT_KEY_HOME ? MEMO_RENDER_TYPE_NORMAL : MEMO_RENDER_TYPE_NO_TOOLS;
        var memoJSON = JSON.parse(capture);
        return renderMemo(new Memo(memoJSON, true), renderType).outerHTML;
    });
}
function updateUI(content) {

    // Render content
    domManager.contentPanel.innerHTML = content;

    if (nav.contentKey === CONTENT_KEY_HOME)
        activateQuickEntry(false, true);

    if (nav.contentKey === CONTENT_KEY_HOME)
        domManager.contentFrame.classList.add('home');
    else
        domManager.contentFrame.classList.remove('home');

    var textArea;
    if ((textArea = document.getElementById('quick_entry_textarea')) !== null) {
        textArea.onkeypress = function (e) {
            if ((e.keyCode === 13 || e.keyCode === 10) && !e.shiftKey) {
                createMemoFromQE();
                if (e.preventDefault)
                    e.preventDefault();
                return false;
            }
        };
        var submitButton = document.getElementById('quick_entry_submit');
        if (submitButton !== null) {
            textArea.onkeyup = function () {
                if (this.value.length)
                    submitButton.classList.remove('disabled');
                else
                    submitButton.classList.add('disabled');
            };
        }
        textArea.onfocus = function () {
            activateQuickEntry(true, false);
        };
    }
    if ((textArea = document.getElementById('memo_text_area')) !== null) {
        if (textArea.hasAttribute("autofocus"))
            textArea.focus();
    }
    Array.prototype.slice.call(document.getElementsByTagName('form')).forEach(function (form) {
        form.onsubmit = function (e) {
            e.preventDefault();
            processForm(parseFormInput(form));
            return false; // prevent the form from submitting
        };
    });

    domManager.contentContainer.scrollTop = 0;
    domManager.menuBar.className = domManager.menuBar.className.replace(/content[0-9]+/, "content" + nav.contentKey.toString());
}
function updateListHeader() {
    var buttonsHtml = "";
    var req = nav.getFilterReq();
    if (req.specialSearch && req.specialSearch !== SPECIAL_SEARCH_NONE) {
        var specialCaption = "";
        switch (req.specialSearch) {
            case SPECIAL_SEARCH_SHARED:
                specialCaption = "Shared Memos";
                break;
            case SPECIAL_SEARCH_STARS:
                specialCaption = "Stars Only";
                break;
            case SPECIAL_SEARCH_UNSTARRED:
                specialCaption = "Unstarred";
                break;
            case SPECIAL_SEARCH_OLD:
                specialCaption = "Old &gt; 30 Days";
                break;
            case SPECIAL_SEARCH_EDITED:
                specialCaption = "Edited";
                break;
            case SPECIAL_SEARCH_BY_ME:
                specialCaption = 'By Me';
                break;
            case SPECIAL_SEARCH_NOT_BY_ME:
                specialCaption = "Not By Me";
                break;
            case SPECIAL_SEARCH_PRIVATE:
                specialCaption = "Private";
                break;
            case SPECIAL_SEARCH_ALARM:
                specialCaption = "Alarms Set";
                break;
            case SPECIAL_SEARCH_OLDEST_FIRST:
                specialCaption = "Oldest First";
                break;
            case SPECIAL_SEARCH_HIDDEN:
                specialCaption = "Hidden";
                break;
        }
        if (specialCaption.length)
            buttonsHtml += a("dismissable_button special_search", specialCaption, "specialSearch(SPECIAL_SEARCH_NONE); domManager.vanish(this);");
    }
    if (req.filterText)
        buttonsHtml += a("dismissable_button filter_text", req.filterText.split(" ").map(function (w) {
            return "&quot;" + w + "&quot;";
        }).join(" & "), "filterByText(''); domManager.vanish(this);");

    // don't use req
    if (domManager.searchBox !== null) {
        if (document.activeElement !== domManager.searchBox)
            domManager.searchBox.value = nav.getFilterTextAsEntered();
    }

    if (req.screenName) {
        buttonsHtml += a("dismissable_button filter_op screen_name_filter_op", req.screenNameTo ? "To:" : "From:", "cycleFilterScreenNameTo();");
        buttonsHtml += a("dismissable_button filter_screen_name", "@" + req.screenName, "filterByScreenName(''); domManager.vanish(this); domManager.vanishByClass('screen_name_filter_op');");
    }

    if (req.filterTags) {
        var tags = req.filterTags.split('+');
        if (tags.length > 1)
            switch (req.filterTagsOp) {
                case FILTER_TAGS_OP_ALL:
                    buttonsHtml += a("dismissable_button filter_op tag_filter_op", "All Of:", "cycleFilterTagOp();");
                    break;
                case FILTER_TAGS_OP_ANY:
                    buttonsHtml += a("dismissable_button filter_op tag_filter_op", "Any Of:", "cycleFilterTagOp();");
                    break;
            }
        for (var i in tags)
            buttonsHtml += a("dismissable_button filter_tag", "#" + tags[i], "filterRemoveTag('" + tags[i] + "'); domManager.vanish(this); if (domManager.elementCount('filter_tag') === 2) { domManager.vanishByClass('tag_filter_op'); }");
    }

    domManager.headerFilterButtons.innerHTML = buttonsHtml;

    if (buttonsHtml.length === 0) {
        domManager.headerFilterText.textContent = bucketText[req.bucket];
        domManager.headerFilterClear.classList.remove("available");
        if (!isMobile)
            domManager.filterBarCaption.style.display = "inline-block";
    } else {
        domManager.headerFilterText.textContent = "";
        domManager.filterBarCaption.style.display = "none";
        domManager.headerFilterClear.classList.add("available");
    }

    var bucketClass = "bucket" + req.bucket.toString();
    domManager.headerFilterIcon.className = "header_filter_icon img_" + bucketClass;
    domManager.filterBar.className = "filter_bar btn_container " + bucketClass;

    return false;
}
function showMemoAdvisory(resultType) {
    var advisoryDiv = document.getElementById("memo_advisory");
    var isErr = false;
    var isWarning = false;
    var text = "";
    switch (resultType) {
        case QUERY_RESULT_ERROR:
            text = "Error retrieving memos.";
            isErr = true;
            break;
        case QUERY_RESULT_SYNTHETIC:
        /* falls through */
        case QUERY_RESULT_LOCAL_FALLBACK:
            text = "Could not connect to Memofrog server. Results may be incomplete. " + a("link_button", "Try Again.", "navigate(null);");
            isWarning = true;
            break;
        case QUERY_RESULT_FAIL:
            text = "Could not connect to Memofrog server. " + a("link_button", "Try Again.", "navigate(null);");
            isErr = true;
            advisoryDiv.classList.add("error");
            advisoryDiv.classList.remove("warning");
            break;
    }
    advisoryDiv.innerHTML = text;
    if (isErr) {
        advisoryDiv.classList.remove("warning");
        advisoryDiv.classList.add("error");
    } else if (isWarning) {
        advisoryDiv.classList.add("warning");
        advisoryDiv.classList.remove("error");
    } else {
        advisoryDiv.classList.remove("warning");
        advisoryDiv.classList.remove("error");
    }
}

//noinspection JSUnusedGlobalSymbols
function appendTag(tag) {
    var textArea = document.getElementById('edit_memo_text_area');
    if (textArea !== null) {
        var cursorPosition = textArea.selectionStart;
        var text = textArea.value;
        var startSlice = text.slice(0, cursorPosition);
        var endSlice = text.slice(cursorPosition);
        if ((startSlice.length) && !charIsWhitespace(startSlice.charAt(startSlice.length - 1))) {
            startSlice += " ";
            cursorPosition++;
        }
        if (endSlice.length && !charIsWhitespace(endSlice.charAt(0))) {
            endSlice = " " + endSlice;
        } else {
            endSlice = " ";
        }
        cursorPosition += tag.length + 2;
        textArea.value = startSlice + "#" + tag + endSlice;
        textArea.selectionStart = cursorPosition;
        textArea.selectionEnd = cursorPosition;
        textArea.focus();
    }
}

function removeMemoFooter() {
    var footer = document.getElementById("memo_list_footer");
    if (footer !== null)
        footer.parentNode.removeChild(footer);
}

function clearAll(container, className) {
    var els = container.getElementsByClassName(className);
    while (els[0])
        container.removeChild(els[0]);
}

function expandHiddenText(invokingElement, expand) {
    var container = invokingElement.parentElement;
    var textDiv = container.getElementsByClassName("expandable_text")[0];
    if (expand)
        $(textDiv).slideDown();
    else
        $(textDiv).slideUp();
}

function charIsWhitespace(ch) {
    return (ch === ' ') || (ch === '\t') || (ch === '\n');
}