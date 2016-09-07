<?php

class TShareVisibleCanEditIndependence extends Test
{
    function __construct()
    {
        $this->name = 'Share Visible and Can Edit Independence';
    }

    public function Run()
    {
        $userId_1 = $this->GetNewUser();
        $userId_2 = $this->GetNewUser();

        $tag1 = $this->GetUniqueIdentifier('#a');
        $tag2 = $this->GetUniqueIdentifier('#a');

        $memos = array(
            0 => ControllerW::CreateMemo($userId_1, "Test: $tag1 $tag2", Bucket::BUCKET_HOT_LIST),
            1 => ControllerW::CreateMemo($userId_1, "Test: none", Bucket::BUCKET_HOT_LIST)
            );

        // Basic case
        $this->assertMemoVisibility('A', $userId_1, $userId_2, $memos);
        $this->assertMemoEditability('B', $userId_1, $userId_2, $memos);

        // Turn on with share but not edit
        $shareId = ControllerW::FindOrCreateShare($userId_1, $userId_2, substr($tag1, 1), true, true, false, $isNew);
        $this->assertMemoVisibility('C', $userId_1, $userId_2, $memos, array($memos[0]));
        $this->assertMemoEditability('D', $userId_1, $userId_2, $memos);

        // Make share can_edit=true
        ControllerW::ToggleShareCanEdit($shareId);
        $this->assertMemoVisibility('E', $userId_1, $userId_2, $memos, array($memos[0]));
        $this->assertMemoEditability('F', $userId_1, $userId_2, $memos, array($memos[0]));

        // Disable share (source)
        ControllerW::SetShareEnable($shareId, true, false);
        $this->assertMemoVisibility('G', $userId_1, $userId_2, $memos);
        $this->assertMemoEditability('H', $userId_1, $userId_2, $memos);

        // Re-enable share (source)
        ControllerW::SetShareEnable($shareId, true, true);
        $this->assertMemoVisibility('I', $userId_1, $userId_2, $memos, array($memos[0]));
        $this->assertMemoEditability('J', $userId_1, $userId_2, $memos, array($memos[0]));

        // Make can_edit=false again
        ControllerW::ToggleShareCanEdit($shareId);
        $this->assertMemoVisibility('K', $userId_1, $userId_2, $memos, array($memos[0]));
        $this->assertMemoEditability('L', $userId_1, $userId_2, $memos);

        // Add new share with can_edit = true
        $shareId2 = ControllerW::FindOrCreateShare($userId_1, $userId_2, substr($tag2, 1), true, true, true, $isNew);
        $this->assertMemoVisibility('M', $userId_1, $userId_2, $memos, array($memos[0]));
        $this->assertMemoEditability('N', $userId_1, $userId_2, $memos, array($memos[0]));

        // Disable the new share, ensure still visible but can't edit
        ControllerW::SetShareEnable($shareId2, true, false);
        $this->assertMemoVisibility('O', $userId_1, $userId_2, $memos, array($memos[0]));
        $this->assertMemoEditability('P', $userId_1, $userId_2, $memos);

        $this->finished = true;
    }

    public function OkForProduction()
    {
        return false;
    }
}
