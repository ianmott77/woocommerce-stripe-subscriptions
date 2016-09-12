<?php


// Retrieve the request's body and parse it as JSON
$input = @file_get_contents("php://input");
$event_json = json_decode($input);

// Do something with $event_json

http_response_code(200); // PHP 5.4 or greater
?>