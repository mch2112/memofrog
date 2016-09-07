<?php

class TMemoAlarm extends Test
{
    function __construct()
    {
        $this->name = 'Memo Alarm';
    }

    public function Run()
    {
        $userId_1 = $this->GetNewUser();
        $userId_2 = $this->GetNewUser();

        $tag = $this->GetUniqueIdentifier('tag');

        $memoId_1 = ControllerW::CreateMemo($userId_1, "Test: #$tag", Bucket::BUCKET_REFERENCE);
        $memoId_2 = ControllerW::CreateMemo($userId_2, "Test: #$tag", Bucket::BUCKET_JOURNAL);

        $shareId1 = ControllerW::FindOrCreateShare($userId_1, $userId_2, $tag, true, true, false, $isNew);
        $shareId2 = Controller::GetReciprocalShareId($shareId1);
        ControllerW::SetShareEnable($shareId2, true, true);
        ControllerW::SetShareEnable($shareId1, false, true);
        ControllerW::SetShareEnable($shareId2, false, true);

        ControllerW::SetBucket($userId_1, $memoId_2, Bucket::BUCKET_B_LIST);
        ControllerW::SetBucket($userId_2, $memoId_1, Bucket::BUCKET_B_LIST);

        $this->assertIsFalse('A', Controller::GetStar($userId_1, $memoId_1), "Memo $memoId_1 should not be starred for $userId_1.");
        $this->assertIsFalse('B', Controller::GetStar($userId_2, $memoId_1), "Memo $memoId_1 should not be starred for $userId_2.");
        $this->assertIsFalse('C', Controller::GetMemoBucket($userId_1, $memoId_1) === Bucket::BUCKET_HOT_LIST, "Memo $memoId_1 should not be in the Hot List for User $userId_1");
        $this->assertIsFalse('D', Controller::GetMemoBucket($userId_1, $memoId_1) === Bucket::BUCKET_HOT_LIST, "Memo $memoId_1 should not be in the Hot List for User $userId_2");
        $this->assertIsFalse('E', Controller::GetStar($userId_1, $memoId_2), "Memo $memoId_2 should not be starred for $userId_1.");
        $this->assertIsFalse('F', Controller::GetStar($userId_2, $memoId_2), "Memo $memoId_2 should not be starred for $userId_2.");
        $this->assertIsFalse('G', Controller::GetMemoBucket($userId_1, $memoId_2) === Bucket::BUCKET_HOT_LIST, "Memo $memoId_2 should not be in the Hot List for User $userId_1");
        $this->assertIsFalse('H', Controller::GetMemoBucket($userId_2, $memoId_2) === Bucket::BUCKET_HOT_LIST, "Memo $memoId_2 should not be in the Hot List for User $userId_2");

        ControllerW::EditAlarm($userId_1, $memoId_1, Util::GetDaysFromToday(-1));
        Batch::RunAll();

        $this->assertIsTrue ('I', Controller::GetStar($userId_1, $memoId_1), "Memo $memoId_1 should be starred for $userId_1.");
        $this->assertIsFalse('J', Controller::GetStar($userId_2, $memoId_1), "Memo $memoId_1 should not be starred for $userId_2.");
        $this->assertIsTrue ('K', Controller::GetMemoBucket($userId_1, $memoId_1) === Bucket::BUCKET_HOT_LIST, "Memo $memoId_1 should be in the Hot List for User $userId_1");
        $this->assertIsFalse('L', Controller::GetMemoBucket($userId_2, $memoId_1) === Bucket::BUCKET_HOT_LIST, "Memo $memoId_1 should not be in the Hot List for User $userId_2");

        ControllerW::EditAlarm($userId_2, $memoId_2, Util::GetDaysFromToday(-1));
        Batch::RunAll();

        $this->assertIsFalse('M', Controller::GetStar($userId_1, $memoId_2), "Memo $memoId_2 should not be starred for $userId_1.");
        $this->assertIsTrue ('N', Controller::GetStar($userId_2, $memoId_2), "Memo $memoId_2 should be starred for $userId_2.");
        $this->assertIsFalse('O', Controller::GetMemoBucket($userId_1, $memoId_2) === Bucket::BUCKET_HOT_LIST, "Memo $memoId_2 should not be in the Hot List for User $userId_1");
        $this->assertIsTrue ('P', Controller::GetMemoBucket($userId_2, $memoId_2) === Bucket::BUCKET_HOT_LIST, "Memo $memoId_2 should be in the Hot List for User $userId_2");

        ControllerW::EditAlarm($userId_2, $memoId_1, Util::GetDaysFromToday(-1));
        Batch::RunAll();

        $this->assertIsTrue('Q', Controller::GetStar($userId_1, $memoId_1), "Memo $memoId_1 should be starred for $userId_1.");
        $this->assertIsTrue('R', Controller::GetStar($userId_2, $memoId_1), "Memo $memoId_1 should be starred for $userId_2.");
        $this->assertIsTrue('S', Controller::GetMemoBucket($userId_1, $memoId_1) === Bucket::BUCKET_HOT_LIST, "Memo $memoId_1 should be in the Hot List for User $userId_1");
        $this->assertIsTrue('T', Controller::GetMemoBucket($userId_2, $memoId_1) === Bucket::BUCKET_HOT_LIST, "Memo $memoId_1 should be in the Hot List for User $userId_2");

        ControllerW::EditAlarm($userId_1, $memoId_2, Util::GetDaysFromToday(-1));
        Batch::RunAll();

        $this->assertIsTrue('U', Controller::GetStar($userId_1, $memoId_2), "Memo $memoId_2 should be starred for $userId_1.");
        $this->assertIsTrue('V', Controller::GetStar($userId_2, $memoId_2), "Memo $memoId_2 should be starred for $userId_2.");
        $this->assertIsTrue('W', Controller::GetMemoBucket($userId_1, $memoId_2) === Bucket::BUCKET_HOT_LIST, "Memo $memoId_2 should be in the Hot List for User $userId_1");
        $this->assertIsTrue('X', Controller::GetMemoBucket($userId_2, $memoId_2) === Bucket::BUCKET_HOT_LIST, "Memo $memoId_2 should be in the Hot List for User $userId_2");

        $this->finished = true;
    }

    public function OkForProduction()
    {
        return false;
    }
}
