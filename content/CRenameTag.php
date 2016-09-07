<?php

class CRenameTag extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_RENAME_TAG;
    }

    public function AuthLevel()
    {
        return LoginStatus::OK;
    }

    public function Render($userId)
    {
        $this->cacheable = self::CACHEABLE_NEVER;
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;

        $oldTag = UserInput::Extract(Key::KEY_RENAME_TAG_OLD_TAG, '');
        $newTag = UserInput::Extract(Key::KEY_RENAME_TAG_NEW_TAG, '');

        if (strlen($oldTag) && strlen($newTag))
        {
            $count = ControllerW::RenameTag($userId, $oldTag, $newTag);
            if ($count === 0) {
                self::$alert = 'None of your memos have the tag ' . Html::Span('hashtag', '#' . $oldTag) . '. Tags must match exactly.';
            } else {
                self::$alert = 'Tag ' . Html::Span('hashtag', '#' . $oldTag) . ' renamed to ' . Html::Span('hashtag', '#' . $newTag) . ' in ' . self::Pluralize($count, 'memo') . '.';
                $tagId = Controller::GetTagId($newTag);
                UserInput::Store(Key::KEY_TAG_ID, $tagId);
                $this->redirect = ContentKey::CONTENT_KEY_TAG;
                return;
            }
        }

        $tagPattern = '(#?\w+)(#?\w+)*';

        $this->html =
            Html::Div('submenu',
                self::GetNavLink('&lt; Back', ContentKey::CONTENT_KEY_TAGS, 'link_button')) .
            Html::Heading('Rename Tag') .
            Html::Div('instructions', 'You can replace all uses of a particular tag in all the memos you have written. This will not change any memos written by your friends. This cannot be undone!') .
            Html::Form(
                Html::Fieldset(
                    Html::Input(Key::KEY_RENAME_TAG_OLD_TAG, Key::KEY_RENAME_TAG_OLD_TAG, $oldTag, 'text', 'Old Tag', 'Old Tag', true, !strlen($oldTag), $tagPattern) .
                    Html::Input(Key::KEY_RENAME_TAG_NEW_TAG, Key::KEY_RENAME_TAG_NEW_TAG, $newTag, 'text', 'New Tag', 'New Tag', true, strlen($oldTag), $tagPattern) .
                    Html::HiddenInput(Key::KEY_CONTENT_REQ, ContentKey::CONTENT_KEY_RENAME_TAG)) .
                Html::Fieldset( Html::Div('button_row',
                    Html::CancelButton(ContentKey::CONTENT_KEY_TAGS) .
                    Html::SubmitButton('Rename')))
            );
    }
}