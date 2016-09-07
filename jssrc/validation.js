var KEY_VALIDATION = 'key_validation';
var KEY_VALIDATION_TYPE = 'validationType';
var KEY_VALIDATION_SOURCE = 'key_validation_source';
var KEY_VALIDATION_TARGET = 'key_validation_target';
var KEY_VALIDATION_MESSAGE = 'key_validation_message';
var KEY_VALIDATION_ERROR = 'key_validation_error';
var KEY_VALIDATION_WARNING = 'key_validation_warning';
var KEY_SUBMIT_OK = 'key_submit_ok';
var KEY_SUBMIT_BUTTON = 'key_submit_button';

var VALIDATION_TYPE_NON_ZERO_LENGTH_ANY_INPUT = 50;
var VALIDATION_TYPE_NON_ZERO_LENGTH_ALL_INPUTS = 60;
var pendingValidationTimeoutId = 0;

function initValidation(type, ids, extraSubmitPredicate) {
    ids.forEach(function (id) {
        var e = document.getElementById(id);
        if (e !== null) {
            switch (type) {
                case VALIDATION_TYPE_NON_ZERO_LENGTH_ANY_INPUT:
                    e.oninput = function () {
                        updateSubmitButton(e.value.length > 0);
                    };
                    break;
                case VALIDATION_TYPE_NON_ZERO_LENGTH_ALL_INPUTS:
                    e.oninput = function () {
                        updateSubmitButton(allHaveText(ids));
                    };
                    break;
                default:
                    e.oninput = function () {
                        e.dataset.touched = true;
                        doValidation(type, ids, extraSubmitPredicate);
                    };
                    break;
            }
        }
    });
}
function doValidation(type, ids, extraSubmitPredicate) {
    if (pendingValidationTimeoutId > 0) {
        clearTimeout(pendingValidationTimeoutId);
    }
    pendingValidationTimeoutId = setTimeout(function () {
        var data = {};
        data[KEY_VALIDATION_TYPE] = type;
        for (var i = 0, len = ids.length; i < len; i++) {
            data[ids[i]] = document.getElementById(ids[i]).value.trim();
        }
        new Ajax("ajax_get_validation.php").get(data).then(function(response) {
            if (response) {
                if (response[KEY_VALIDATION]) {
                    var validation = response[KEY_VALIDATION];
                    for (var i = 0, len = validation.length; i < len; i++) {
                        var fb = validation[i];
                        var source = document.getElementById(fb[KEY_VALIDATION_SOURCE]);
                        if (source !== null && source.dataset.touched) {
                            var msg = fb[KEY_VALIDATION_MESSAGE];
                            var target = document.getElementById(fb[KEY_VALIDATION_TARGET]);
                            if (msg.length) {
                                target.innerHTML = msg;
                                target.classList.add("visible");
                            } else {
                                target.classList.remove("visible");
                            }
                            if (fb[KEY_VALIDATION_WARNING] !== null) {
                                if (fb[KEY_VALIDATION_WARNING]) {
                                    source.classList.add("warning");
                                    target.classList.add("warning");
                                } else {
                                    source.classList.remove("warning");
                                    target.classList.remove("warning");
                                }
                            }
                            if (fb[KEY_VALIDATION_ERROR] !== null) {
                                if (fb[KEY_VALIDATION_ERROR]) {
                                    source.classList.add("error");
                                    target.classList.add("error");
                                } else {
                                    source.classList.remove("error");
                                    target.classList.remove("error");
                                }
                            }
                        }
                    }
                }

                if (!(KEY_SUBMIT_OK in response) || response[KEY_SUBMIT_OK] === true) {
                    if (extraSubmitPredicate)
                        updateSubmitButton(extraSubmitPredicate());
                    else
                        updateSubmitButton(true);
                }
                else {
                    updateSubmitButton(false);
                }
            } else {
                updateSubmitButton(true);
            }
        }).catch(function() {
            updateSubmitButton(true);
        });
    }, 300);
}
function updateSubmitButton(enable) {
    var submitButton = document.getElementById(KEY_SUBMIT_BUTTON);
    if (submitButton !== null) {
        submitButton.disabled = !enable;
        if (submitButton.disabled)
            submitButton.classList.add("disabled");
        else
            submitButton.classList.remove("disabled");
    }
}
function allHaveText(ids) {
    for (var i = 0, len = ids.length; i < len; i++) {
        if (document.getElementById(ids[i]).value.length === 0)
            return false;
    }
    return true;
}