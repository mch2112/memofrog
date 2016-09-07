<?php

require_once('global.php');

try {
    $result = Batch::RunAll();
    echo $result;
}
catch (Exception $e) {
    echo 'Fail.';
}
