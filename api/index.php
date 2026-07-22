<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

echo json_encode([
    "status" => "success",
    "message" => "St. Vincent Ferrer Parish API Service is running.",
    "endpoints" => [
        "announcements" => "api/announcements.php",
        "events" => "api/events.php",
        "contact" => "api/contact.php (POST)"
    ]
]);
