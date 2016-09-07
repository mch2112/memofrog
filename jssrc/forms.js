var qeActivated = false;
var qeToolbarDefaultDisplayStyle = null;

function processForm(data) {
    navigate(data);
}
function parseFormInput(form) {
    var data = {};
    for (var i = 0; i < form.length; i++) {
        var input = form[i];
        if (input.name && input.name.length) {
            if (input.type === "radio" || input.type === "checkbox") {
                data[input.name] = input.checked;
            } else {
                data[input.name] = input.value.trim();
            }
        }
    }
    return data;
}
function activateQuickEntry(activate, force) {
    if (nav.contentKey === CONTENT_KEY_HOME) {
        var container = document.getElementById('quick_entry_container');
        if (container !== null) {
            if (force || qeActivated != activate) {
                var textArea = document.getElementById('quick_entry_textarea');
                var qeToolbar = document.getElementById('quick_entry_toolbar');
                if (qeToolbarDefaultDisplayStyle === null)
                    qeToolbarDefaultDisplayStyle = qeToolbar.style.display;
                if (activate) {
                    qeActivated = true;
                    container.classList.add('activated');
                    qeToolbar.style.display = qeToolbarDefaultDisplayStyle;
                    textArea.setAttribute("placeholder", "Enter your memo. Add #tags anywhere so they're easy to find and share. Hit the Enter key to save.");
                    alertBox.clearAlert(0);
                } else if (force || textArea.value.length === 0) {
                    qeActivated = false;
                    container.classList.remove('activated');
                    qeToolbar.style.display = 'none';
                    textArea.value = '';
                    textArea.setAttribute("placeholder", (isMobile ? "" : "[Alt-Q] ") + "Quick memo entry...");
                    textArea.blur();
                }
                if (textArea.value.length)
                    document.getElementById('quick_entry_submit').classList.remove('disabled');
                else
                    document.getElementById('quick_entry_submit').classList.add('disabled');
            }
        }
    }
}
