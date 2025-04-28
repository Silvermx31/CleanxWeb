<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $email = $_POST['email'] ?? '';
    $referral = $_POST['referral'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $idcode = $_POST['idcode'] ?? '';
    $documentType = $_POST['documentType'] ?? '';
    $businessType = $_POST['businessType'] ?? '';
    $companyName = $_POST['companyName'] ?? '';
    $companyRegistryCode = $_POST['companyRegistryCode'] ?? '';
    $iban = $_POST['iban'] ?? '';

    $to = 'silvermx31@gmail.com';
    $subject = 'Uus registreerimine CleanX kaudu';
    
    // Kirja põhisisu
    $message = "
Eesnimi: $firstname
Perekonnanimi: $lastname
E-mail: $email
Soovituskood: $referral
Sünniaeg: $birthdate
Isikukood: $idcode
Dokumendi tüüp: $documentType
Ettevõtlusvorm: $businessType
";

    // Kui ettevõtlusvorm on OÜ, lisame ettevõtte andmed
    if ($businessType === 'OÜ') {
        $message .= "
Ettevõtte nimi: $companyName
Registrikood: $companyRegistryCode
";
    }

    // Pangakonto number (IBAN) lisatakse alati
    $message .= "
Pangakonto (IBAN): $iban
";

    // Faili töötlemine
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB

        $file_tmp = $_FILES['file']['tmp_name'];
        $file_name = basename($_FILES['file']['name']);
        $file_size = $_FILES['file']['size'];
        $file_type = $_FILES['file']['type'];

        // Faili kontrollid
        if (!in_array($file_type, $allowed_types)) {
            http_response_code(400);
            echo "Lubatud on ainult JPG, PNG või PDF failid.";
            exit;
        }

        if ($file_size > $max_size) {
            http_response_code(400);
            echo "Fail on liiga suur. Maksimaalne suurus on 5MB.";
            exit;
        }

        $file_name = preg_replace("/[^A-Za-z0-9\.\-_]/", '', $file_name);

        $handle = fopen($file_tmp, "r");
        $content = fread($handle, $file_size);
        fclose($handle);
        $encoded_content = chunk_split(base64_encode($content));

        $boundary = md5(time());

        // Headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: cleanx@yourdomain.com\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"".$boundary."\"\r\n";

        // Body
        $body = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $message . "\r\n";

        $body .= "--$boundary\r\n";
        $body .= "Content-Type: $file_type; name=\"$file_name\"\r\n";
        $body .= "Content-Disposition: attachment; filename=\"$file_name\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= $encoded_content . "\r\n";
        $body .= "--$boundary--";

        // Saadame meili
        if (mail($to, $subject, $body, $headers)) {
            http_response_code(200);
        } else {
            http_response_code(500);
        }
    } else {
        // Kui faili pole, saadame lihtsalt tekstilise kirja
        $headers = "From: cleanx@yourdomain.com\r\n";
        if (mail($to, $subject, $message, $headers)) {
            http_response_code(200);
        } else {
            http_response_code(500);
        }
    }
} else {
    http_response_code(403);
}
?>
