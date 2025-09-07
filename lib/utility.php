<?php

function dtEastern($timestamp) {
    date_default_timezone_set("America/New_York");
    return date("Y-m-d h:i:s", strtotime("$timestamp UTC"));
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
