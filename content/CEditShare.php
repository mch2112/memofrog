<?php

class CEditShare extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_EDIT_SHARE;
    }

    public function AuthLevel()
    {
        return LoginStatus::OK;
    }

    public function Render($userId)
    {
        if ($this->processCallbackVals($userId, $shareId))
            return;

        if ($shareId > 0) {

            self::$response[Key::KEY_SHARE_ID] = $shareId;

            // ASSEMBLE DATA

            $shareId2 = Controller::GetOrCreateReciprocalShareId($shareId);

            $shareRow1 = Database::QueryOneRow("SELECT * FROM shares WHERE id=$shareId");
            $tags = Controller::GetTagsAsString($shareId);
            $tagCount = (int)$shareRow1['tag_count'];

            $shareRow2 = Database::QueryOneRow("SELECT * FROM shares WHERE id=$shareId2");

            $source1 = (int)$shareRow1['source_user_id'];
            $target1 = (int)$shareRow1['target_user_id'];

            if ($source1 === $userId) {
                $friendId = $targetUserId = $target1;
                $outShareId = $shareId;
                $outShareRow = $shareRow1;
                $inShareId = $shareId2;
                $inShareRow = $shareRow2;
            } else if ($target1 === $userId) {
                $friendId = $sourceUserId = $source1;
                $outShareId = $shareId2;
                $outShareRow = $shareRow2;
                $inShareId = $shareId;
                $inShareRow = $shareRow1;
            } else {
                $this->setError(ErrorCode::ERROR_USER_NOT_AUTH_FOR_SHARE);
                return;
            }

            $inSourceEnabled = boolval($inShareRow['source_enabled']);
            $inTargetEnabled = boolval($inShareRow['target_enabled']);
            $outSourceEnabled = boolval($outShareRow['source_enabled']);

            $canShareIn = $inSourceEnabled;
            $sharingIn = $canShareIn && $inTargetEnabled;
            $sharingOut = $outSourceEnabled;

            $canEditIn = $sharingIn && boolval($inShareRow['can_edit']);
            $allowEditOut = boolval($outShareRow['can_edit']);

            $this->renderScreen($userId, $friendId, $inShareId, $outShareId, $tags, $canShareIn, $sharingIn, $sharingOut, $canEditIn, $allowEditOut, $tagCount);

        } else {
            $this->setError(ErrorCode::ERROR_SHARE_NOT_FOUND);
        }
    }

    /**
     * @param $userId int
     * @param $shareId int
     * @return bool
     */
    private function processCallbackVals($userId, &$shareId)
    {
        $redirect = false;
        if (($shareToToggleCanEdit = UserInput::Extract(Key::KEY_SHARE_CAN_EDIT_TO_TOGGLE, 0)) > 0) {
            if (($otherUserId = Controller::OtherUserOnShare($userId, $shareToToggleCanEdit)) > 0) {
                $canEdit = ControllerW::ToggleShareCanEdit($shareToToggleCanEdit);
                if ($canEdit)
                    self::$alert = '@' . Account::GetScreenName($otherUserId) . ' can edit these memos.';
                else
                    self::$alert = '@' . Account::GetScreenName($otherUserId) . ' can no longer edit these memos.';
                $shareId = $shareToToggleCanEdit;
            } else {
                $this->setError(ErrorCode::ERROR_INADEQUATE_AUTH_FOR_CONTENT);
                $redirect = true;
            }
        } else if (($shareId = UserInput::Extract(Key::KEY_SHARE_TO_DELETE, 0)) > 0) {
            if (($otherUserId = Controller::OtherUserOnShare($userId, $shareId)) > 0) {
                $shareId2 = Controller::GetReciprocalShareId($shareId);
                ControllerW::DeleteShare($shareId, $shareId2, false);
                Session::SetSessionVal(Key::KEY_FRIEND_ID, $otherUserId);
                self::$alert = Html::Icon('deleted') . "&nbsp;Share deleted.&nbsp;&nbsp;" .
                    $this->GetNavLinkWithArgs('Undo',
                        array(Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_EDIT_SHARE,
                            Key::KEY_SHARE_TO_UNDELETE => $shareId)
                    );
                $this->redirect = ContentKey::CONTENT_KEY_FRIEND;
            } else {
                $this->setError(ErrorCode::ERROR_SHARE_NOT_FOUND);
            }
            $redirect = true;
        } else if (($shareId = UserInput::Extract(Key::KEY_SHARE_TO_UNDELETE, 0)) > 0) {
            if (($otherUserId = Controller::OtherUserOnShare($userId, $shareId)) > 0) {
                $shareId2 = Controller::GetReciprocalShareId($shareId);
                ControllerW::DeleteShare($shareId, $shareId2, true);
                Session::SetSessionVal(Key::KEY_FRIEND_ID, $otherUserId);
                $this->redirect = ContentKey::CONTENT_KEY_FRIEND;
                self::$alert = 'Share restored.';
            } else {
                $this->setError(ErrorCode::ERROR_SHARE_NOT_FOUND);
                $redirect = true;
            }
        } else if (($shareId = UserInput::Extract(Key::KEY_ENABLE_SHARE_SOURCE, 0)) > 0) {
            if (($otherUserId = Controller::OtherUserOnShare($userId, $shareId)) > 0) {
                ControllerW::SetShareEnable($shareId, true, true);
                self::$alert = 'Sharing enabled with @' . Account::GetScreenName($otherUserId) . '.';
            }
        } else if (($shareId = UserInput::Extract(Key::KEY_DISABLE_SHARE_SOURCE, 0)) > 0) {
            if (($otherUserId = Controller::OtherUserOnShare($userId, $shareId)) > 0) {
                ControllerW::SetShareEnable($shareId, true, false);
                self::$alert = 'Sharing disabled with @' . Account::GetScreenName($otherUserId) . '.';
            }
        } else if (($shareId = UserInput::Extract(Key::KEY_ENABLE_SHARE_TARGET, 0)) > 0) {
            if (($otherUserId = Controller::OtherUserOnShare($userId, $shareId)) > 0) {
                ControllerW::SetShareEnable($shareId, false, true);
                self::$alert = 'Sharing enabled from @' . Account::GetScreenName($otherUserId) . '.';
            }
        } else if (($shareId = UserInput::Extract(Key::KEY_DISABLE_SHARE_TARGET, 0)) > 0) {
            if (($otherUserId = Controller::OtherUserOnShare($userId, $shareId)) > 0) {
                ControllerW::SetShareEnable($shareId, false, false);
                self::$alert = 'Sharing disabled from @' . Account::GetScreenName($otherUserId) . '.';
            }
        } else {
            $shareId = UserInput::Extract(Key::KEY_SHARE_ID);
        }
        return $redirect;
    }

    /**
     * @param $userId int
     * @param $friendId int
     * @param $inShareId int
     * @param $outShareId int
     * @param $tags string
     * @param $canShareIn bool
     * @param $sharingIn bool
     * @param $sharingOut bool
     * @param $canEditIn bool
     * @param $allowEditOut bool
     * @param $tagCount int
     * @internal param int $shareId
     */
    private function renderScreen($userId, $friendId, $inShareId, $outShareId, $tags, $canShareIn, $sharingIn, $sharingOut, $canEditIn, $allowEditOut, $tagCount)
    {
        $tagsNoHash = str_replace('#', '', $tags);
        $otherScreenName = Account::GetScreenName($friendId);
        $otherScreenNameLink = View::RenderScreenNameLinkFromString($otherScreenName);

        $numMemosOut = Controller::GetNumberOfMemosAssociatedWithShare($outShareId, $userId, true);
        $numMemosIn = Controller::GetNumberOfMemosAssociatedWithShare($inShareId, $userId, false);

        switch ($tagCount) {
            case 0:
            case 1:
                $tag_count_text = 'this tag';
                break;
            case 2:
                $tag_count_text = 'both of these tags';
                break;
            default:
                $tag_count_text = "all $tagCount of these tags";
                break;
        }

        $html = Html::Div('submenu',
                self::GetJavaScriptLink("&lt; @$otherScreenName", "navigateToFriend(\"$otherScreenName\");return false;", 'link_button') .
                Html::Div('spacer', '') .
                self::GetNavLinkWithArgs(Html::Icon('bucket410') . ' Delete Share',
                    array(Key::KEY_SHARE_TO_DELETE => $outShareId,
                        Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_EDIT_SHARE),
                    'link_button')) .
            Html::Heading("Sharing $tags with @$otherScreenName");

        if ($tagCount > 1)
            $html .= Html::TagWithClass('p', 'parenthetic', "Only memos with $tag_count_text will be shared.");

        $sharingInArgs = $canShareIn ?
            ($sharingIn ?
                array(Key::KEY_DISABLE_SHARE_TARGET => $inShareId, Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_EDIT_SHARE) :
                array(Key::KEY_ENABLE_SHARE_TARGET => $inShareId, Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_EDIT_SHARE)) :
            array();

        $sharingOutArgs = $sharingOut ? array(Key::KEY_DISABLE_SHARE_SOURCE => $outShareId, Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_EDIT_SHARE) : array(Key::KEY_ENABLE_SHARE_SOURCE => $outShareId, Key::KEY_CONTENT_REQ => ContentKey::CONTENT_KEY_EDIT_SHARE);

        if ($canShareIn)
            $inClass = $sharingIn ? 'share_in' : 'share_in_disabled';
        else
            $inClass = 'share_none';

        $outClass = $sharingOut ? 'share_out' : 'share_out_disabled';

        $numMemosOutText = self::Pluralize($numMemosOut, 'memo');

        if ($sharingOut && $numMemosOut > 0)
            $numMemosOutText .= ' shared<br>with @' . $otherScreenName;
        else
            $numMemosOutText .= ' available<br>to share with @' . $otherScreenName;

        if ($numMemosOut > 0) {
            if ($sharingOut) {
                $linkSN = $otherScreenName;
                $linkSO = 'true';
            } else {
                $linkSN = Account::GetScreenName($userId);
                $linkSO = 'false';
            }
            $memosOutLink = self::GetJavaScriptLink($numMemosOutText, "filterByTagsAndScreenName(\"$tagsNoHash\", \"$linkSN\", $linkSO);return false;", 'link_button');
        } else {
            $memosOutLink = $numMemosOutText;
        }

        $numMemosInText = self::Pluralize($numMemosIn, 'memo');

        if ($sharingIn && $numMemosIn > 0)
            $numMemosInText .= '<br>shared with you';
        else if ($canShareIn)
            $numMemosInText .= '<br>available to you';

        if ($numMemosIn > 0 && $sharingIn) {
            $linkSN = $otherScreenName;
            $linkSO = 'false';
            $memosInLink = self::GetJavaScriptLink($numMemosInText, "filterByTagsAndScreenName(\"$tagsNoHash\", \"$linkSN\", $linkSO);return false;", 'link_button');
        } else
            $memosInLink = $numMemosInText;

        $totalMemosSharable = $numMemosIn + $numMemosOut;
        $totalMemosShared = ($sharingIn ? $numMemosIn : 0) + ($sharingOut ? $numMemosOut : 0);
        $totalMemosSharedText = self::Pluralize($totalMemosShared, 'memo');

        if ($totalMemosSharable === $totalMemosShared)
            $html .= Html::Div('share_intro_text', "You and $otherScreenNameLink are sharing $totalMemosSharedText:");
        else
            $html .= Html::Div('share_intro_text', "You and $otherScreenNameLink are sharing $totalMemosSharedText of $totalMemosSharable available:");

        if ($canShareIn) {
            $memoCountIn = Html::Div('share_memo_count_text', $memosInLink);
            $shareIconIn = self::getNavLinkWithArgs(Html::Div('share_icon hoverable ' . $inClass, ''), $sharingInArgs);
            $editIconIn = $canEditIn ? Html::LargeIcon('edit') : Html::LargeIcon('blank');
        } else {
            $editIconIn = $shareIconIn = $memoCountIn = '';
        }

        $memoCountOut = Html::Div('share_memo_count_text', $memosOutLink);
        $shareIconOut = self::getNavLinkWithArgs(Html::Div('share_icon hoverable ' . $outClass, ''), $sharingOutArgs);
        $editIconOut = ($sharingOut && $allowEditOut) ? Html::LargeIcon('edit') : Html::Span('inline_icon_large blank', '');

        if (Session::IsMobile())
            $html .= Html::Div('share_graphic share_graphic_icon',
                    $editIconIn . $shareIconIn . $shareIconOut . $editIconOut) . Html::Div('share_graphic share_graphic_buttons', $memoCountIn . $memoCountOut);
        else
            $html .= Html::Div('share_graphic', $memoCountIn . $editIconIn . $shareIconIn . $shareIconOut . $editIconOut . $memoCountOut);

        $html .= '<hr>';

        $cbName = 'share_can_edit_checkbox';
        $label = Html::Icon('edit') . '&nbsp;Allow ' . Html::Span('screen_name', '@' . $otherScreenName) . ' to edit these memos.';

        $html .= Html::Checkbox($cbName, $cbName, $allowEditOut, $label, !$sharingOut, "toggleShareCanEdit($outShareId);");

        $this->html = $html;
    }
}