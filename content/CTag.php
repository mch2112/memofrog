<?php

class CTag extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_TAG;
    }

    public function AuthLevel()
    {
        return LoginStatus::OK;
    }

    public function Render($userId)
    {
        $tag = UserInput::Extract(Key::KEY_TAG, "");
        if (strlen($tag))
            $tagId = Database::LookupValue('tags', 'tag', $tag, 's', 'id', 0);
        else
            $tagId = UserInput::Extract(Key::KEY_TAG_ID, 0);

        if ($tagId > 0) {
            self::$response = array(Key::KEY_TAG => $tag);
            $tag = Database::LookupValue('tags', 'id', $tagId, 'i', 'tag', '');

            $delBucket = Bucket::BUCKET_DELETED;
            $sql = "SELECT bucket, count(*) AS count FROM shared_memo_status INNER JOIN memos_tags ON shared_memo_status.memo_id=memos_tags.memo_id WHERE shared_memo_status.user_id=$userId AND visible=1 AND memos_tags.tag_id=$tagId AND bucket<$delBucket AND memos_tags.valid=1 GROUP BY bucket ORDER BY count DESC";

            $stats = array();
            $total = 0;
            $trashCount = 0;
            $hiddenCount = 0;

            Database::QueryCallback($sql, function ($row) use (&$stats, &$total, &$trashCount, &$hiddenCount) {
                $bucket = (int)$row['bucket'];
                $count = (int)$row['count'];
                switch ($bucket) {
                    case Bucket::BUCKET_TRASH:
                        $trashCount += $count;
                        break;
                    case Bucket::BUCKET_HIDDEN:
                        $hiddenCount += $count;
                        break;
                    default:
                        $stats[$bucket] = $count;
                        $total += $count;
                }
            });

            $html =
                Html::Div('submenu',
                    self::GetNavLink('&lt; Tags', ContentKey::CONTENT_KEY_TAGS, 'link_button') .
                    Html::Div('spacer', '') .
                    self::GetNavLinkWithArgs('Rename', array(Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_RENAME_TAG, Key::KEY_RENAME_TAG_OLD_TAG => $tag), 'link_button')) .
                Html::Heading('#' . $tag) .
                Html::P(
                    self::Pluralize($total, 'memo', true) . ' ' . self::pluralizedVerb($total) . ' tagged with ' .
                    self::GetNavLinkWithArgs(Html::Span('hashtag', '#' . $tag),
                        array(Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_HOME, Key::KEY_CLEAR_FILTERS => true, Key::KEY_FILTER_TAGS => $tag, Key::KEY_BUCKET => Bucket::BUCKET_EVERYTHING)) . ':');

            foreach ($stats as $maxBucket => $count) {
                $html .= $this->renderBucketInfo($count, $tag, $maxBucket);
            }

            if ($trashCount > 0 || $hiddenCount > 0) {
                $html .= Html::P('In addition to ' . ($total > 1 ? 'these:' : 'this:'));
                if ($hiddenCount > 0)
                    $html .= $this->renderBucketInfo($hiddenCount, $tag, Bucket::BUCKET_HIDDEN);
                if ($trashCount > 0)
                    $html .= $this->renderBucketInfo($trashCount, $tag, Bucket::BUCKET_TRASH);

            }

            $this->html = $html;

        } else {
            self::setError(ErrorCode::ERROR_TAG_NOT_FOUND);
            return;
        }
    }

    /**
     * @param $count
     * @param $tag
     * @param $bucket
     * @return string
     */
    public function renderBucketInfo($count, $tag, $bucket)
    {
        $linkText = self::GetJavaScriptLink(self::Pluralize($count, 'memo', true), "filterByTagAndBucket(\"$tag\", $bucket);return false;");

        $foo = Html::P(
            Html::LargeIcon(Bucket::GetBucketClass($bucket)) .
            $linkText . ' ' . self::pluralizedVerb($count) . ' in ' . Html::Tag('strong', Bucket::GetShortBucketName($bucket)) . '.');
        return $foo;
    }

}