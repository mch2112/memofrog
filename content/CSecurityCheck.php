<?php

class CSecurityCheck extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_SECURITY_CHECK;
    }

    public function AuthLevel()
    {
        return LoginStatus::ADMIN;
    }

    public function Render($userId)
    {
        $startTime = microtime(true);
        $results = $this->renderCheck();
        $endTime = microtime(true);

        $diff = $endTime - $startTime;

        $html = '';
        foreach ($results as $r)
            $html .= Html::P($r);

        $this->html =
            Html::Div('submenu', Html::LinkButton('&lt;&nbsp;Admin', ContentKey::CONTENT_KEY_ADMIN)) .
            Html::Heading('Security Check') .
            Html::P("Elapsed Time: $diff seconds") .
            $html;
    }
    /* @return string[] */
    private function renderCheck()
    {
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;

        $checks = 0;
        $errors = 0;

        $results = array();

        Database::QueryCallback('SELECT memos.id AS memo_id, memos.user_id AS author_id, memos.private AS private, memos.deleted AS deleted, shared_memo_status.id AS sms_id, shared_memo_status.user_id AS sms_user_id FROM memos INNER JOIN shared_memo_status ON memos.id=shared_memo_status.memo_id AND shared_memo_status.is_author = 0 AND shared_memo_status.available=1',
            function($row) use (&$html, &$checks, &$errors) {
                $viewerUserId = (int)$row['sms_user_id'];
                $memoId = (int)$row['memo_id'];
                $okShares = array();
                $checks++;
                $private = boolval($row['private']);
                $deleted = boolval($row['deleted']);
                if ($private) {
                    $results[] = "Memo $memoId is private but is shared with $viewerUserId";
                    $errors++;
                } else if ($deleted) {
                    $results[] = "Memo $memoId is deleted but is shared with $viewerUserId";
                    $errors++;
                } else {
                    $memoIsOk = false;
                    $authorId = (int)$row['author_id'];
                    Database::QueryCallback("SELECT id FROM shares WHERE source_user_id=$authorId AND target_user_id=$viewerUserId AND source_enabled=1 AND deleted=0", function ($row) use (&$memoIsOk, &$okShares, $memoId, $viewerUserId) {
                        $shareId = (int)$row['id'];
                        if (Controller::ShareAuthorizesViewer($shareId, $memoId, $viewerUserId)) {
                            $okShares[] = $shareId;
                            $memoIsOk = true;
                        }
                    });
                    Database::QueryCallback("SELECT memos.id AS memo_id, direct_shares.user_id AS user_id, users.screen_name AS screen_name, memos.memo_text AS memo_text FROM direct_shares INNER JOIN memos ON direct_shares.memo_id=memos.id INNER JOIN users ON direct_shares.user_id=users.id WHERE memo_id=$memoId AND direct_shares.valid=1 AND direct_shares.user_id=$viewerUserId",
                        function ($row) use (&$html, &$memoIsOk, &$checks, &$errors, $memoId, $viewerUserId) {
                            $checks++;
                            $memoIsOk = true;
                            $pos = mb_strpos($row['memo_text'], '@' . $row['screen_name']);
                            if ($pos === false) {
                                $screenName = $row['screen_name'];
                                $results[] = "Memo $memoId has a direct share for $viewerUserId but that screen name ($screenName) is not in the memo text.";
                                $errors++;
                            }
                        });

                    foreach ($okShares as $shareId) {
                        $checks++;
                        if (!Controller::MemoHasTagsForShare($memoId, $shareId)) {
                            $results[] = "Memo $memoId does not have all the tags needed to meet criteria for share $shareId";
                            $errors++;
                        }
                    }
                    if (!$memoIsOk) {
                        $results[] = "Memo $memoId should not be viewable by user $viewerUserId";
                        $errors++;
                    }
                }
            }
        );
        $results[] = "Checks performed: $checks";
        $results[] = "Errors found: $errors";
        return $results;
    }
}
