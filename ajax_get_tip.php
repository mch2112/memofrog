<?php

require_once('global.php');

const NUM_TIPS = 33;

$userId = Session::DoAjaxSession(true);

if (Session::IsLoggedIn()) {
    $tipOption = Option::GetOptionValue($userId, Option::OPTION_SHOW_TIPS);
    if ($tipOption === Option::OPTION_VALUE_SHOW_TIPS_ALWAYS || (!Session::IsMobile() && $tipOption === Option::OPTION_VALUE_SHOW_TIPS_LARGE_SCREEN)) {
        $lastTipId = $tipId = UserInput::Extract(Key::KEY_TIP_ID, 0);

        do {
            $tipId = mt_rand(1, NUM_TIPS);
        } while ($tipId == $lastTipId);

        /*if ($tipId <= 0)
            $tipId = mt_rand(1, NUM_TIPS);
        else
            $tipId = $tipId % NUM_TIPS + 1;*/

        switch ($tipId) {
            case 1:
                $tip = '<strong>Tip:</strong> You can turn off these tips by disabling them on the ' . getIconLink('account', 'Account', ContentKey::CONTENT_KEY_ACCOUNT) . ' screen.';
                break;
            case 2:
                $tip = '<strong>Tip:</strong> Share ' . getTag('chores') . ' with family members by creating a share with that tag. Go to the ' . getIconLink('friends', 'Friends', ContentKey::CONTENT_KEY_FRIENDS) . ' screen to add a share.';
                break;
            case 3:
                $tip = '<strong>Tip:</strong> You can copy and paste website URLs into memos and they&apos;ll become clickable.';
                break;
            case 4:
                $tip = '<strong>Tip:</strong> Create lots of memos and include just one or two things on each one. It&apos;s easier when you don&apos;t put too many ideas into a single memo.';
                break;
            case 5:
                $tip = '<strong>Tip:</strong> View a complete history of any memo by clicking on the ' . Html::Icon('details') . ' Details icon.';
                break;
            case 6:
                $tip = '<strong>Idea:</strong> Memofrog is an ideal place to keep a daily ' . getIconLinkToBucket(Bucket::BUCKET_JOURNAL) . '.';
                break;
            case 7:
                $tip = '<strong>Idea:</strong> Memofrog is a great way to remember your gift giving ideas. Tag ideas with ' . getTag('gift') . '.';
                break;
            case 8:
                $tip = '<strong>Idea:</strong> Memofrog is a great place to make a list of ' . getTag('projects') . ' to do around the house.';
                break;
            case 9:
                $tip = '<strong>Idea:</strong> Copy and paste the URLs of articles on the web that you want to follow-up on and mark them with ' . getTag('reading') . '.';
                break;
            case 10:
                $tip = '<strong>Idea:</strong> Going on a trip? Use Memofrog to remember flight information, car rental details, hotel addresses, etc.';
                break;
            case 11:
                $tip = '<strong>Tip:</strong> Use the ' . getIconLink('tags', 'Tags', ContentKey::CONTENT_KEY_TAGS) . ' screen to see all of the tags in your memos and easily find what you&apos;re looking for.';
                break;
            case 12:
                $tip = '<strong>Tip:</strong> Use ' . Html::Icon('star_on') . ' Stars to identify which memos you need to deal with immediately.';
                break;
            case 13:
                $tip = '<strong>Hint:</strong> Starred ' . Html::Icon('star_on') . ' memos appear at the top of each list (except in the ' . getIconLinkToBucket(Bucket::BUCKET_JOURNAL) . ', ' . getIconLinkToBucket(Bucket::BUCKET_DONE) . ', and ' . getIconLinkToBucket(Bucket::BUCKET_TRASH) . ' views).';
                break;
            case 14:
                $tip = '<strong>Hint:</strong> You can use Memofrog on any computer or mobile device with a modern browser. No need to sync; all your memos are available whenever you&apos;re connected.';
                break;
            case 15:
                $tip = '<strong>Note:</strong> Memofrog uses ' . getTag('tags') . ' instead of categories. You can have more than one tag on a memo, and you can easily find a memo using any of its tags.';
                break;
            case 16:
                $tip = '<strong>Tip:</strong> When you set an ' . Html::Icon('alarm_on') . ' Alarm for a memo, it will remain unstarred until the alarm date. Then, it will get a ' . Html::Icon('star_on') . ' Star and you&apos;ll get a reminder email. Starred memos come to the top of the list.';
                break;
            case 17:
                $tip = '<strong>Tip:</strong> Share a single memo with another user without using tags by including their screen name preceded by an @ sign, for example: ' . getScreenName('froggy') . '.';
                break;
            case 18:
                $tip = '<strong>Tip:</strong> Use a separate memo for each ' . getTag('shopping') . ' list item, and mark them as ' . getIconLinkToBucket(Bucket::BUCKET_DONE) . ' as you buy each one.';
                break;
            case 19:
                $tip = '<strong>Tip:</strong> The ' . getIconLinkToBucket(Bucket::BUCKET_REFERENCE) . ' bucket is a good place for information you&apos;ll only need to refer to occasionally. You can find what you need with ' . getIconLink('tags', 'Tags', ContentKey::CONTENT_KEY_TAGS) . ' or ' . getIconLink('find', 'Search', ContentKey::CONTENT_KEY_FIND) . '.';
                break;
            case 20:
                $tip = '<strong>Tip:</strong> Go to the ' . getIconLink('friends', 'Friends', ContentKey::CONTENT_KEY_FRIENDS) . ' screen to see everyone you&apos;re sharing with and who is sharing with you.';
                break;
            case 21:
                $tip = '<strong>Hint:</strong> The ' . getIconLinkToBucket(Bucket::BUCKET_B_LIST) . ' list is for things you&apos;ll need to deal with -- but not right now.';
                break;
            case 22:
                $tip = '<strong>Hint:</strong> The ' . getIconLinkToBucket(Bucket::BUCKET_EVERYTHING) . ' view includes all your memos except for those in the ' . getIconLinkToBucket(Bucket::BUCKET_TRASH) . '.';
                break;
            case 23:
                $tip = '<strong>Hint:</strong> On mobile devices, click on Froggy on the upper left to see more options, including ' . getIconLink('account', 'Account', ContentKey::CONTENT_KEY_ACCOUNT) . ' settings.';
                break;
            case 24:
                $tip = '<strong>Tip:</strong> If you can do it in 2 minutes, just do it. Otherwise put it in Memofrog.';
                break;
            case 25:
                $tip = '<strong>Hint:</strong> Friends who share memos can have the same memo but in different buckets. For example, you might have a memo in ' . getIconLinkToBucket(Bucket::BUCKET_B_LIST) . ' while your friend may have put it in her ' . getIconLinkToBucket(Bucket::BUCKET_DONE) . ' bucket.';
                break;
            case 26:
                $tip = '<strong>Hint:</strong> Memos in your ' . getIconLinkToBucket(Bucket::BUCKET_DONE) . ' folder will automatically move to ' . getIconLinkToBucket(Bucket::BUCKET_TRASH) . ' after ' . strval(Batch::DAYS_DONE_TO_TRASH) . ' days unless you turn this off on the ' . getIconLink('account', 'Account', ContentKey::CONTENT_KEY_ACCOUNT) . ' screen.';
                break;
            case 27:
                $tip = '<strong>Hint:</strong> When friends share memos, one can ' . Html::Icon('star_on') . ' Star the memo but that won&apos;t affect the other friend. Stars are not shared between friends.';
                break;
            case 28:
                $tip = '<strong>Hint:</strong> When the original author ' . Html::Icon('bucket410') . ' Deletes a memo (like when it is emptied from the ' . getIconLinkToBucket(Bucket::BUCKET_TRASH) . '), that memo will no longer be available to anyone, even if it had been shared with friends.';
                break;
            case 29:
                $tip = '<strong>Tip:</strong> To make sure a memo is never shared, click the ' . Html::Icon('edit') . ' Edit icon and then check the ' . Html::Icon('private_on') . ' Private box.';
                break;
            case 30:
                $tip = '<strong>Hint:</strong> See the ' . getIconLink('find', ' Find', ContentKey::CONTENT_KEY_FIND) . ' view for special searches.';
                break;
            case 31:
                $tip = '<strong>Idea:</strong> Like Memofrog? Tell your friends!';
                break;
            case 32:
                $tip = '<strong>Idea:</strong> For things you want to accomplish today, '. Html::Icon('star_on') .' Star them and put them on your ' . Html::Icon('hotlist') . ' Hotlist.';
                break;
            case 33:
            default :
                $tip = '<strong>Idea:</strong> Use tags in Memofrog to remember ' . getTag('books') . ' or ' . getTag('movies') . ' that you&apos;ve been meaning to see.';
                break;
        }
        Response::RenderJsonResponse(
            array(
                Key::KEY_TIPS_DISABLED => false,
                Key::KEY_TIP => $tip,
                Key::KEY_TIP_ID => $tipId));
        exit(0);
    }
}
Response::RenderJsonResponse(array(Key::KEY_TIPS_DISABLED => true));
exit(0);

function getIconLink($class, $caption, $contentKey)
{
    return Html::Icon($class) . '&nbsp;' . Content::GetNavLink($caption, $contentKey);
}
function getIconLinkToBucket($bucket)
{
    return Html::Icon(Bucket::GetBucketClass($bucket)) . '&nbsp;' .
        Content::GetNavLinkWithArgs(Bucket::GetShortBucketName($bucket),
            array(Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_HOME,
                  Key::KEY_CLEAR_FILTERS => true,
                  Key::KEY_BUCKET => $bucket));
}
function getIconLinkWithArgs($class, $caption, array $args)
{
    return Html::Icon($class) . '&nbsp;' . Content::GetNavLinkWithArgs($caption, $args);
}

function getTag($tag)
{
    return "<span class=\"hashtag\">#$tag</span>";
}
function getScreenName($screenName)
{
    return "<span class=\"hashtag\">@$screenName</span>";
}
