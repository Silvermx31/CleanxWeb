<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $email = $_POST['email'] ?? '';
    $referral = $_POST['referral'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $idcode = $_POST['idcode'] ?? '';
    $documentType = $_POST['documentType'] ?? '';
    $businessType = $_POST['businessType'] ?? '';
    $companyName = $_POST['companyName'] ?? '';
    $companyRegistryCode = $_POST['companyRegistryCode'] ?? '';
    $iban = $_POST['iban'] ?? '';

    $to = 'silvermx31@gmail.com';
    $subject = 'Uus registreerimine CleanX kaudu';

    $message = "
Eesnimi: $firstname
Perekonnanimi: $lastname
E-mail: $email
Soovituskood: $referral
Telefon: $phone
Sünniaeg: $birthdate
Isikukood: $idcode
Dokumendi tüüp: $documentType
Ettevõtlusvorm: $businessType
";

    if ($businessType === 'OÜ') {
        $message .= "
Ettevõtte nimi: $companyName
Registrikood: $companyRegistryCode
";
    }

    $message .= "
Pangakonto (IBAN): $iban
";

    $headers = "From: cleanx@yourdomain.com\r\n";

    // 💥 1. E-maili saatmine
    $mailSuccess = mail($to, $subject, $message, $headers);

    if ($mailSuccess) {
        error_log("✅ E-mail saadetud edukalt.");
    } else {
        error_log("❌ E-maili saatmine ebaõnnestus.");
    }

    // 💥 2. Django API päring
    $apiUrl = 'https://ed9f-193-40-225-195.ngrok-free.app/api/provider-applications/';
    $curl = curl_init();

    $postFields = [
        'firstname' => $firstname,
        'lastname' => $lastname,
        'email' => $email,
        'referral' => $referral,
        'birthdate' => $birthdate,
        'idcode' => $idcode,
        'documentType' => $documentType,
        'businessType' => $businessType,
        'companyName' => $companyName,
        'companyRegistryCode' => $companyRegistryCode,
        'iban' => $iban
    ];

    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $postFields['file'] = new CURLFile($_FILES['file']['tmp_name'], $_FILES['file']['type'], $_FILES['file']['name']);
    }

    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $postFields
    ]);

    $apiResponse = curl_exec($curl);
    $apiHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($curlError) {
        error_log("❌ API cURL error: $curlError");
    } else {
        error_log("✅ Django API vastus [$apiHttpCode]: $apiResponse");
    }

    // 💥 Lõplik vastus brauserile
    if ($mailSuccess && ($apiHttpCode >= 200 && $apiHttpCode < 300)) {
        http_response_code(200);
        echo "E-mail ja Django API päring õnnestusid.";
    } elseif ($mailSuccess) {
        http_response_code(206);
        echo "E-mail õnnestus, Django API päring ebaõnnestus.";
    } else {
        http_response_code(500);
        echo "E-mail ebaõnnestus.";
    }
} else {
    http_response_code(405);
    echo "Ainult POST päring on lubatud.";
}
?>
