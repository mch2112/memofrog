<?php

class TMemoShareVisibility extends Test
{
    function __construct()
    {
        $this->name = 'Memo Share Visibility';
    }

    public function Run()
    {
        $userId_1 = $this->GetNewUser();
        $memos = array(ControllerW::CreateMemo($userId_1, 'Test: #one #two #three', Bucket::BUCKET_HOT_LIST),
            ControllerW::CreateMemo($userId_1, 'Test: #one #two', Bucket::BUCKET_HOT_LIST),
            ControllerW::CreateMemo($userId_1, 'Test: #one #three', Bucket::BUCKET_HOT_LIST),
            ControllerW::CreateMemo($userId_1, 'Test: #two #three', Bucket::BUCKET_HOT_LIST),
            ControllerW::CreateMemo($userId_1, 'Test: #one', Bucket::BUCKET_HOT_LIST),
            ControllerW::CreateMemo($userId_1, 'Test: #two', Bucket::BUCKET_HOT_LIST),
            ControllerW::CreateMemo($userId_1, 'Test: #three', Bucket::BUCKET_HOT_LIST));

        $userId_2 = $this->getNewUser();

        // user 2 can't see anything
        $this->assertMemoVisibility('A', $userId_1, $userId_2, $memos, array());

        $shares = array();
        $shares[0] = ControllerW::FindOrCreateShare($userId_1, $userId_2, 'one two three', true, true, false, $isNew);

        // but now can see the first one because it has all three tags and that's shared...
        $this->assertMemoVisibility('B', $userId_1, $userId_2, $memos, array($memos[0]));

        // disable share, make sure user can't see it
        ControllerW::SetShareEnable($shares[0], true, false);
        $this->assertMemoVisibility('C', $userId_1, $userId_2, $memos);

        // and re-enable
        ControllerW::SetShareEnable($shares[0], true, true);
        $this->assertMemoVisibility('D', $userId_1, $userId_2, $memos, array($memos[0]));

        // now outright delete
        ControllerW::DeleteShare($shares[0], 0, false);
        $this->assertMemoVisibility('E', $userId_1, $userId_2, $memos);

        // and undelete
        ControllerW::DeleteShare($shares[0], 0, true);
        $this->assertMemoVisibility('G', $userId_1, $userId_2, $memos, array($memos[0]));

        // another share
        $shares[1] = ControllerW::FindOrCreateShare($userId_1, $userId_2, 'one two', true, true, false, $isNew);
        $this->assertMemoVisibility('H', $userId_1, $userId_2, $memos, array($memos[0], $memos[1]));

        // and a third
        $shares[2] = ControllerW::FindOrCreateShare($userId_1, $userId_2, 'two', true, true, false, $isNew);
        $this->assertMemoVisibility('I', $userId_1, $userId_2, $memos, array($memos[0], $memos[1], $memos[3], $memos[5]));

        // disable the second one, shouldn't change anything since the third one is broader
        ControllerW::SetShareEnable($shares[1], true, false);
        $this->assertMemoVisibility('J', $userId_1, $userId_2, $memos, array($memos[0], $memos[1], $memos[3], $memos[5]));
        $this->assertMemoVisibility('J1', $userId_1, $userId_2, array($memos[0]), array($memos[0]));
        $this->assertMemoVisibility('J2', $userId_1, $userId_2, array($memos[1]), array($memos[1]));
        $this->assertMemoVisibility('J3', $userId_1, $userId_2, array($memos[3]), array($memos[3]));
        $this->assertMemoVisibility('J4', $userId_1, $userId_2, array($memos[5]), array($memos[5]));

        ControllerW::SetShareEnable($shares[0], true, false);
        $this->assertMemoVisibility('K', $userId_1, $userId_2, $memos, array($memos[0], $memos[1], $memos[3], $memos[5]));

        ControllerW::SetShareEnable($shares[2], true, false);
        $this->assertMemoVisibility('L', $userId_1, $userId_2, $memos);

        ControllerW::SetShareEnable($shares[1], true, true);
        $this->assertMemoVisibility('M', $userId_1, $userId_2, $memos, array($memos[0], $memos[1]));

        // Check after memo edits
        ControllerW::EditMemo($userId_1, $memos[6], 'Revised: #two #four #one', false);
        $this->assertMemoVisibility('N', $userId_1, $userId_2, $memos, array($memos[0], $memos[1], $memos[6]));

        ControllerW::EditMemo($userId_1, $memos[1], 'Revised: #seven #nine #one #ten', false);
        $this->assertMemoVisibility('O', $userId_1, $userId_2, $memos, array($memos[0], $memos[6]));

        ControllerW::RenameTag($userId_1, 'one', 'one_1_one');
        $this->assertMemoVisibility('P', $userId_1, $userId_2, $memos, array());
        ControllerW::RenameTag($userId_1, 'one_1_one', 'one');
        $this->assertMemoVisibility('Q', $userId_1, $userId_2, $memos, array($memos[0], $memos[6]));

        // Cycle share enable on target side
        ControllerW::SetShareEnable($shares[1], false, false);
        $this->assertMemoVisibility('R', $userId_1, $userId_2, $memos);

        ControllerW::SetShareEnable($shares[1], false, true);
        $this->assertMemoVisibility('S', $userId_1, $userId_2, $memos, array($memos[0], $memos[6]));

        ControllerW::RenameTag($userId_1, 'three', 'three_3_three');
        $this->assertMemoVisibility('T', $userId_1, $userId_2, $memos, array($memos[0], $memos[6]));
        ControllerW::RenameTag($userId_1, 'three_3_three', 'three');
        $this->assertMemoVisibility('U', $userId_1, $userId_2, $memos, array($memos[0], $memos[6]));

        ControllerW::RenameTag($userId_1, 'two', 'two_2_two');
        $this->assertMemoVisibility('V', $userId_1, $userId_2, $memos);
        ControllerW::RenameTag($userId_1, 'two_2_two', 'two');
        $this->assertMemoVisibility('W', $userId_1, $userId_2, $memos, array($memos[0], $memos[6]));

        foreach ($memos as $k => $v) {
            ControllerW::SetBucket($userId_1, $v, Bucket::BUCKET_DELETED);
            $this->assertIsFalse('X'.strval($k), Controller::ComputeMemoIsShared($v), "Memo $v should not be shared after deletion");
        }

        $this->finished = true;
    }

    public function OkForProduction()
    {
        return false;
    }
}
