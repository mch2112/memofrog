<?php

class CAccount extends Content
{
    const OPTION_DESCRIPTION = 0;
    const OPTION_ACTION = 1;
    const OPTION_ACTION_ARGS = 2;

    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_ACCOUNT;
    }

    public function AuthLevel()
    {
        return LoginStatus::OK;
    }

    public function Render($userId)
    {
        $this->cacheable = self::CACHEABLE_NEVER;
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;

        if (UserInput::Extract(Key::KEY_TOGGLE_MOVE_DONE_TO_TRASH, false)) {
            if (Option::CycleOption($userId, Option::OPTION_MOVE_DONE_TO_TRASH))
                self::$alert = Html::Icon('bucket310') . ' Auto move to Trash enabled.';
            else
                self::$alert = Html::Icon('bucket310') . ' Auto move to Trash disabled.';
        }
        if (UserInput::Extract(Key::KEY_EMPTY_TRASH, false)) {
            ControllerW::EmptyTrash($userId);
            self::$alert = 'Trash emptied.';
        }
        if (UserInput::Extract(Key::KEY_TOGGLE_SHOW_TIPS, false)) {
            switch (Option::CycleOption($userId, Option::OPTION_SHOW_TIPS)) {
                case Option::OPTION_VALUE_SHOW_TIPS_NEVER:
                    self::$alert = 'Tips disabled.';
                    $this->script .= 'tipManager.dismissTips(true);';
                    break;
                case Option::OPTION_VALUE_SHOW_TIPS_ALWAYS:
                    self::$alert = 'Tips enabled.';
                    $this->script .= 'tipManager.showNextTip();';
                    break;
                case Option::OPTION_VALUE_SHOW_TIPS_LARGE_SCREEN:
                    self::$alert = 'Tips disabled on small screens.';
                    if (Session::IsMobile())
                        $this->script .= 'tipManager.dismissTips(false);';
                    else
                        $this->script .= 'tipManager.showNextTip();';
                    break;
            }
        }
        if (UserInput::Extract(Key::KEY_TOGGLE_EMAIL_ON_ALARM, false)) {
            if (Option::CycleOption($userId, Option::OPTION_SEND_EMAIL_ON_ALARM)) {
                self::$alert = Html::Icon('alarm_on') . ' Emails will be sent when memos alarm.';
            } else {
                self::$alert = Html::Icon('alarm_on') . ' No emails will be sent when memos alarm.';
            }
        }
        if (UserInput::Extract(Key::KEY_TOGGLE_EMAIL_ON_SHARE, false)) {
            if (Option::CycleOption($userId, Option::OPTION_SEND_EMAIL_ON_SHARE)) {
                self::$alert = Html::Icon('friends') . ' Emails will be sent when friends share memos.';
            } else {
                self::$alert = Html::Icon('friends') . ' No emails will be sent when friends share memos.';
            }
        }

        $sql = <<< EOT
SELECT
       users.screen_name,
       accounts.real_name,
       accounts.email,
       accounts.created,
       accounts.email_validated
FROM
       users
INNER JOIN
       accounts ON users.id=accounts.user_id
WHERE users.id=$userId

EOT;

        $row = Database::QueryOneRow($sql);

        $html = '';

        $emailNotValidated = ($row['email_validated'] != 1);
        if ($emailNotValidated)
            $html .= $this->renderOptionBlockWithAction('Email validation', 'Your email is not validated.', 'Validate',
                array(Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_GEN_VALIDATION_TOKEN));

        $html .= $this->renderOptionBlockWithAction('Your name', Html::Span( 'account_name_info', self::GetFullNameLink($userId)), 'Change', array(Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_ACCOUNT_DETAILS));
        $html .= $this->renderOptionBlockWithAction('Your email', $row['email'], 'Change', array(Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_CHANGE_EMAIL));
        $html .= $this->renderOptionBlockWithAction('Password', '************', 'Change', array(Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_CHANGE_PASSWORD));

        $tipsEnabled = Option::GetOptionValue($userId, Option::OPTION_SHOW_TIPS);
        $html .= $this->renderOptionBlockWithAction('Tips', Option::GetOptionName(Option::OPTION_SHOW_TIPS), Option::GetOptionDescription(Option::OPTION_SHOW_TIPS, $tipsEnabled), array(Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_ACCOUNT, Key::KEY_TOGGLE_SHOW_TIPS => true));

        $emailOnAlarm = Option::GetOptionValue($userId, Option::OPTION_SEND_EMAIL_ON_ALARM);
        $emailOnShare = Option::GetOptionValue($userId, Option::OPTION_SEND_EMAIL_ON_SHARE);
        $html .= $this->renderOptionBlockWithMultipleActions('Email', array(
            array(self::OPTION_DESCRIPTION => Option::GetOptionName(Option::OPTION_SEND_EMAIL_ON_ALARM), self::OPTION_ACTION => Option::GetOptionDescription(Option::OPTION_SEND_EMAIL_ON_ALARM, $emailOnAlarm), self::OPTION_ACTION_ARGS => array(Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_ACCOUNT, Key::KEY_TOGGLE_EMAIL_ON_ALARM => true)),
            array(self::OPTION_DESCRIPTION => Option::GetOptionName(Option::OPTION_SEND_EMAIL_ON_SHARE), self::OPTION_ACTION => Option::GetOptionDescription(Option::OPTION_SEND_EMAIL_ON_SHARE, $emailOnShare), self::OPTION_ACTION_ARGS => array(Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_ACCOUNT, Key::KEY_TOGGLE_EMAIL_ON_SHARE => true)))
        );

        $moveDoneTrashOption = Option::GetOptionValue($userId, Option::OPTION_MOVE_DONE_TO_TRASH);
        $doneItems = Controller::GetNumberMemosInBucket($userId, Bucket::BUCKET_DONE);

        $days = Batch::DAYS_DONE_TO_TRASH;

        $html .= $this->renderOptionBlockWithAction('Done items',
            Option::GetOptionName(Option::OPTION_MOVE_DONE_TO_TRASH),
            Option::GetOptionDescription(Option::OPTION_MOVE_DONE_TO_TRASH, $moveDoneTrashOption),
            array(Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_ACCOUNT, Key::KEY_TOGGLE_MOVE_DONE_TO_TRASH => true),
            ($moveDoneTrashOption === Option::OPTION_VALUE_ENABLED) ? "Memos left in Done for $days days wil be moved to the Trash. " .
                        self::GetNavLinkWithArgs($this->Pluralize($doneItems, 'memo'), array(Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_HOME, Key::KEY_CLEAR_FILTERS => true, Key::KEY_BUCKET => Bucket::BUCKET_DONE))  . ' are currently marked Done.' :
                        '');

        $trashItems = Controller::GetNumberMemosInBucket($userId, Bucket::BUCKET_TRASH);

        $days = Batch::DAYS_TRASH_TO_DELETED;
        $trashComment = "Items left in Trash will be deleted after $days days.";
        if ($trashItems > 0)
            $html .= $this->renderOptionBlockWithAction('Trash',
                     self::GetNavLinkWithArgs($this->Pluralize($trashItems, 'memo'), array(Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_HOME, Key::KEY_CLEAR_FILTERS => true, Key::KEY_BUCKET => Bucket::BUCKET_TRASH)) . ' in Trash',
                     'Empty Trash',
                     array(Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_ACCOUNT, Key::KEY_EMPTY_TRASH => true), $trashComment);
        else
            $html .= $this->renderOptionBlock('Trash', 'Trash is empty.', $trashComment);

        $html .= $this->renderOptionBlockWithAction('Troubleshooting',
                 'Reset local database', 'Reset', array(Key::KEY_RESET_DB => true));

        $this->html =  Html::Heading('Account Details') .
                       Html::Div('big_list', $html);
    }

    private function renderOptionBlockWithAction($caption, $description, $action, $actionArgs, $comment='')
    {
        return Html::Div('big_list_item option_block',
            (strlen($caption) > 0 ? Html::Div('option_caption', $caption) : '') .
            Html::Div('option_set',
                Html::Div('option_description', $description) .
                Html::Div('option_action', self::GetNavLinkWithArgs($action, $actionArgs, 'link_button'))) .
            (strlen($comment) > 0 ? Html::Div('option_comment', $comment) : ''));
    }

    private function renderOptionBlockWithMultipleActions($caption, array $actionSet, $comment='')
    {
        if (strlen($caption) > 0)
            $html = Html::Div('option_caption', $caption);
        else
            $html = '';

        foreach ($actionSet as $set) {
            $html .= Html::Div('option_set',
                        Html::Div('option_description', $set[self::OPTION_DESCRIPTION]) .
                        Html::Div('option_action', self::GetNavLinkWithArgs($set[self::OPTION_ACTION], $set[self::OPTION_ACTION_ARGS], 'link_button')));
        }

        if (strlen($comment) > 0)
            $html .= Html::Div('option_comment', $comment);

        return Html::Div('big_list_item option_block', $html);
    }

    private function renderOptionBlock($caption, $description, $comment='')
    {
        return Html::Div('big_list_item option_block',
            Html::Div('option_caption', $caption) .
            Html::Div('option_set', Html::Div('option_description', $description)) .
            (strlen($comment) > 0 ? Html::Div('option_comment', $comment) : ''));
    }
}