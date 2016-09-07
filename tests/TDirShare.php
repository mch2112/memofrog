<?php

class TDirShare extends Test
{
    function __construct()
    {
        $this->name = 'Direct Share';
    }

    public function Run()
    {
        $sourceUserId = $this->GetNewUser();
        $targetUserId = $this->GetNewUser();

        $targetScreenName = Account::GetScreenName($targetUserId);

        $memoTextPrefix = "Test: @";
        $memo1TextPostfix = " xyz"; // used for memo 1 not 0
        $directShareMemo0Text = $memoTextPrefix . $targetScreenName;
        $directShareMemo1Text = $directShareMemo0Text . $memo1TextPostfix;

        $allMemos = array(
            ControllerW::CreateMemo($sourceUserId, "Test: foo", Bucket::BUCKET_HOT_LIST),
            ControllerW::CreateMemo($sourceUserId, $directShareMemo1Text, Bucket::BUCKET_HOT_LIST)
        );

        // User 1 can see memo 1
        $this->assertMemoVisibility('A', $sourceUserId, $targetUserId, $allMemos, array($allMemos[1]));

        // set memo1 private, user 1 can't see any now
        ControllerW::EditMemo($sourceUserId, $allMemos[1], $directShareMemo1Text, true);
        $this->assertMemoVisibility('B', $sourceUserId, $targetUserId, $allMemos);

        // make memo0 direct share
        ControllerW::EditMemo($sourceUserId, $allMemos[0], $directShareMemo0Text, false);
        $this->assertMemoVisibility('C', $sourceUserId, $targetUserId, $allMemos, array($allMemos[0]));

        // now memo1 not private anymore, can see all
        ControllerW::EditMemo($sourceUserId, $allMemos[1], $directShareMemo1Text, false);
        $this->assertMemoVisibility('D', $sourceUserId, $targetUserId, $allMemos, $allMemos);

        // but none editable
        $this->assertMemoEditability('E', $sourceUserId, $targetUserId, $allMemos);

        // rename user 1 and ensure direct share still works
        $newTargetScreenName = $this->GetUniqueIdentifier('screen_name');
        Account::UpdateAccountDetails($targetUserId, 'y', $newTargetScreenName);
        $newMemo0ExpectedText = $memoTextPrefix . $newTargetScreenName;
        $newMemo1ExpectedText = $newMemo0ExpectedText . $memo1TextPostfix;
        $this->assertIsTrue('F', Controller::GetMemoText($allMemos[0]) === $newMemo0ExpectedText, "Memo {$allMemos[0]} text should be $newMemo0ExpectedText");
        $this->assertIsTrue('G', Controller::GetMemoText($allMemos[1]) === $newMemo1ExpectedText, "Memo {$allMemos[1]} text should be $newMemo1ExpectedText");
        $this->assertMemoVisibility('H', $sourceUserId, $targetUserId, $allMemos, $allMemos);

        // should not be shared after deleting
        ControllerW::SetBucket($sourceUserId, $allMemos[0], Bucket::BUCKET_DELETED);
        $this->assertIsFalse('I', Controller::ComputeMemoIsShared($allMemos[0]), "Memo {$allMemos[0]} should not be shared after deletion");

        ControllerW::SetBucket($sourceUserId, $allMemos[1], Bucket::BUCKET_DELETED);
        $this->assertIsFalse('J', Controller::ComputeMemoIsShared($allMemos[1]), "Memo {$allMemos[1]} should not be shared after deletion");

        $this->finished = true;
    }

    public function OkForProduction()
    {
        return false;
    }
}
