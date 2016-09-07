<?php

class TAutoBucketChanges extends Test
{
    function __construct()
    {
        $this->name = 'Auto Bucket Changes';
    }

    public function Run()
    {
        $userId = $this->GetNewUser();

        Controller::SetUserOption($userId, Option::OPTION_MOVE_DONE_TO_TRASH, 1);

        $allMemos = array(
            ControllerW::CreateMemo($userId, "Test0", Bucket::BUCKET_HOT_LIST),
            ControllerW::CreateMemo($userId, "Test1", Bucket::BUCKET_HOT_LIST)
        );

        Batch::RunAll();

        $this->assertBucket('A', $userId, $allMemos, Bucket::BUCKET_HOT_LIST);
        $this->setLastBucketChangeDaysAgo($allMemos, Batch::DAYS_DONE_TO_TRASH - 2);

        Batch::RunAll();

        $this->assertBucket('B', $userId, $allMemos, Bucket::BUCKET_HOT_LIST);

        foreach ($allMemos as $m)
            ControllerW::SetBucket($userId, $m, Bucket::BUCKET_DONE);

        // second one as if old enough, first one not
        $this->setLastBucketChangeDaysAgo(array($allMemos[0]), Batch::DAYS_DONE_TO_TRASH - 2);
        $this->setLastBucketChangeDaysAgo(array($allMemos[1]), Batch::DAYS_DONE_TO_TRASH + 2);
        Batch::RunAll();
        $this->assertBucket('C', $userId, array($allMemos[0]), Bucket::BUCKET_DONE);
        $this->assertBucket('D', $userId, array($allMemos[1]), Bucket::BUCKET_TRASH);

        $this->setLastBucketChangeDaysAgo(array($allMemos[0]), Batch::DAYS_DONE_TO_TRASH + 2);
        Batch::RunAll();
        $this->assertBucket('E', $userId, $allMemos, Bucket::BUCKET_TRASH);

        // first one, as if old enough, second one not
        $this->setLastBucketChangeDaysAgo(array($allMemos[0]), Batch::DAYS_TRASH_TO_DELETED + 2);
        $this->setLastBucketChangeDaysAgo(array($allMemos[1]), Batch::DAYS_TRASH_TO_DELETED - 2);
        Batch::RunAll();
        $this->assertBucket('F', $userId, array($allMemos[0]), Bucket::BUCKET_DELETED);
        $this->assertBucket('G', $userId, array($allMemos[1]), Bucket::BUCKET_TRASH);

        $this->setLastBucketChangeDaysAgo(array($allMemos[1]), Batch::DAYS_TRASH_TO_DELETED + 2);
        Batch::RunAll();
        $this->assertBucket('H', $userId, $allMemos, Bucket::BUCKET_DELETED);

        /*// make sure they're nuked (or at least still deleted)
        $this->setLastBucketChangeDaysAgo(array($allMemos[0]), Batch::BATCH_NUKE_DELETED + 2);
        Batch::RunAll();
        $this->assertMemoNotDeleted('I', $allMemos[0], false);
        $this->assertMemoNotDeleted('J', $allMemos[1], true);

        $this->setLastBucketChangeDaysAgo(array($allMemos[1]), Batch::BATCH_NUKE_DELETED + 2);
        Batch::RunAll();
        $this->assertMemoNotDeleted('K', $allMemos[1], false);*/

        $this->finished = true;
    }

    public function OkForProduction()
    {
        return false;
    }

    private function setLastBucketChangeDaysAgo($memos, $days)
    {
        // I'm so sorry for this lazy hack
        switch (count($memos)) {
            case 3:
                Database::ExecutePreparedStatement("UPDATE shared_memo_status SET last_bucket_change=DATE_SUB(NOW(), INTERVAL $days day) WHERE memo_id=? OR memo_id=? OR memo_id=?",
                    'iii', $memos);
                break;
            case 2:
                Database::ExecutePreparedStatement("UPDATE shared_memo_status SET last_bucket_change=DATE_SUB(NOW(), INTERVAL $days day) WHERE memo_id=? OR memo_id=?",
                    'ii', $memos);
                break;
            case 1:
                Database::ExecutePreparedStatement("UPDATE shared_memo_status SET last_bucket_change=DATE_SUB(NOW(), INTERVAL $days day) WHERE memo_id=?",
                    'i', $memos);
                break;
        }
    }
}