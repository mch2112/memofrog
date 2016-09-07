<?php

class CHelp extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_HELP;
    }

    public function AuthLevel()
    {
        return LoginStatus::ANY;
    }

    public function Render($userId)
    {
        $this->cacheable = self::CACHEABLE_ALWAYS;
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;

        $html = Html::SubHeading('About Memofrog');

        /** @noinspection SpellCheckingInspection */
        $html .= $this->renderFaqItems('What\'s this all about?',
            array('Memofrog is a place to capture all your thoughts and get to them quickly and easily -- and share the ones you want with people you know.', 'Whenever inspiration strikes, an idea pops into your head, or you just want to make sure the thought you&apos;re having doesn&apos;t get lost, Memofrog will grab hold of it and have it for you when you&apos;re ready.'));
        $html .= '<hr>';
        if (!Session::IsLoggedIn()) {
            $html .= $this->renderFaqItem('How do I use Memofrog?', "It&apos;s easy! First, " .
                self::GetNavLink('register for an account', ContentKey::CONTENT_KEY_REGISTER_USER) .
                '. Then, just start entering your thoughts. Add some #tags to make them easier to find and share later.');
            $html .= '<hr>';
        }

        $html .= $this->renderFaqItem('OK, but isn&apos;t this just another to-do app?', 'No, there are plenty of those out there you can use. We wanted something simpler and easier, and for more than just tasks.');
        $html .= '<hr>';
        $html .= $this->renderFaqItem('How much does it cost?', 'Memofrog is free.');
        $html .= '<hr>';
        $html .= $this->renderFaqItem('Really?', "Yes.");

        $html .= Html::SubHeading('Overview');
        $html .= $this->renderFaqItem('So how does it work?', 'Memofrog is a list of notes you create. It&apos;s designed for short items that you need to remember. Just start entering short notes and you will soon see how it works.');
        $html .= '<hr>';
        $html .= $this->renderFaqItems('What kind of tool is Memofrog?', array('Memofrog helps extend your memory.', 'Memofrog covers what your calendar, contacts, email, and files don&apos;t handle well. Those apps are great for schedules and direct messaging, large documents and traditional tasks. They&apos;re not very good for saving and finding the little things that are easy to forget.', 'Think of Memofrog as a replacement for writing stuff on your hand. :)'));
        $html .= '<hr>';
        $html .= $this->renderFaqItem('How should I organize Memofrog before getting started?', 'Don&apos;t worry too much about that. After you get going you&apos;ll see that not much organizing is required. Adding tags is the main thing (see the section on Tags, below).');

        if (Session::IsMobile())
            $html .= $this->renderFaqItem('How can I get back to this help screen after I sign in?', 'Click on the frog on the upper left. You&apos;ll see more options, including this help screen.');

        $html .= Html::SubHeading('Icons');
        $html .= $this->renderFaqItems('What are all those icons about?',
            array(
                "That's where you choose which &apos;bucket&apos; of memos you are looking at. Your choices are:",
                Html::LargeIcon('bucket200') . '<strong>Everything</strong>. All your memos (except for the ones in the Trash).',
                Html::LargeIcon('bucket110') . "<strong>Hot List</strong>. Your most important items that you need to keep in mind.",
                Html::LargeIcon('bucket120') . "<strong>B List</strong>. Your tasks and items that will need attention later.",
                Html::LargeIcon('bucket210') . "<strong>Journal</strong>. Day to day observations that you want to reflect on from time to time.",
                Html::LargeIcon('bucket220') . "<strong>Reference</strong>. Things you might need to look up later.",
                Html::LargeIcon('bucket250') . "<strong>Done Items</strong>. Items that you&apos;ve marked off as Done.",
                Html::LargeIcon('bucket350') . "<strong>Hidden Items</strong>. Items that you&apos;ve intentionally hidden.",
                Html::LargeIcon('bucket310') . "<strong>Trash</strong>. The system will delete these after a while.",
                Html::LargeIcon('bucket510') . "<strong>Historic</strong>. You&apos;ll see this when viewing old versions of memos."
            ));
        $html .= '<hr>';

        $menuItems = array(
            "Those are the main menu options. They are:",
            Html::LargeIcon('home') . "<strong>Home</strong>. Where you see your main list of memos.",
            Html::LargeIcon('create') . "<strong>New Memo</strong>. Create a new memo.",
            Html::LargeIcon('friends') . "<strong>Friends</strong>. You can see who you've shared with and who is sharing with you.",
            Html::LargeIcon('tags') . "<strong>Tags</strong>. The tags that are on your memos.",
            Html::LargeIcon('find') . "<strong>Search</strong>. Find a memo.");

        if (!Session::IsMobile()) $menuItems[] = Html::LargeIcon('account') . "<strong>Account</strong>. Information about your account.";

        $menuItems[] =  Html::LargeIcon('help') . "<strong>Help</strong>. This screen.";

        $html .= $this->renderFaqItems('What about those ones at the top?', $menuItems);

        $html .= '<hr>';
        $html .= $this->renderFaqItems('But there are some more?', array(
                "Oh, right. Those are a few of other things you can see on memos:",
                Html::LargeIcon('star_on') . "<strong>Star</strong>. These are memos you&apos;ve said are important. They show up at the top of the list.",
            Html::LargeIcon('private_on') . "<strong>Private</strong>. This shows memos that you've declared to be private (and they stay private, even if they would otherwise be shared.)",
                Html::LargeIcon('alarm_on') . "<strong>Alarm</strong>. This means that you&apos;ve set an alarm for this memo. When the alarm date is reached, it will be automatically starred and sent to the Hot List, and also you&apos;ll receive a reminder email.",
                Html::LargeIcon('edit') . "<strong>Edit</strong>. Click on this to edit your memo.",
            Html::LargeIcon('shared_indicator') . "<strong>Shared</strong>. This indicates that a memo you have written has been shared with a friend.",
            Html::LargeIcon('details') . "<strong>Details</strong>. Click on this to see edit history and sharing information for a memo.",
            Html::LargeIcon('sync_indicator') . "<strong>Not Synced</strong>. These memos haven't been sent to the server. You're probably working offline if you see this icon."));

        $html .= Html::SubHeading('Tags');
        $html .= $this->renderFaqItem('What&apos;s the deal with tags?', "When you use a <span class='hashtag'>#</span> sign in your memos, Memofrog recognizes the word as a tag. For example, you can use <span class='hashtag'>#todo</span> to indicate that that memo is something you want to get done later.");
        $html .= '<hr>';
        $html .= $this->renderFaqItem('Why do I care about tags?', "Tags make it easy to find what your looking for. Clicking on a tag will then show you all the memos that also have that tag. For example, when you realize something you&apos;ll need to get at the grocery store, just enter something like &quot;Milk and eggs <span class='hashtag'>#groceries</span>&quot;. Then, when you go to the grocery store, click the #groceries tag, and you&apos;ll see your complete list.");
        $html .= '<hr>';
        $html .= $this->renderFaqItem('What else?', 'You can put tags anywhere in your memo. They don&apos;t need to be at the end. Also, tags must be letters and numbers only, and are not case-sensitive.');

        $html .= Html::SubHeading('Friends and Sharing');

        $html .= $this->renderFaqItem('How does sharing work?', "You decide who you share your memos with. Sharing is done with tags. For example, you can decide to share the tag <span class='hashtag'>#shopping</span> with your friend so that she can see what you need to pick up. Then, all your memos with that tag will be visible to her.");
        $html .= '<hr>';
        $html .= $this->renderFaqItem('What else can I do with sharing and tags?', "You can share more than one tag, like <span class='hashtag'>#shopping</span> and <span class='hashtag'>#groceries</span>. In this case, only those memos with <em>both</em> tags will be visible to your friend.");
        $html .= '<hr>';
        $html .= $this->renderFaqItem('If someone is sharing with me, do I have to share with them?', 'No. You can turn off sharing with them on each individual share.');
        $html .= '<hr>';
        $html .= $this->renderFaqItem('What if I don&apos;t want to share with someone anymore?', 'You can easily disable or remove your shares on the Friends screen.');
        $html .= '<hr>';
        $html .= $this->renderFaqItem('Is there any other way to share?', 'Sure, you can include a screen name in your memo like so: <span class=\'screen_name_link\'>@froggy</span>. Now froggy will be able to see that memo, unless you&apos;ve marked it as private.');
        $html .= '<hr>';
        $html .= $this->renderFaqItem('What if I want to make sure a memo stays private?', 'Just check the \'Keep this memo private\' box in the New Memo or Edit Memo screens. You&apos;ll see a ' . Html::Icon('private_on') . 'icon so you know it&apos;s secure.');

        $html .= Html::SubHeading('More Help');

        $html .= $this->renderFaqItem('What if I have questions or technical problems?', "Drop us a line at our support desk at <a href='mailto:support@memofrog.com'>support@memofrog.com</a> and we&apos;ll get back to you right away.");

        $this->html = $html = Html::Heading('Help') .
            Html::TagWithClass('dl', 'help', $html);
    }

    private function renderFaqItem($title, $text)
    {
        return Html::Tag('dt', $title) . Html::Tag('dd', $text);
    }

    private function renderFaqItems($title, array $text)
    {
        $html = Html::Tag('dt', $title);

        foreach ($text as $t)
            $html .= Html::Tag('dd', $t);

        return $html;
    }
}