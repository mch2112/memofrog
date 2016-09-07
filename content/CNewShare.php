<?php

class CNewShare extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_NEW_SHARE;
    }

    public function AuthLevel()
    {
        return LoginStatus::OK;
    }

    public function Render($userId)
    {
        $friendName = UserInput::Extract(Key::KEY_SHARE_WITH_SCREEN_NAME, '');

        if (strlen($friendName) > 0) {
            $friendId = Account::GetUserIdFromScreenName($friendName);
        } else {
            $friendId = UserInput::Extract(Key::KEY_FRIEND_ID, 0);
            if ($friendId > 0)
                $friendName = Account::GetScreenName($friendId);
        }

        $tags = UserInput::Extract(Key::KEY_SHARE_TAGS, '');
        $canEdit = UserInput::Extract(Key::KEY_SHARE_CAN_EDIT, false);

        if (strlen($tags) > 0) {
            $response = $this->createShare($userId, $friendName, $tags, $canEdit);

            $message = $response['message'];

            if (strlen($message) > 0)
                self::$alert = $message;

            if (!$response['error']) {
                Session::SetSessionVal(Key::KEY_FRIEND_ID, $friendId);
                $this->redirect = ContentKey::CONTENT_KEY_FRIEND;
                return;
            }
        }

        $cancelButton = $friendId > 0
            ? self::GetNavLinkWithArgs('Cancel', array(Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_FRIEND, Key::KEY_FRIEND_ID => $friendId), 'standard_button cancel')
            : Html::CancelButton(ContentKey::CONTENT_KEY_FRIENDS);

        $backButton = $friendId > 0
            ? self::GetNavLinkWithArgs('&lt; Back', array(Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_FRIEND, Key::KEY_FRIEND_ID => $friendId), 'link_button')
            : Html::LinkButton('&lt; Back', ContentKey::CONTENT_KEY_FRIENDS);

        $nameAutoFocus = strlen($friendName) === 0;

        $editIcon = Html::Icon('edit');

        $html =
            Html::Div('submenu',
                $backButton) .
            Html::Heading('New Share') .
            Html::Div('instructions', 'Share memos with friends by choosing which tags to share and who can see them.') .
            Html::Form(
                Html::Fieldset(
                    Html::Input(Key::KEY_SHARE_WITH_SCREEN_NAME, Key::KEY_SHARE_WITH_SCREEN_NAME, $friendName, 'text', 'Share With (Screen name)', 'Screen name of whom you are sharing with', true, $nameAutoFocus, '@?\w+') .
                    Html::DivWithId(Key::KEY_VALIDATION_TARGET_SCREEN_NAME, 'validation', '') .
                    Html::Input(Key::KEY_SHARE_TAGS, Key::KEY_SHARE_TAGS, $tags, 'text', 'Tags (Specify more than one tag to make them all required to share. Separate with spaces.)', 'One or more tags to share', true, !$nameAutoFocus, '(#?\w+)(#?\w+| )*') .
                    Html::DivWithId(Key::KEY_VALIDATION_TARGET_TAGS, 'validation', '') .
                    Html::Checkbox(Key::KEY_SHARE_CAN_EDIT, Key::KEY_SHARE_CAN_EDIT, $canEdit, "$editIcon&nbsp;Allow this user to edit these memos.") .
                    Html::HiddenInput(Key::KEY_CONTENT_REQ, ContentKey::CONTENT_KEY_NEW_SHARE)) .
                Html::Fieldset(
                    Html::Div('button_row', $cancelButton . Html::SubmitButton('Create New Share', true)))
            );
        $this->html = $html;

        $this->script = self::GetValidationScript(Validation::VALIDATION_TYPE_NEW_SHARE, array(Key::KEY_SHARE_WITH_SCREEN_NAME, Key::KEY_SHARE_TAGS));
    }

    private function createShare($sourceUserId, $targetUserScreenName, $tagsAsString, $canEdit)
    {
        if (substr($targetUserScreenName, 0, 1) == '@')
            $targetUserScreenName = substr($targetUserScreenName, 1);

        $targetUserId = Account::GetUserIdFromScreenName($targetUserScreenName);

        if ($targetUserId == 0)
            return array('message' => "Error creating share: user '$targetUserScreenName' not found.", 'error' => true);

        if ($sourceUserId == $targetUserId)
            return array('message' => "You can't share with yourself.", 'error' => true);

        if (strlen($tagsAsString) == 0)
            return array('message' => 'Tags needed.', 'error' => true);

        ControllerW::FindOrCreateShare($sourceUserId, $targetUserId, $tagsAsString, true, true, $canEdit, $isNew);
        if ($isNew)
            return array('message' => 'Share added.', 'error' => false);
        else
            return array('message' => 'Share already exists.', 'error' => false);
    }
}