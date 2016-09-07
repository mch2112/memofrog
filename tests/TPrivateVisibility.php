<?php

class TPrivateVisibility extends Test
{
    function __construct()
    {
        $this->name = 'Private Visibility';
    }

    public function Run()
    {
        $userId_1 = $this->GetNewUser();
        $userId_2 = $this->GetNewUser();

        $tag = $this->GetUniqueIdentifier('#');

        $allMemos = array(ControllerW::CreateMemo($userId_1, "Test: $tag", Bucket::BUCKET_HOT_LIST));

        // user 2 can't see w/o share
        $this->assertMemoVisibility('A', $userId_1, $userId_2, $allMemos);

        ControllerW::FindOrCreateShare($userId_1, $userId_2, substr($tag, 1), true, true, false, $isNew);

        // but now can
        $this->assertMemoVisibility('B', $userId_1, $userId_2, $allMemos, $allMemos);

        // make private
        ControllerW::EditMemo($userId_1, $allMemos[0], $this->GetMemoText($allMemos[0]), true);

        // and again can't see
        $this->assertMemoVisibility('C', $userId_1, $userId_2, $allMemos);

        $this->finished = true;
    }

    public function OkForProduction()
    {
        return false;
    }
}
