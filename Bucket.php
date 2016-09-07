<?php

abstract class Bucket
{
    const BUCKET_NONE = 0;

    const BUCKET_VALID_RANGE_START = 100;

    const BUCKET_ALL_ACTIVE = 100;

    const BUCKET_EVERYTHING_START = 100;
    const BUCKET_EVERYTHING = 200;
    const BUCKET_EVERYTHING_END = 299;

    const BUCKET_HOT_LIST = 110;
    const BUCKET_B_LIST = 120;

    const BUCKET_JOURNAL = 210;
    const BUCKET_REFERENCE = 220;
    const BUCKET_DONE = 250;

    const BUCKET_TRASH = 310;
    const BUCKET_HIDDEN = 350;
    const BUCKET_DELETED = 410;
    const BUCKET_HISTORIC = 510;

    const BUCKET_VALID_RANGE_END = 510;

    const BUCKET_REVERT = 900;

    public static function GetShortBucketName($bucket)
    {
        switch ($bucket) {
            /*case self::BUCKET_ALL_ACTIVE:
                return 'Active';
            */
            case self::BUCKET_EVERYTHING:
                return 'Everything';
            case self::BUCKET_ALL_ACTIVE:
                return 'Hot List + B List';
            case self::BUCKET_HOT_LIST:
                return 'Hot List';
            case self::BUCKET_B_LIST:
                return 'B List';
            case self::BUCKET_JOURNAL:
                return 'Journal';
            case self::BUCKET_REFERENCE:
                return 'Reference';
            case self::BUCKET_DONE:
                return 'Done';
            case self::BUCKET_TRASH:
                return 'Trash';
            case self::BUCKET_HIDDEN:
                return 'Hidden';
            case self::BUCKET_DELETED:
                return 'Deleted';
            case self::BUCKET_HISTORIC:
                return 'Historic';
            default:
                return 'None';
        }
    }
    public static function GetBucketClass($bucket)
    {
        switch ($bucket) {
            case self::BUCKET_EVERYTHING:
                return 'bucket200';
            case self::BUCKET_ALL_ACTIVE:
                return 'bucket100';
            case self::BUCKET_HOT_LIST:
                return 'bucket110';
            case self::BUCKET_B_LIST:
                return 'bucket120';
            case self::BUCKET_JOURNAL:
                return 'bucket210';
            case self::BUCKET_REFERENCE:
                return 'bucket220';
            case self::BUCKET_DONE:
                return 'bucket250';
            case self::BUCKET_TRASH:
                return 'bucket310';
            case self::BUCKET_HIDDEN:
                return 'bucket350';
            case self::BUCKET_DELETED:
                return 'bucket410';
            case self::BUCKET_HISTORIC:
                return 'bucket510';
            default:
                return '';
        }
    }
    public static function IsValidBucket($bucket)
    {
        switch ($bucket) {
            case self::BUCKET_HOT_LIST:
            case self::BUCKET_B_LIST:
            case self::BUCKET_JOURNAL:
            case self::BUCKET_REFERENCE:
            case self::BUCKET_DONE:
            case self::BUCKET_TRASH:
            case self::BUCKET_DELETED:
                return true;
            default:
                return false;
        }
    }
}