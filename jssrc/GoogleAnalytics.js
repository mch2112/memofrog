// put this in Page head:
// <script async src='//www.google-analytics.com/analytics.js'></script>

var googleAnalytics = new GoogleAnalytics();

function GoogleAnalytics() {
    this.enabled = false;
}
GoogleAnalytics.prototype.start = function () {
    this.enabled = true;
    window.ga = window.ga || function () {
            (ga.q = ga.q || []).push(arguments);
        };
    ga.l = +new Date();
    ga("create", "UA-70396919-1", "auto");
    ga("send", "pageview");
};
GoogleAnalytics.prototype.track = function (contentKey) {
    if (this.enabled) {
        ga("send", "pageview", "/" + contentKey.toString());
    }
};