<?php

const APP_VERSION = '1.0252g';
const CSS_VERSION = '1.0252';

require_once('Account.php');
require_once('Batch.php');
require_once('Bucket.php');
require_once('Content.php');
require_once('ContentKey.php');
require_once('Controller.php');
require_once('ControllerW.php');
require_once('Database.php');
require_once('ErrorCode.php');
require_once('Html.php');
require_once('IntroLib.php');
require_once('Key.php');
require_once('LoginStatus.php');
require_once('Mail.php');
require_once('Notification.php');
require_once('Option.php');
require_once('Page.php');
require_once('Password.php');
require_once('RegEx.php');
require_once('Response.php');
require_once('Search.php');
require_once('Session.php');
require_once('ShareDbUpdater.php');
require_once('Sql.php');

require_once('SqlOps.php');
require_once('Test.php');
require_once('Token.php');
require_once('UserInput.php');
require_once('Util.php');
require_once('Validation.php');
require_once('View.php');

mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");

ini_set("date.timezone", "Etc/UTC");