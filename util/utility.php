<?php

$PPM_TIMEZONE = "America/New_York";

// takes a UTC timestamp and returns a new timestamp converted to $PPM_TIMEZONE
function dtLocal(string $timestamp): string {
    global $PPM_TIMEZONE;
    $dtutc = new DateTimeImmutable($timestamp, new DateTimeZone("UTC"));
    $dtl = $dtutc->setTimezone(new DateTimeZone($PPM_TIMEZONE));
    return $dtl->format("Y-m-d H:i:s");
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

function setDueColor(string $due_date): string {
    global $PPM_TIMEZONE;

    if (!$due_date) {
        return "";
    }

    $dtnow = new DateTimeImmutable("now", new DateTimeZone($PPM_TIMEZONE));
    $dtnow = $dtnow->setTime(0, 0, 0, 0);
    $dtdue = new DateTimeImmutable($due_date, new DateTimeZone($PPM_TIMEZONE));
    $interval = $dtnow->diff($dtdue);
    if ($interval->invert) {
        $diff = $interval->d * -1;
    } else {
        $diff = $interval->d;
    }

    if ($diff < 0) {
        $due_color = "style='background-color: darkorchid; font-weight: bold;'";
    } elseif ($diff == 0) {
        $due_color = "style='background-color: orangered; font-weight: bold;'";
    } elseif ($diff <= 3) {
        $due_color = "style='background-color: salmon; font-weight: bold;'";
    } elseif ($diff <= 5) {
        $due_color = "style='background-color: lightsalmon; font-weight: bold;'";
    } elseif ($diff <= 7) {
        $due_color = "style='background-color: wheat; font-weight: bold;'";
    } else {
        $due_color = "";
    }
    return $due_color;
}
