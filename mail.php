<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // PHPMailer autoload

header('Content-Type: application/json');

$admin_email = 'info@kdpbookformat.com';

function send_mail($to, $to_name, $subject, $body, $file=null){
    $mail = new PHPMailer(true);
    try{
        $mail->isSMTP();
        $mail->Host = 'smtp.dreamhost.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'info@kdpbookformat.com';
        $mail->Password = 'info@kdpbookformat';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('info@kdpbookformat.com','KDP Book Format');
        $mail->addAddress($to,$to_name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        if($file){
            $mail->addAttachment($file['tmp_name'],$file['name']);
        }

        return $mail->send();
    }catch(Exception $e){
        return false;
    }
}

// -------------------- Form data --------------------
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$language = trim($_POST['language']);
$interests = isset($_POST['interests']) ? implode(", ", $_POST['interests']) : "None";
$message = trim($_POST['message']);
$attachment = isset($_FILES['manuscript']) ? $_FILES['manuscript'] : null;

// -------------------- Validation --------------------
if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
    echo json_encode(['success'=>false,'message'=>'Invalid client email']);
    exit;
}

$allowedExt = ['pdf','jpg','jpeg','png','doc','docx','epub','mobi'];
$maxSize = 100*1024*1024;

if($attachment && $attachment['size']>0){
    $ext = strtolower(pathinfo($attachment['name'],PATHINFO_EXTENSION));
    if(!in_array($ext,$allowedExt)){
        echo json_encode(['success'=>false,'message'=>'File type not allowed']);
        exit;
    }
    if($attachment['size'] > $maxSize){
        echo json_encode(['success'=>false,'message'=>'File exceeds 100MB']);
        exit;
    }
}

// -------------------- Email bodies --------------------
$admin_body = "<h2>New KDP Service Request</h2>
<p><strong>Name:</strong> $name</p>
<p><strong>Email:</strong> $email</p>
<p><strong>Phone:</strong> $phone</p>
<p><strong>Language:</strong> $language</p>
<p><strong>Services:</strong> $interests</p>
<p><strong>Message:</strong><br>$message</p>";

$client_body = "<h2>Thank you $name!</h2>
<p>We received your request. Here is a copy of your submission:</p>
<p><strong>Name:</strong> $name</p>
<p><strong>Email:</strong> $email</p>
<p><strong>Phone:</strong> $phone</p>
<p><strong>Language:</strong> $language</p>
<p><strong>Services:</strong> $interests</p>
<p><strong>Message:</strong><br>$message</p>
<p>We will contact you shortly.</p>";

// -------------------- Send emails --------------------
$admin_sent = send_mail($admin_email,'Admin','New KDP Request',$admin_body,$attachment);
$client_sent = send_mail($email,$name,'Thank You for Your Request!',$client_body,$attachment);

if($admin_sent && $client_sent){
    echo json_encode(['success'=>true,'message'=>'Request sent successfully!']);
}else{
    echo json_encode(['success'=>false,'message'=>'Error sending email.']);
}
?>
