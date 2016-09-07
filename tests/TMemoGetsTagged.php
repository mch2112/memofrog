<?php

class TMemoGetsTagged extends Test
{
    function __construct()
    {
        $this->name = 'Memo Gets Tagged';
    }

    public function Run()
    {
        $userId = $this->getNewUser();

        // note: sorted
        $tag0 = $this->GetUniqueIdentifier('#alpha');
        $tag1 = $this->GetUniqueIdentifier('#beta');
        $tag2 = $this->GetUniqueIdentifier('#gamma');
        $tag3 = $this->GetUniqueIdentifier('#omega');

        // new tags
        $this->doTest($userId, $tag1, $tag3, $tag2, $tag0);

        // tags in use
        $this->doTest($userId, $tag1, $tag3, $tag2, $tag0);

        $this->finished = true;

    }

    public function OkForProduction()
    {
        return false;
    }

    public function doTest($userId, $tag1, $tag3, $tag2, $tag0)
    {
        $memoText = "Test: $tag1 $tag3 $tag2 $tag0";
        $memoId = ControllerW::CreateMemo($userId, $memoText, Bucket::BUCKET_HOT_LIST);

        $index = 0;
        $tagsFound = array();
        $sql = "SELECT tags.tag FROM memos_tags INNER JOIN tags ON memos_tags.tag_id=tags.id WHERE memos_tags.memo_id=$memoId AND memos_tags.valid=1";

        Database::QueryCallback($sql, function ($row) use (&$tagsFound, &$index) {
            $tagsFound[$index++] = $row['tag'];
        });

        $count = count($tagsFound);
        $this->assertIsTrue('A', $count == 4, "Wrong number of tags on memo $memoId, should be 4 not $count");
        sort($tagsFound);
        $this->assertIsTrue('B', ('#' . $tagsFound[0]) == $tag0, "Tag $tag0 not on memo");
        $this->assertIsTrue('C', ('#' . $tagsFound[1]) == $tag1, "Tag $tag1 not on memo");
        $this->assertIsTrue('D', ('#' . $tagsFound[2]) == $tag2, "Tag $tag2 not on memo");
        $this->assertIsTrue('E', ('#' . $tagsFound[3]) == $tag3, "Tag $tag3 not on memo");

        $memoText = "Test: $tag1 $tag3 $tag0";
        ControllerW::EditMemo($userId, $memoId, $memoText, false);

        $index = 0;
        $tagsFound = array();
        Database::QueryCallback($sql, function ($row) use (&$tagsFound, &$index) {
            $tagsFound[$index++] = $row['tag'];
        });
        $count = count($tagsFound);
        $this->assertIsTrue('F', $count == 3, "Wrong number of tags on memo $memoId, should be 3 not $count");
        sort($tagsFound);
        $this->assertIsTrue('G', ('#' . $tagsFound[0]) == $tag0, "Tag $tag0 not on memo");
        $this->assertIsTrue('H', ('#' . $tagsFound[1]) == $tag1, "Tag $tag1 not on memo");
        $this->assertIsTrue('I', ('#' . $tagsFound[2]) == $tag3, "Tag $tag3 not on memo");
    }
}
