<?php

$SECS_IN_DAY = 86400;

function dtEastern($timestamp) {
    date_default_timezone_set("America/New_York");
    return date("Y-m-d H:i:s", strtotime("$timestamp UTC"));
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

function daysUntil($date_str) {
    global $SECS_IN_DAY;
    $unix_diff = strtotime($date_str) - time();
    $days_diff = ceil($unix_diff / $SECS_IN_DAY);
    return $days_diff;
}

function setDueColor($due_date) {
    if (!$due_date) {
        return "";
    }
    if (daysUntil($due_date) < -1) {
        $due_color = "style='background-color: darkorchid; font-weight: bold;'";
    } elseif (daysUntil($due_date) < 0) {
        $due_color = "style='background-color: orangered; font-weight: bold;'";
    } elseif (daysUntil($due_date) < 3) {
        $due_color = "style='background-color: salmon; font-weight: bold;'";
    } elseif (daysUntil($due_date) < 5) {
        $due_color = "style='background-color: lightsalmon; font-weight: bold;'";
    } elseif (daysUntil($due_date) < 7) {
        $due_color = "style='background-color: wheat; font-weight: bold;'";
    } else {
        $due_color = "";
    }
    return $due_color;
}
