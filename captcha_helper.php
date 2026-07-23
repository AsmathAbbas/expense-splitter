<?php
function verifyRecaptcha($response) {
    if (empty($response)) return false;

    $url = "https://www.google.com/recaptcha/api/siteverify";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        "secret"   => RECAPTCHA_SECRET_KEY,
        "response" => $response
    ]));

    $result = curl_exec($ch);
    curl_close($ch);

    if ($result === false) return false;
    $resultJson = json_decode($result, true);
    return isset($resultJson['success']) && $resultJson['success'] === true;
}
?>