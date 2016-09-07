<?php

class TTagVisibility extends Test
{
    function __construct()
    {
        $this->name = 'Tag Visibility';
    }

    public function Run()
    {

        $userId_1 = $this->GetNewUser();

        $tag0 = $this->GetUniqueIdentifier('#alpha');
        $tag1 = $this->GetUniqueIdentifier('#beta');
        $tag2 = $this->GetUniqueIdentifier('#gamma');

        $memo0 = ControllerW::CreateMemo($userId_1, "Test0", Bucket::BUCKET_HOT_LIST);
        $memo1 = ControllerW::CreateMemo($userId_1, "Test1: $tag0 $tag1", Bucket::BUCKET_HOT_LIST);
        $memo2 = ControllerW::CreateMemo($userId_1, "Test2: $tag1 $tag0", Bucket::BUCKET_HOT_LIST);
        $memo3 = ControllerW::CreateMemo($userId_1, "Test3: $tag0", Bucket::BUCKET_HOT_LIST);
        $memo4 = ControllerW::CreateMemo($userId_1, "Test4: $tag1", Bucket::BUCKET_HOT_LIST);

        $pageSize = 10000;

        // ALL MEMOS
        $memos = Controller::GetMemoData($pageSize, 0, $userId_1, 0, false, '', Sql::FILTER_TAGS_OP_ALL, '', Bucket::BUCKET_EVERYTHING, Search::SPECIAL_SEARCH_NONE);
        $memosReturned = array();
        foreach ($memos as $m)
            $memosReturned[] = $m[Key::KEY_MEMO_ID];
        $memosExpected = array($memo0, $memo1, $memo2, $memo3, $memo4);
        $this->assertIsTrue('A', $this->ArraysAreIdentical($memosExpected, $memosReturned), 'All Memos: Memo mismatch - Expected:' . json_encode($memosExpected) . ' Returned: ' . json_encode($memosReturned));

        // ONE TAG
        $memos = Controller::GetMemoData($pageSize, 0, $userId_1, 0, false, substr($tag0, 1), Sql::FILTER_TAGS_OP_ALL, '', Bucket::BUCKET_EVERYTHING, Search::SPECIAL_SEARCH_NONE);
        $memosReturned = array();
        foreach ($memos as $m)
            $memosReturned[] = $m[Key::KEY_MEMO_ID];
        $memosExpected = array($memo1, $memo2, $memo3);
        $this->assertIsTrue('B', $this->ArraysAreIdentical($memosExpected, $memosReturned), 'One Tag: Memo mismatch - Expected:' . json_encode($memosExpected) . ' Returned: ' . json_encode($memosReturned));

        // TWO TAGS
        $memos = Controller::GetMemoData($pageSize, 0, $userId_1, 0, false, substr($tag0, 1) . '+' . substr($tag1, 1), Sql::FILTER_TAGS_OP_ALL, '', Bucket::BUCKET_EVERYTHING, Search::SPECIAL_SEARCH_NONE);
        $memosReturned = array();
        foreach ($memos as $m)
            $memosReturned[] = $m[Key::KEY_MEMO_ID];
        $memosExpected = array($memo1, $memo2);
        $this->assertIsTrue('C', $this->ArraysAreIdentical($memosExpected, $memosReturned), 'Two Tags: Memo mismatch - Expected:' . json_encode($memosExpected) . ' Returned: ' . json_encode($memosReturned));

        // NON-EXISTENT TAG
        $memos = Controller::GetMemoData($pageSize, 0, $userId_1, 0, false, substr($tag2, 1), Sql::FILTER_TAGS_OP_ALL, '', Bucket::BUCKET_EVERYTHING, Search::SPECIAL_SEARCH_NONE);
        $memosReturned = array();
        foreach ($memos as $m)
            $memosReturned[] = $m[Key::KEY_MEMO_ID];
        $memosExpected = array();
        $this->assertIsTrue('D', $this->ArraysAreIdentical($memosExpected, $memosReturned), 'Non-Existent Tag: Memo mismatch - Expected:' . json_encode($memosExpected) . ' Returned: ' . json_encode($memosReturned));

        $this->finished = true;
    }

    public function OkForProduction()
    {
        return false;
    }
}
