function element(name, props, contents) {
    var inner = "";
    for (var prop in props) {
        inner += " " + prop + "=\"" + props[prop] + "\"";
    }
    return "<" + name + inner + ">" + contents + "</" + name + ">";
}
function elementStandAlone(name, props) {
    var inner = "";
    for (var prop in props) {
        inner += " " + prop + "=\"" + props[prop] + "\"";
    }
    return "<" + name + inner + " />";
}
function div(className, contents, onclick) {
    return _element("div", className, contents, onclick);
}
function divTitle(className, title, contents) {
    return "<div class=\"" + className + "\" title=\"" + title + "\">" + contents + "</div>";
}
function form(className, contents) {
    return "<form class=\"" + className + "\" class=\"standard_form\" method=\"post\" >" + contents + "</form>";
}
function fieldset(contents) {
    return "<fieldset>" + contents + "</fieldset>";
}
function label(contents, labelFor) {
    return "<label for=\"" + labelFor + "\">" + contents + "</label>";
}
function textInput(id, type, name, labelText, placeholder, value, autofocus, autocomplete, pattern) {
    if (pattern)
        pattern = "pattern =\"" + pattern + "\"";

    return label(labelText, id) +
        "<input id=\"" + id + "\" type=\"" + type + "\" name=\"" + name + "\" placeholder=\"" + placeholder + "\" value=\"" + value + "\"" +
        (autofocus ? "autofocus " : "") + " autocomplete=\"" + autocomplete + "\" x-autocompletetype=\"" + autocomplete + "\"" + pattern + ">";
}
function checkbox(id, name, labelText, checked) {
    return "<input type=\"checkbox\" id=\"" + id + "\" name=\"" + name + "\"" + (checked ? " checked" : "") + "><label for=\"" + id + "\">" + labelText + "</label>";
}
function hiddenInput(name, value) {
    return "<input type=\"hidden\" name=\"" + name + "\" value=\"" + value + "\">";
}
function divId(id, className, contents, onclick) {
    return _elementWithId("div", id, className, contents, onclick);
}
function aId(id, className, contents, onclick) {
    return _elementWithId("a", id, className, contents, onclick);
}
function textArea(props, value) {
    return element("textarea", props, value);
}
function p(className, contents) {
    return _element("p", className, contents);
}
function span(className, contents)  {
    return _element("span", className, contents);
}
function a(className, contents, onclick) {
    return "<a class=\"" +
        className +
        "\" onclick=\"" +
        onclick +
        " return false;\" href=\"\">" +
        contents +
        "</a>";
}
function aTitle(className, title, contents, onclick) {
    return "<a class=\"" +
        className +
        "\" title=\"" +
        title +
        "\" onclick=\"" +
        onclick +
        " return false;\" href=\"\">" +
        contents +
        "</a>";
}
function submitButton(id, className, value) {
    return "<input id=\"" + id + "\" type=\"submit\" class=\"" + className + "\" value=\"" + value + "\">";
}
function wrap(className, title, onclick) {
    return aTitle("wrap", title, div(className, ""), onclick);
}
function strong(content) {
    return "<strong>" + content + "</strong>";
}
function _element(name, className, contents, onclick) {
    if (onclick)
        return "<" + name + " class=\"" + className + "\" onclick=\"" + onclick + "return false;\">" + contents + "</" + name + ">";
    else if (className)
        return "<" + name + " class=\"" + className + "\">" + contents + "</" + name + ">";
    else
        return "<" + name + ">" + contents + "</" + name + ">";
}
function _elementWithId(name, id, className, contents, onclick) {
    if (onclick)
        return "<" + name + " id=\"" + id + "\" class=\"" + className + "\" onclick=\"" + onclick + "return false;\">" + contents + "</" + name + ">";
    else
        return "<" + name + " id=\"" + id + "\" class=\"" + className + "\">" + contents + "</" + name + ">";
}