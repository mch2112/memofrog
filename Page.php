<?php

class Page
{
    const USE_MANIFEST = false;

    /* @param $isIntro bool
     * @param $body string
     * @return string
     */
    public static function RenderPage($isIntro, $body = '')
    {
        $head = self::renderHead($isIntro);

        if (strlen($body) == 0)
            $body = self::renderBody();

        if (self::USE_MANIFEST) {
            $manifest = $isIntro ? 'intro_manifest.php' : 'manifest.php';
            return "<!DOCTYPE html><html lang='en' manifest='$manifest'>\n$head\n$body</html>";
        } else {
            return "<!DOCTYPE html><html lang='en'>\n$head\n$body</html>";
        }
    }

    /* @param $isIntro bool
     * @return string
     */
    private static function renderHead($isIntro)
    {
        $appVersion = APP_VERSION;

        $userId = Account::GetUserId();
        $isProduction = Session::IsProduction();

        $xframe = '<meta http-equiv="X-FRAME-OPTIONS" content="DENY">';

        if ($isIntro) {
            $jsURL = self::GetJsUrlIntro();
            $js = "<script src=\"$jsURL\"></script>";
            $inlineJS = '';
            $cssURL = self::GetCssUrlIntro();
            $css = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$cssURL\">";
            $gaJS = '';
        } else {
            $useGAVal = $isProduction ? 'true' : 'false';
            $defaultBucket = Account::IsNewbie($userId) ? Bucket::BUCKET_EVERYTHING : Bucket::BUCKET_HOT_LIST;
            $inlineJS = "<script>__appVersion='$appVersion';__useGA=$useGAVal;__defaultBucket=$defaultBucket</script>";
            $jsURL = self::GetJsUrl();
            $js = "<script src=\"$jsURL\"></script>";

            if ($isProduction)
                /** @noinspection JSUnresolvedLibraryURL */
                $gaJS = '<script async src="//www.google-analytics.com/analytics.js"></script>';
            else
                $gaJS = '';

            $cssURL = self::GetCssUrl();
            $css = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$cssURL\">";
        }
        $googleFont = '';//'<link href="https://fonts.googleapis.com/css?family=Lato:400,700|Raleway:400,700" rel="stylesheet" type="text/css">';
        $html = <<< EOT
<head>
<title>Memofrog</title>
<meta charset="utf-8">
<meta name="description" content="Memofrog captures your ideas, tasks, and reminders, and keeps them so that you can find them quickly and easily, and share them with people you know.">
<meta property="title" content="Memofrog | Keep your ideas fresh and ready." />
<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0">
$xframe
$inlineJS
$googleFont
$css
$js
$gaJS
</head>
EOT;
        return $html;
    }

    /* @return string
     */
    private static function renderBody()
    {
        $isMobile = Session::IsMobile();
        $menuBar = self::renderMenuBar($isMobile);
        $tipsBar = self::renderTipsBar();
        $filterBar = self::renderFilterBar();
        $alertBox = self::renderAlertBox();

        return Html::Tag('body',
            Html::DivWithId('content_frame', 'content_frame home',
                $alertBox .
                Html::DivWithId('top_container', 'top_container',
                    Html::DivWithId('top_panel', 'top_panel',
                        $menuBar .
                        $tipsBar .
                        Html::DivWithId('needs_validation', 'needs_validation dismissed suppressed', 'Your email address has not yet been validated so notifications will not be sent. ' . Html::LinkButton('Validate Now.', ContentKey::CONTENT_KEY_NEEDS_VALIDATION)) .
                        Html::DivWithId('list_header', 'list_header',
                            Html::DivWithId('filter_items', 'filter_items',
                                Html::DivWithid('header_filter_icon', 'header_filter_icon img_bucket0', '') .
                                Html::DivWithid('header_filter_buttons', 'header_filter', '') .
                                Html::DivWithid('header_filter_text', 'header_filter', '') .
                                Html::Div('spacer', '') .
                                '<div id="header_filter_clear" class="header_filter_clear" oncl∂ick="clearFilters(BUCKET_NONE); navigate(null); return false;"></div>') .
                            $filterBar))) .
                Html::DivWithId('content_container', 'content_container',
                    Html::DivWithId('content_panel', 'content_panel', ''))));
    }

    /* @param $isMobile bool
     * @return string
     */
    private static function renderMenuBar($isMobile)
    {
        $account = $isMobile ? '' : Html::A('wrap', 'Account', 'navigateTo(' . ContentKey::CONTENT_KEY_ACCOUNT . ');', Html::Div('menu_img menu_img_account btn_img img_account', ''));
        $searchBox = $isMobile ? '' : Html::Div('spacer', '&nbsp;') . '<input id="search-box" class="search-box" type="search" name="search" placeholder="Search...">';

        return Html::DivWithId('menu_bar', 'menu_bar disabled btn_container content0',
            Html::A('wrap', 'Settings', 'logoClick();', Html::Div('menu_img menu_img_logo', '')) .
            Html::A('wrap', 'Home', 'navigateTo(' . ContentKey::CONTENT_KEY_HOME . ');', Html::Div('menu_img menu_img_home btn_img img_home', '')) .
            Html::A('wrap', 'New Memo', 'navigateTo(' . ContentKey::CONTENT_KEY_NEW_MEMO . ');', Html::Div('menu_img menu_img_create btn_img img_create', '')) .
            Html::A('wrap', 'Tags', 'navigateTo(' . ContentKey::CONTENT_KEY_TAGS . ');', Html::Div('menu_img menu_img_tags btn_img img_tags', '')) .
            Html::A('wrap', 'Friends', 'navigateTo(' . ContentKey::CONTENT_KEY_FRIENDS . ');', Html::Div('menu_img menu_img_friends btn_img img_friends', '')) .
            Html::A('wrap', 'Find', 'navigateTo(' . ContentKey::CONTENT_KEY_FIND . ');', Html::Div('menu_img menu_img_find btn_img img_find', '')) .
            $account .
            Html::A('wrap', 'Help', 'navigateTo(' . ContentKey::CONTENT_KEY_HELP . ');', Html::Div('menu_img menu_img_help btn_img img_help', '')) .
            $searchBox
        );
    }

    /*
     * @return string
     */
    private static function renderAlertBox()
    {
        $dismissIcon = Content::GetJavaScriptLink(
            Html::DivWithId('alert_dismiss_icon', 'alert_dismiss_icon', Html::LargeIcon('dismiss'))
            , 'alertBox.clearAlert(0);return false;');

        $statusBox = Html::DivWithId('outer_alert_box', 'outer_alert_box',
            Html::DivWithId('alert_box', 'alert_box',
                Html::DivWithId('inner_alert_box', 'inner_alert_box',
                    Html::DivWithId('alert_icon', 'alert_icon', self::getLoadingIcon()) . Html::DivWithId('alert_box_text', 'alert_box_text', '') . $dismissIcon)));
        return $statusBox;
    }

    /*
     * @return string
     */
    private static function renderFilterBar()
    {
        return Html::DivWithId('filter_bar', 'filter_bar btn_container bucket0',
            Html::DivWithId('filter_bar_caption', 'filter_bar_caption', 'View:') .
            Html::A('wrap', 'Show Everything', 'filterByBucket(' . Bucket::BUCKET_EVERYTHING . ');', Html::Div('filter_bar_item btn_img img_bucket' . Bucket::BUCKET_EVERYTHING, '')) .
            Html::A('wrap', 'Show Hot List + B List', 'filterByBucket(' . Bucket::BUCKET_ALL_ACTIVE . ');', Html::Div('filter_bar_item btn_img img_bucket' . Bucket::BUCKET_ALL_ACTIVE, '')) .
            Html::Div('img_separator', '') .
            Html::A('wrap', 'Show Hot List', 'filterByBucket(' . Bucket::BUCKET_HOT_LIST . ');', Html::Div('filter_bar_item btn_img img_bucket' . Bucket::BUCKET_HOT_LIST, '')) .
            Html::A('wrap', 'Show B List', 'filterByBucket(' . Bucket::BUCKET_B_LIST . ');', Html::Div('filter_bar_item btn_img img_bucket' . Bucket::BUCKET_B_LIST, '')) .
            Html::Div('img_separator', '') .
            Html::A('wrap', 'Show Reference Items', 'filterByBucket(' . Bucket::BUCKET_REFERENCE . ');', Html::Div('filter_bar_item btn_img img_bucket' . Bucket::BUCKET_REFERENCE, '')) .
            Html::A('wrap', 'Show Journal', 'filterByBucket(' . Bucket::BUCKET_JOURNAL . ');', Html::Div('filter_bar_item btn_img img_bucket' . Bucket::BUCKET_JOURNAL, '')) .
            Html::Div('img_separator', '') .
            Html::A('wrap', 'Show Done Items', 'filterByBucket(' . Bucket::BUCKET_DONE . ');', Html::Div('filter_bar_item btn_img img_bucket' . Bucket::BUCKET_DONE, '')) .
            Html::A('wrap', 'Show Trash', 'filterByBucket(' . Bucket::BUCKET_TRASH . ');', Html::Div('filter_bar_item btn_img img_bucket' . Bucket::BUCKET_TRASH, ''))
        );
    }

    /* @return string */
    private static function renderTipsBar()
    {
        return Html::DivWithId('tips', 'tips suppressed',
            Html::Div('tip_content',
                Html::Div('tip_icon', '') .
                Html::DivWithId('tip_text', 'tip_text', '')) .
            Html::Div('tip_options', Content::GetJavaScriptLink('Next Tip', 'tipManager.showNextTip(); return false;', 'link_button') . '&nbsp;|&nbsp;' . Content::GetJavaScriptLink('Dismiss', 'tipManager.dismissTips(true); return false;', 'link_button')));
    }

    private static function getLoadingIcon()
    {
        $div = '';
        for ($i = 0; $i < 12; ++$i)
            $div .= Html::Div('wt_icn_seg', '');
        return Html::DivWithId('wt_icn_anim', 'wt_icn_anim', $div);
    }
    public static function GetJsUrl() {
        $appVersion = APP_VERSION;
        $suffix = self::GetSuffix(false);
        return "/js/memofrog{$suffix}.js?v=$appVersion";
    }
    public static function GetCssUrl() {
        $suffix = self::GetSuffix(false);
        $cssVersion = CSS_VERSION;
        return "/css/memofrog{$suffix}.css?v=$cssVersion";
    }
    public static function GetJsUrlIntro() {
        $appVersion = APP_VERSION;
        $suffix = self::GetSuffix(true);
        return "/js/intro{$suffix}.js?v=$appVersion";
    }
    public static function GetCssUrlIntro() {
        $suffix = self::GetSuffix(true);
        $cssVersion = CSS_VERSION;
        return "/css/intro{$suffix}.css?v=$cssVersion";
    }
    private static function GetSuffix($minOnly) {
        if ($minOnly) {
            $suffix = '';
        } else {
            if (Session::IsMobile())
                $suffix = 'MT';
            else if (Session::IsTouch())
                $suffix = 'T';
            else
                $suffix = '';
        }
        if (Session::IsProduction())
            $suffix .= '.min';
        return $suffix;
    }
}
