<?php

class CError extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_ERROR;
    }

    public function AuthLevel()
    {
        return LoginStatus::ANY;
    }
    public function Render($userId)
    {
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;
        $this->needsEmailValSuppressed = true;

        $e = Session::GetException();
        if (is_null($e)) {

            $errorCode = UserInput::Extract(Key::KEY_ERROR_CODE, 0);
            $errorContentKey = UserInput::Extract(Key::KEY_ERROR_CONTENT_ID, 0);

            $this->LogError($userId, $errorCode, $errorContentKey, true);

            $this->html =
                Html::Heading("Error") .
                Html::SubHeading('An error has occurred:') .
                View::RenderInfoWidget('Error Code', strval($errorCode)) .
                View::RenderInfoWidget('Error Content Key', strval($errorContentKey)) .
                View::RenderInfoWidget('Error Time', Html::Tag('displaydate', (new DateTime())->format(Util::DATE_TIME_FORMAT))) .
                self::GetNavLink('Home', ContentKey::CONTENT_KEY_HOME, 'standard_button');
        } else {
            $this->SetException($userId, $e);
        }
    }
    public function SetException($userId, Exception $e)
    {
        $this->LogError($userId, ErrorCode::ERROR_UNKNOWN_EXCEPTION, ContentKey::CONTENT_KEY_NONE, true);

        $this->html =
            Html::Heading('Error') .
            Html::SubHeading('Exception Details') .
            Html::P($e->getMessage()) .
            Html::P($e->getTraceAsString());
    }
    private function LogError($userId, $code, $contentKey, $userFacing)
    {
        Database::ExecutePreparedStatement('INSERT INTO errors (user_id, error_code, error_content_key, user_facing) VALUES (?,?,?,?)', 'iiii', array($userId, $code, $contentKey, $userFacing ? 1 : 0));
    }
}