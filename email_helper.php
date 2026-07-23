<?php
function sendOtpEmail($toEmail, $toName, $otp) {
    $url = "https://api.sendgrid.com/v3/mail/send";

    $data = [
        "personalizations" => [
            [
                "to" => [
                    ["email" => $toEmail, "name" => $toName]
                ],
                "subject" => "Your SplitEase verification code"
            ]
        ],
        "from" => [
            "email" => SENDGRID_FROM_EMAIL,
            "name" => SENDGRID_FROM_NAME
        ],
        "content" => [
            [
                "type" => "text/plain",
                "value" => "Your SplitEase verification code: " . $otp
            ],
            [
                "type" => "text/html",
                "value" => "<p>Hi " . htmlspecialchars($toName) . ",</p>"
                           . "<p>Your SplitEase verification code is:</p>"
                           . "<h2>" . htmlspecialchars($otp) . "</h2>"
                           . "<p>This code expires in 10 minutes. If you didn't request this, you can ignore this email.</p>"
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . SENDGRID_API_KEY,
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $logEntry = date("Y-m-d H:i:s") . " | To: $toEmail | HTTP: $httpCode | Response: $response" . PHP_EOL;
    file_put_contents(__DIR__ . '/otp_errors.log', $logEntry, FILE_APPEND);

    return $httpCode === 202;
}
?>