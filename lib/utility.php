<?php

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
