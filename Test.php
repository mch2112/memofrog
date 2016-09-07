<?php

require_once('tests/TAuthorFilter.php');
require_once('tests/TAutoBucketChanges.php');
require_once('tests/TDatabaseTimeSync.php');
require_once('tests/TDirShare.php');
require_once('tests/TMemoAlarm.php');
require_once('tests/TMemoGetsTagged.php');
require_once('tests/TMemoShareVisibility.php');
require_once('tests/TMixedShare.php');
require_once('tests/TNewUser.php');
require_once('tests/TPrivateVisibility.php');
require_once('tests/TRegistration.php');
require_once('tests/TShareVisibleCanEditIndependence.php');
require_once('tests/TTagVisibility.php');

abstract class Test
{
    const TEST_NONE = 0;
    const TEST_AUTHOR_FILTER = 150;
    const TEST_AUTO_BUCKET_CHANGES = 160;
    const TEST_DATABASE_TIME_SYNC = 170;
    const TEST_DIRECT_SHARE = 180;
    const TEST_MEMO_SHARE_VISIBILITY = 190;
    const TEST_MEMO_ALARM = 200;
    const TEST_MEMO_GETS_TAGGED = 205;
    const TEST_MIXED_SHARE = 208;
    const TEST_NEW_USER = 210;
    const TEST_PRIVATE_VISIBILITY = 220;
    const TEST_REGISTRATION = 230;
    const TEST_SHARE_CAN_EDIT_INDEPENDENCE = 240;
    const TEST_TAG_VISIBILITY = 250;

    protected $name = '';
    protected $exception = null;
    protected $type = self::TEST_NONE;
    protected $isError = false;
    protected $finished = false;
    protected $result = '';
    protected static $abort = false;

    /* @param $testId int
     * @return Test
     */
    public static function GetTest($testId)
    {
        /* @var $test Test */
        switch ($testId) {
            case self::TEST_AUTHOR_FILTER:
                $test = new TAuthorFilter();
                break;
            case self::TEST_AUTO_BUCKET_CHANGES:
                $test = new TAutoBucketChanges();
                break;
            case self::TEST_DATABASE_TIME_SYNC:
                $test = new TDatabaseTimeSync();
                break;
            case self::TEST_DIRECT_SHARE:
                $test = new TDirShare();
                break;
            case self::TEST_MEMO_ALARM:
                $test = new TMemoAlarm();
                break;
            case self::TEST_MEMO_SHARE_VISIBILITY:
                $test = new TMemoShareVisibility();
                break;
            case self::TEST_MEMO_GETS_TAGGED:
                $test = new TMemoGetsTagged();
                break;
            case self::TEST_MIXED_SHARE:
                $test = new TMixedShare();
                break;
            case self::TEST_NEW_USER:
                $test = new TNewUser();
                break;
            case self::TEST_PRIVATE_VISIBILITY:
                $test = new TPrivateVisibility();
                break;
            case self::TEST_REGISTRATION:
                $test = new TRegistration();
                break;
            case self::TEST_TAG_VISIBILITY:
                $test = new TTagVisibility();
                break;
            case self::TEST_SHARE_CAN_EDIT_INDEPENDENCE:
                $test = new TShareVisibleCanEditIndependence();
                break;
            default:
                return new TTestError('Test not found.');
        }
        if (!$test->OkForProduction() && Session::IsProduction()) {
            $name = $test->name;
            $test = new TTestError('Test not ok for production.');
            $test->name = $name;
        }
        return $test;
    }

    /* @param $run bool
     * @param $callback Callable
     */
    public static function EnumerateTests($run, $callback)
    {
        $testIds = array(self::TEST_AUTHOR_FILTER,
            self::TEST_DATABASE_TIME_SYNC,
            self::TEST_DIRECT_SHARE,
            self::TEST_AUTO_BUCKET_CHANGES,
            self::TEST_MEMO_ALARM,
            self::TEST_MEMO_SHARE_VISIBILITY,
            self::TEST_MEMO_GETS_TAGGED,
            self::TEST_MIXED_SHARE,
            self::TEST_NEW_USER,
            self::TEST_PRIVATE_VISIBILITY,
            self::TEST_REGISTRATION,
            self::TEST_SHARE_CAN_EDIT_INDEPENDENCE,
            self::TEST_TAG_VISIBILITY);

        foreach ($testIds as $t) {

            /*if ($t != self::TEST_REGISTRATION)
                continue;*/

            $test = self::GetTest($t);

            try {

                if ($run)
                    $test->Run();

                $callback($test);

            } catch (Exception $e) {
                $test->exception = $e;
            }

            if (self::$abort)
                break;
        }
    }

    public abstract function Run();

    public abstract function OkForProduction();

    public function IsError()
    {
        return $this->isError;
    }

    public function Result()
    {
        $dnf = $this->finished ? '' : '<br>DID NOT FINISH';

        if (!is_null($this->exception)) {
            $this->isError = true;
            $ret = '<br>Exception: ' . json_encode($this->exception);
        } else if (strlen($this->result) > 0) {
            $ret = '<br>' . $this->result;
        } else if ($this->isError) {
            $ret = 'Error';
        } else  if (!$this->finished) {
            $ret = $dnf;
            $dnf = '';
        } else {
            $ret = 'OK';
        }

        return $dnf.$ret;
    }

    public function Name()
    {
        return $this->name;
    }

    protected function assertIsTrue($step, $predicate, $failText)
    {
        if (!$predicate) {
            $this->isError = true;
            $this->result .= "$step: $failText<br>";
        }
    }

    protected function assertIsFalse($step, $predicate, $failText)
    {
        $this->assertIsTrue($step, !$predicate, $failText);
    }
    protected function getNewUser()
    {
        return Controller::RegisterUser($this->GetUniqueIdentifier('screen_name') . '@' . $this->GetUniqueIdentifier('domain') . '.com',
            $this->GetUniqueIdentifier('screen_name'),
            $this->GetUniqueIdentifier('real_name'),
            Account::HashPassword('password'),
            false);
    }

    protected function GetMemoText($memoId)
    {
        return Database::LookupValue('memos', 'id', $memoId, 'i', 'memo_text', '');
    }

    protected function GetUniqueIdentifier($prefix)
    {
        return $prefix . Util::GetRandomHexString(16);
    }

    protected function ArraysAreIdentical(array $array0, array $array1)
    {
        // assumes uniqueness

        sort($array0);
        sort($array1);

        if (count($array0) != count($array1))
            return false;

        if (count(array_diff($array0, $array1)) > 0)
            return false;

        if (count(array_diff($array1, $array0)) > 0)
            return false;

        return true;
    }

    protected function assertMemoVisibility($step, $authorId, $otherUserId, array $allMemos, array $memosThatShouldBeVisible = array())
    {
        foreach ($allMemos as $m) {
            $this->assertCanSeeMemo($step, $authorId, $m, true);
            if ($authorId != $otherUserId && $otherUserId > 0)
                $this->assertCanSeeMemo($step, $otherUserId, $m, in_array($m, $memosThatShouldBeVisible));
        }
    }

    protected function assertBucket($step, $userId, array $memoIds, $bucket)
    {
        foreach ($memoIds as $m) {
            $this->assertIsTrue($step, Controller::GetMemoBucket($userId, $m) == $bucket, "Memo $m bucket is " . Controller::GetMemoBucket($userId, $m) . ' should be ' . $bucket . '.');
        }
    }

    protected function assertMemoNotDeleted($step, $memoId, $exists)
    {
        if ($exists)
            $this->assertIsTrue($step, Controller::MemoNotDeleted($memoId), "Memo $memoId should exist.");
        else
            $this->assertIsFalse($step, Controller::MemoNotDeleted($memoId), "Memo $memoId should not exist.");
    }
    protected function assertCanSeeMemo($step, $userId, $memoId, $assertCan)
    {
        if ($assertCan)
            $this->assertIsTrue($step, Controller::UserCanViewMemo($userId, $memoId), "User $userId can't view memo $memoId: &quot;" . $this->GetMemoText($memoId) . "&quot;");
        else
            $this->assertIsFalse($step, Controller::UserCanViewMemo($userId, $memoId), "User $userId shouldn't view memo $memoId: &quot;" . $this->GetMemoText($memoId) . "&quot;");
    }

    protected function assertMemoEditability($step, $authorId, $otherUserId, array $allMemos, array $memosThatShouldBeEditable = array())
    {
        foreach ($allMemos as $m) {
            $this->assertCanEditMemo($step, $authorId, $m, true);
            if ($authorId != $otherUserId && $otherUserId > 0)
                $this->assertCanEditMemo($step, $otherUserId, $m, in_array($m, $memosThatShouldBeEditable));
        }
    }

    protected function assertCanEditMemo($step, $userId, $memoId, $assertCan)
    {
        if ($assertCan)
            $this->assertIsTrue($step, Controller::UserCanEditMemo($userId, $memoId), "User $userId can't edit memo $memoId: &quot;" . $this->GetMemoText($memoId) . "&quot;");
        else
            $this->assertIsFalse($step, Controller::UserCanEditMemo($userId, $memoId), "User $userId shouldn't be able to edit memo $memoId: &quot;" . $this->GetMemoText($memoId) . "&quot;");
    }
}

class TTestError extends Test
{
    function __construct($result)
    {
        $this->name = 'Error';
        $this->isError = true;
        $this->result = $result;
    }

    public function Run()
    {
    }

    public function OkForProduction()
    {
        return true;
    }
}