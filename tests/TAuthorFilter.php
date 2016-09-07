<?php

class TAuthorFilter extends Test
{
    function __construct()
    {
        $this->name = 'Author Filter';
    }

    public function Run()
    {
        $tag = $this->GetUniqueIdentifier('#tag');

        $userId_1 = $this->GetNewUser();
        $userId_2 = $this->GetNewUser();
        $userId_3 = $this->GetNewUser();

        $allMemos = array(
            0 => ControllerW::CreateMemo($userId_1, "Test: $tag", Bucket::BUCKET_HOT_LIST),
            1 => ControllerW::CreateMemo($userId_1, "Test: $tag", Bucket::BUCKET_HOT_LIST),
            2 => ControllerW::CreateMemo($userId_2, "Test: $tag", Bucket::BUCKET_HOT_LIST),
            3 => ControllerW::CreateMemo($userId_2, "Test: $tag", Bucket::BUCKET_HOT_LIST),
            4 => ControllerW::CreateMemo($userId_3, "Test: $tag", Bucket::BUCKET_HOT_LIST),
            5 => ControllerW::CreateMemo($userId_3, "Test: $tag", Bucket::BUCKET_HOT_LIST)
        );

        ControllerW::FindOrCreateShare($userId_1, $userId_2, substr($tag, 1), true, true, false, $isNew);

        $pageSize = 10000;

        // 1 -> 2 with only one way, can't see
        $memos = Controller::GetMemoData($pageSize, 0, $userId_1, $userId_2, false, substr($tag, 1), Sql::FILTER_TAGS_OP_ALL, '', Bucket::BUCKET_EVERYTHING, Search::SPECIAL_SEARCH_NONE);
        $memosReturned = array();
        foreach ($memos as $m)
            $memosReturned[] = $m[Key::KEY_MEMO_ID];
        $memosExpected = array();
        $this->assertIsTrue('A', $this->ArraysAreIdentical($memosExpected, $memosReturned), "User $userId_1 viewing $userId_2: Memo mismatch - Expected:" . json_encode($memosExpected) . ' Returned: ' . json_encode($memosReturned));

        // two ways
        ControllerW::FindOrCreateShare($userId_2, $userId_1, substr($tag, 1), true, true, false, $isNew);
        $memos = Controller::GetMemoData($pageSize, 0, $userId_1, $userId_2, false, substr($tag, 1), Sql::FILTER_TAGS_OP_ALL, '', Bucket::BUCKET_EVERYTHING, Search::SPECIAL_SEARCH_NONE);
        $memosReturned = array();
        foreach ($memos as $m)
            $memosReturned[] = $m[Key::KEY_MEMO_ID];
        $memosExpected = array($allMemos[2], $allMemos[3]);
        $this->assertIsTrue('B', $this->ArraysAreIdentical($memosExpected, $memosReturned), "User $userId_1 viewing $userId_2: Memo mismatch - Expected:" . json_encode($memosExpected) . ' Returned: ' . json_encode($memosReturned));

        $this->finished = true;
    }

    public function OkForProduction()
    {
        return false;
    }
}
