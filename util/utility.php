<?php

$SECS_IN_DAY = 86400;

function dtEastern($timestamp) {
    date_default_timezone_set("America/New_York");
    return date("Y-m-d H:i:s", strtotime("$timestamp UTC"));
}

function daysUntil($date_str) {
    global $SECS_IN_DAY;
    $unix_diff = strtotime($date_str) - time();
    $days_diff = ceil($unix_diff / $SECS_IN_DAY);
    return $days_diff;
}

function statusColor($status) {
    if ($status == "NOT STARTED") {
        return "cornflowerblue";
    }
    if ($status == "IN PROGRESS") {
        return "indianred";
    }
    if ($status == "ON HOLD") {
        return "plum";
    }
    if ($status == "COMPLETE") {
        return "forestgreen";
    }
    if ($status == "ABANDONED") {
        return "chocolate";
    }
}
