<?php

class TMixedShare extends Test
{
    function __construct()
    {
        $this->name = 'Mixed Share';
    }

    public function Run()
    {
        $authorUserId = $this->GetNewUser();
        $targetUserId_1 = $this->GetNewUser();
        $targetUserId_2 = $this->GetNewUser();

        $targetScreenName_1 = Account::GetScreenName($targetUserId_1);
        $tag = $this->GetUniqueIdentifier('tag');
        $memoTagText = "Test: #$tag";
        $memoDsText = "Test: @$targetScreenName_1";
        $memoBothText = $memoTagText . ' ' . $memoDsText;
        $memoNeitherText = 'abc123';

        $allMemos = array(
            $m0 = ControllerW::CreateMemo($authorUserId, $memoTagText, Bucket::BUCKET_HOT_LIST),
            $m1 = ControllerW::CreateMemo($authorUserId, $memoDsText, Bucket::BUCKET_HOT_LIST),
            $m2 = ControllerW::CreateMemo($authorUserId, $memoBothText, Bucket::BUCKET_HOT_LIST),
            $m3 = ControllerW::CreateMemo($authorUserId, $memoNeitherText, Bucket::BUCKET_HOT_LIST)
        );

        $this->assertIsFalse('A', Controller::ComputeMemoIsShared($m0), "Memo $m0 should not be shared.");
        $this->assertIsTrue('B', Controller::ComputeMemoIsShared($m1), "Memo $m1 should be shared.");
        $this->assertIsTrue('C', Controller::ComputeMemoIsShared($m2), "Memo $m2 should be shared.");
        $this->assertIsFalse('D', Controller::ComputeMemoIsShared($m3), "Memo $m3 should not be shared.");
        $this->assertMemoVisibility('E', $authorUserId, $targetUserId_1, $allMemos, array($m1, $m2));
        $this->assertMemoVisibility('F', $authorUserId, $targetUserId_2, $allMemos);

        $shareId = ControllerW::FindOrCreateShare($authorUserId, $targetUserId_2, $tag, true, true, false, $isNew);

        $this->assertIsTrue('G', Controller::ComputeMemoIsShared($m0), "Memo $m0 should be shared.");
        $this->assertIsTrue('H', Controller::ComputeMemoIsShared($m1), "Memo $m1 should be shared.");
        $this->assertIsTrue('I', Controller::ComputeMemoIsShared($m2), "Memo $m2 should be shared.");
        $this->assertIsFalse('J', Controller::ComputeMemoIsShared($m3), "Memo $m3 should not be shared.");
        $this->assertMemoVisibility('K', $authorUserId, $targetUserId_1, $allMemos, array($m1, $m2));
        $this->assertMemoVisibility('L', $authorUserId, $targetUserId_2, $allMemos, array($m0, $m2));

        ControllerW::SetShareEnable($shareId, true, false);

        $this->assertIsFalse('M', Controller::ComputeMemoIsShared($m0), "Memo $m0 should not be shared.");
        $this->assertIsTrue('N', Controller::ComputeMemoIsShared($m1), "Memo $m1 should be shared.");
        $this->assertIsTrue('O', Controller::ComputeMemoIsShared($m2), "Memo $m2 should be shared.");
        $this->assertIsFalse('P', Controller::ComputeMemoIsShared($m3), "Memo $m3 should not be shared.");
        $this->assertMemoVisibility('Q', $authorUserId, $targetUserId_1, $allMemos, array($m1, $m2));
        $this->assertMemoVisibility('R', $authorUserId, $targetUserId_2, $allMemos);

        ControllerW::SetShareEnable($shareId, true, true);

        $this->assertIsTrue('S', Controller::ComputeMemoIsShared($m0), "Memo $m0 should be shared.");
        $this->assertIsTrue('T', Controller::ComputeMemoIsShared($m1), "Memo $m1 should be shared.");
        $this->assertIsTrue('U', Controller::ComputeMemoIsShared($m2), "Memo $m2 should be shared.");
        $this->assertIsFalse('V', Controller::ComputeMemoIsShared($m3), "Memo $m3 should not be shared.");
        $this->assertMemoVisibility('W', $authorUserId, $targetUserId_1, $allMemos, array($m1, $m2));
        $this->assertMemoVisibility('X', $authorUserId, $targetUserId_2, $allMemos, array($m0, $m2));

        ControllerW::EditMemo($authorUserId, $m0, 'foobar');

        $this->assertIsFalse('Y', Controller::ComputeMemoIsShared($m0), "Memo $m0 should not be shared.");
        $this->assertMemoVisibility('Z', $authorUserId, $targetUserId_2, $allMemos, array($m2));

        ControllerW::EditMemo($authorUserId, $m0, $memoBothText);
        $this->assertIsTrue('AA', Controller::ComputeMemoIsShared($m0), "Memo $m0 should be shared.");
        $this->assertMemoVisibility('AB', $authorUserId, $targetUserId_1, $allMemos, array($m0, $m1, $m2));
        $this->assertMemoVisibility('AC', $authorUserId, $targetUserId_2, $allMemos, array($m0, $m2));

        ControllerW::SetShareEnable($shareId, true, false);

        $this->assertIsTrue('AD', Controller::ComputeMemoIsShared($m0), "Memo $m0 should be shared.");
        $this->assertIsTrue('AE', Controller::ComputeMemoIsShared($m1), "Memo $m1 should be shared.");
        $this->assertIsTrue('AF', Controller::ComputeMemoIsShared($m2), "Memo $m2 should be shared.");
        $this->assertIsFalse('AG', Controller::ComputeMemoIsShared($m3), "Memo $m3 should not be shared.");
        $this->assertMemoVisibility('AH', $authorUserId, $targetUserId_1, $allMemos, array($m0, $m1, $m2));
        $this->assertMemoVisibility('AI', $authorUserId, $targetUserId_2, $allMemos);

        $this->finished = true;
    }

    public function OkForProduction()
    {
        return false;
    }
}
