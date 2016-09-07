<?php

abstract class LoginStatus
{
    const ADMIN = 20;
    const VALIDATED = 10;
    const OK = 5;
    const NONE = 0;
    const ANY = -1000;
}

abstract class LoginError
{
    const NONE = 0;
    const EMAIL_OR_SCREEN_NAME_NOT_FOUND = -20;
    const PASSWORD_MISMATCH = -30;
    const ACCOUNT_LOCKED = -40;

}
