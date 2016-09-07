<?php

class View
{
    /* @param $userId int
     * @param $memoId int
     * @param $to bool
     * @param $suppressCanEdit bool
     * @return string
     */
    public static function RenderUserBucketWidget($userId, $memoId, $to = false, $suppressCanEdit = false)
    {
        $sql = "SELECT users.screen_name AS screen_name, shared_memo_status.bucket AS bucket, shared_memo_status.star AS star, shared_memo_status.alarm_date IS NOT NULL as alarm, shared_memo_status.can_edit AS can_edit FROM users INNER JOIN shared_memo_status ON users.id=shared_memo_status.user_id WHERE users.id=$userId AND shared_memo_status.memo_id=$memoId";
        $row = Database::QueryOneRow($sql);
        $screenName = $row['screen_name'];
        $bucket = (int)$row['bucket'];
        $star = $row['star'] ? 1 : 0;
        $alarm = $row['alarm'] ? 1 : 0;
        if ($suppressCanEdit)
            $canEdit = false;
        else
            $canEdit = $row['can_edit'] ? 1 : 0;

        return self::getUserBucketWidget(self::RenderScreenNameLinkFromString($screenName, $to), $bucket, $star, $alarm, $canEdit);
    }
    /* @param $userId int
     * @return string
     */
    public static function RenderScreenNameLinkFromId($userId)
    {
        return self::RenderScreenNameLinkFromString(Database::LookupValue('users', 'id', $userId, 'i', 'screen_name', ''));
    }

    /* @param $screenName string
     * @param $to bool
     * @return string
     */
    public static function RenderScreenNameLinkFromString($screenName, $to = false)
    {
        $toStr = $to ? 'true' : 'false';
        return "<a class=\"screen_name\" href=\"\" onclick='filterByScreenName(\"$screenName\", $toStr);return false;'>@$screenName</a>";
    }
    /* @param $intro string
     * @param $caption string
     * @return string
     */
    public static function RenderInfoWidget($intro, $caption)
    {
        return Html::Div('info_widget',
            Html::Span('info_intro', $intro) .
            Html::Span('info_caption', $caption));
    }
    

    /* @param $screenName string
     * @param $bucket int
     * @param $star bool
     * @param $alarm bool
     * @param $canEdit bool
     * @return string
     */
    private static function getUserBucketWidget($screenName, $bucket, $star, $alarm, $canEdit)
    {
        return Html::Div('user_memo_bucket_widget',
                        Html::Div('screen_name', $screenName) .
                            Html::Div('bucket_info img_bucket' . strval($bucket), '') .
                            ($star ? Html::Div('bucket_info img_star_on', '') : '') .
                            ($alarm ? Html::Div('bucket_info img_alarm_on', '') : '') .
                            ($canEdit ? Html::Div('bucket_info img_edit', '') : '')
                            );
    }
}