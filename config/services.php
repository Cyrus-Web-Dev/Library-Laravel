<?php

return [

    // Kredensial Gmail API ada di config/gmail.php (dipakai untuk OTP verifikasi & reset password).

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

];
