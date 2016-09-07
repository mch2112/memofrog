<?php

require_once('content/CAccount.php');
require_once('content/CAdmin.php');
require_once('content/CAccountDetails.php');
require_once('content/PAccountDetails.php');
require_once('content/CAccountLocked.php');
require_once('content/CBatch.php');
require_once('content/CChangeEmail.php');
require_once('content/PChangeEmail.php');
//require_once('content/CChangePassword.php');
require_once('content/PChangePassword.php');
require_once('content/CDataCheck.php');
require_once('content/CEasterEgg.php');
require_once('content/CEditShare.php');
require_once('content/CError.php');
require_once('content/CForgotPassword.php');
require_once('content/PForgotPassword.php');
require_once('content/CFriend.php');
//require_once('content/CFriends.php');
require_once('content/CHelp.php');
require_once('content/PLogin.php');
require_once('content/CLoginAs.php');
require_once('content/CLogout.php');
require_once('content/CNeedsValidation.php');
require_once('content/CNotifications.php');
require_once('content/CNewShare.php');
//require_once('content/COptions.php');
require_once('content/CPrivacy.php');
require_once('content/CRegisterUser.php');
require_once('content/PRegisterUser.php');
require_once('content/CRenameTag.php');
require_once('content/CResetPassword.php');
require_once('content/CRunTests.php');
require_once('content/CSecurityCheck.php');
require_once('content/CSqlOps.php');
require_once('content/CTag.php');
//require_once('content/CTags.php');
require_once('content/CTermsOfService.php');
require_once('content/CUsers.php');
require_once('content/CValidateEmail.php');
require_once('content/CValidateEmailComplete.php');

abstract class Content
{
    const SUPPRESS_TIPS_NORMAL = 0;
    const SUPPRESS_TIPS_MOBILE = 1;
    const SUPPRESS_TIPS_ALL = 2;

    const CACHEABLE_NEVER = 0;
    const CACHEABLE_ALWAYS = 1;

    /* @var $alert string
     * @var $html string
     * @var $script string
     * @var $cacheable int
     * @var $response array
     * @var $forceRestart bool
     * @var $suppressTips int
     */
    protected static $alert = '';
    protected static $response = array();

    protected $error = ErrorCode::ERROR_NONE;
    protected $html = '';
    protected $script = '';
    protected $cacheable = self::CACHEABLE_NEVER;
    protected $redirect = ContentKey::CONTENT_KEY_NONE;
    protected $contentData = array();
    protected $forceRestart = false;
    protected $suppressTips = self::SUPPRESS_TIPS_NORMAL;
    protected $needsEmailValSuppressed = false;

    public abstract function AuthLevel();

    /* @param $userId int
     * @return void
     */
    public abstract function Render($userId);

    /* @return string */
    public function GetHtml()
    {
        return $this->html;
    }

    /* @return int */
    public abstract function GetContentId();

    /* @param $responseVars array
     * @return array
     */
    public static function GetResponseVars(array $responseVars) { return self::$response + $responseVars; }

    /* @return int */
    public function GetError() {
        return $this->error;
    }

    /* @return string */
    public static function GetAlert() { return self::$alert;
    }
    /* @return int */
    public function GetRedirect() { return $this->redirect; }

    /* @return string */
    public function GetScript() { return $this->script; }

    /* @return array */
    public function GetContentData() { return $this->contentData; }

    /* @return bool */
    public function GetForceRestart() { return $this->forceRestart; }

    /* @return int */
    public function GetSuppressTips() { return $this->suppressTips; }

    public function GetNeedsEmailValSuppressed() { return $this->needsEmailValSuppressed; }

    public function GetCacheable() { return $this->cacheable; }

    /* @param $contentKey int
     * @return Content
     */
    public static function GetContent($contentKey)
    {
        switch ($contentKey) {
            case ContentKey::CONTENT_KEY_ACCOUNT:
                return new CAccount();
            case ContentKey::CONTENT_KEY_POST_ACCOUNT_DETAILS:
                return new PAccountDetails();
            case ContentKey::CONTENT_KEY_ACCOUNT_DETAILS:
                return new CAccountDetails();
            case ContentKey::CONTENT_KEY_ACCOUNT_LOCKED:
                return new CAccountLocked();
            case ContentKey::CONTENT_KEY_ADMIN:
                return new CAdmin();
            case ContentKey::CONTENT_KEY_BATCH:
                return new CBatch();
            case ContentKey::CONTENT_KEY_CHANGE_EMAIL:
                return new CChangeEmail();
            case ContentKey::CONTENT_KEY_POST_CHANGE_EMAIL:
                return new PChangeEmail();
//            case ContentKey::CONTENT_KEY_CHANGE_PASSWORD:
//                return new CChangePassword();
            case ContentKey::CONTENT_KEY_POST_CHANGE_PASSWORD:
                return new PChangePassword();
            case ContentKey::CONTENT_KEY_DATA_CHECK:
                return new CDataCheck();
            case ContentKey::CONTENT_KEY_EASTER_EGG:
                return new CEasterEgg();
            case ContentKey::CONTENT_KEY_EDIT_SHARE:
                return new CEditShare();
            case ContentKey::CONTENT_KEY_ERROR:
                return new CError();
            case ContentKey::CONTENT_KEY_FORGOT_PASSWORD:
                return new CForgotPassword();
            case ContentKey::CONTENT_KEY_POST_FORGOT_PASSWORD:
                return new PForgotPassword();
//            case ContentKey::CONTENT_KEY_FRIENDS:
//                return new CFriends();
            case ContentKey::CONTENT_KEY_FRIEND:
                return new CFriend();
            case ContentKey::CONTENT_KEY_HELP:
                return new CHelp();
            case ContentKey::CONTENT_KEY_POST_LOGIN:
                return new PLogin();
            case ContentKey::CONTENT_KEY_LOGIN_AS:
                return new CLoginAs();
            case ContentKey::CONTENT_KEY_LOGOUT:
                return new CLogout();
            case ContentKey::CONTENT_KEY_NEEDS_VALIDATION:
                return new CNeedsValidation();
            case ContentKey::CONTENT_KEY_NOTIFICATIONS:
                return new CNotifications();
            case ContentKey::CONTENT_KEY_NEW_SHARE:
                return new CNewShare();
//            case ContentKey::CONTENT_KEY_SETTINGS:
//                return new CSettings();
            case ContentKey::CONTENT_KEY_PRIVACY:
                return new CPrivacy();
            case ContentKey::CONTENT_KEY_REGISTER_USER;
                return new CRegisterUser();
            case ContentKey::CONTENT_KEY_POST_REGISTER_USER;
                return new PRegisterUser();
            case ContentKey::CONTENT_KEY_RENAME_TAG:
                return new CRenameTag();
            case ContentKey::CONTENT_KEY_RESET_PASSWORD:
                return new CResetPassword();
            case ContentKey::CONTENT_KEY_RUN_TESTS:
                return new CRunTests();
            case ContentKey::CONTENT_KEY_SECURITY_CHECK:
                return new CSecurityCheck();
            case ContentKey::CONTENT_KEY_SQL_OPS:
                return new CSqlOps();
            case ContentKey::CONTENT_KEY_TAG:
                return new CTag();
//            case ContentKey::CONTENT_KEY_TAGS:
//                return new CTags();
            case ContentKey::CONTENT_KEY_TERMS_OF_SERVICE:
                return new CTermsOfService();
            case ContentKey::CONTENT_KEY_USERS:
                return new CUsers();
            case ContentKey::CONTENT_KEY_GEN_VALIDATION_TOKEN:
                return new CValidateEmail();
            case ContentKey::CONTENT_KEY_VALIDATE_EMAIL_COMPLETE:
                return new CValidateEmailComplete();
            default:
                return null;
        }
    }
    protected function setError($errorCode)
    {
        $this->error = $errorCode;

//        Session::SetSessionVal(Key::KEY_ERROR_CODE, $errorCode);
//        Session::SetSessionVal(Key::KEY_ERROR_CONTENT_ID, $this->GetContentId());
//        $this->redirect = ContentKey::CONTENT_KEY_ERROR;
    }

    public static function GetFullNameLink($user_id)
    {
        $sql = "SELECT * FROM users INNER JOIN accounts ON users.id = accounts.user_id WHERE users.id=$user_id";
        $row = Database::QueryOneRow($sql);
        return Html::Span('full_name', Html::Span('real_name', $row['real_name']) . '&nbsp;' . Html::Span('screen_name', self::GetJavaScriptLink('@' . $row['screen_name'], "filterByScreenName(\"{$row['screen_name']}\"); return false;")));
    }

    protected function renderInstructions($id, $instructions)
    {
        return Html::DivWithId($id, 'instructions', $instructions);
    }
    public static function GetScreenNamePattern()
    {
        return '[a-zA-Z0-9_]+';
    }
    /* @param $num int
     * @param $word string
     * @param $capitalize bool
     * @return string
     */
    public static function Pluralize($num, $word, $capitalize=false)
    {
        switch ($num) {
            case 0:
                if ($capitalize)
                    return "No {$word}s";
                else
                    return "no {$word}s";
            case 1:
                return "1 $word";
            default:
                return "$num {$word}s";
        }
    }
    /* @param $num int
     * @return string
     */
    public static function pluralizedVerb($num)
    {
        switch ($num) {
            case 1:
                return 'is';
            default:
                return 'are';
        }
    }
    public static function GetNavLink($caption, $contentKey, $className = '')
    {
        return self::GetNavLinkWithArgs($caption, array(Key::KEY_CONTENT_REQ => $contentKey), $className);
    }
    public static function GetJavaScriptLink($caption, $script, $class_name = '', $id = '')
    {
        if (strlen($id) > 0)
            $id = "id='$id'";

        if (strlen($class_name) > 0)
            $class_name = "class='$class_name'";

        if (strlen($script) > 0)
            $script = "onclick='$script'";

        return "<a $id $class_name $script href=\"\">$caption</a>";
    }
    public static function GetNavLinkWithArgs($caption, $args, $class_name='', $id='')
    {
        return self::GetJavaScriptLink($caption, self::getNavJavascript($args), $class_name, $id);
    }
    private static function getNavJavascript($args)
    {
        $jsonArgs = json_encode($args);
        return "navigate($jsonArgs); return false;";
    }
    /**
     * @param $type int
     * @param $elements string[]
     * @return string
     */
    public static function GetValidationScript($type, array $elements)
    {
        $elem = '["'.join('","', $elements) .'"]';
        return "initValidation($type,$elem);";
    }
}