var domManager = new DomManager();

function DomManager() {
    this.contentFrame = null;
    this.contentContainer = null;
    this.contentPanel = null;
    this.menuBar = null;
    this.needsValidation = null;
    this.listHeader = null;
    this.headerFilterIcon = null;
    this.headerFilterClear = null;
    this.filterBarCaption = null;
    this.headerFilterText = null;
    this.headerFilterButtons = null;
    this.searchBox = null;
    this.filterBar = null;
}
DomManager.prototype.start = function() {
    this.contentFrame = document.getElementById("content_frame");
    this.contentContainer = document.getElementById("content_container");
    this.contentPanel = document.getElementById("content_panel");
    this.menuBar = document.getElementById("menu_bar");
    this.needsValidation = document.getElementById("needs_validation");
    this.listHeader = document.getElementById("list_header");
    this.headerFilterIcon = document.getElementById("header_filter_icon");
    this.headerFilterClear = document.getElementById("header_filter_clear");
    this.filterBarCaption = document.getElementById("filter_bar_caption");
    this.headerFilterText = document.getElementById("header_filter_text");
    this.headerFilterButtons = document.getElementById("header_filter_buttons");
    this.searchBox = document.getElementById("search-box");
    this.filterBar = document.getElementById("filter_bar");

    alertBox.start();
    tipManager.start();
};
//noinspection JSUnusedGlobalSymbols
DomManager.prototype.vanishByClass = function(className) {
    Array.prototype.slice.call(document.getElementsByClassName(className)).forEach(this.vanish);
};
DomManager.prototype.vanish = function(elem) {
    if (elem !== null)
        elem.classList.add('vanished');
};
//noinspection JSUnusedGlobalSymbols
DomManager.prototype.elementCount = function(className) {
    return document.getElementsByClassName(className).length;
};
DomManager.prototype.getByClass = function(parent, className) {
    return parent.getElementsByClassName(className)[0];
};
