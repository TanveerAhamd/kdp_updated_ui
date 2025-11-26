<?php
// Start PHP part for form submission
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

$success = false;
$errorMsg = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name       = $_POST['name'] ?? '';
    $email      = $_POST['email'] ?? '';
    $phone      = $_POST['phone'] ?? '';
    $language   = $_POST['language'] ?? '';
    $interests  = isset($_POST['interests']) ? implode(", ", $_POST['interests']) : "None";
    $message    = $_POST['message'] ?? '';
    $attachment = $_FILES['manuscript']['name'] ?? null;

    // Client email validation
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
        $errorMsg = 'Invalid client email address!';
    }

    // File validation
    $tmp_path = null;
    if(empty($errorMsg) && !empty($_FILES['manuscript']['name'])) {
        $file = $_FILES['manuscript'];
        $allowedExt = ['pdf','jpg','jpeg','png','doc','docx','epub','mobi'];
        $fileExt = strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
        if(!in_array($fileExt,$allowedExt)){
            $errorMsg = 'File type not allowed!';
        } elseif($file['size'] > 100*1024*1024){
            $errorMsg = 'File exceeds 100MB!';
        } else {
            $upload_dir = __DIR__.'/temp_uploads/';
            if(!is_dir($upload_dir)) mkdir($upload_dir,0755,true);
            $tmp_path = $upload_dir.basename($file['name']);
            move_uploaded_file($file['tmp_name'],$tmp_path);
        }
    }

    if(empty($errorMsg)){
        // Function to send email
        function send_email($to_email,$to_name,$subject,$body_html,$attachment_path=null){
            $mail = new PHPMailer(true);
            try{
                $mail->isSMTP();
                $mail->Host = 'smtp.dreamhost.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'info@kdpbookformat.com';
                $mail->Password = 'info@kdpbookformat';
                $mail->SMTPSecure = 'ssl';
                $mail->Port = 465;

                $mail->setFrom('info@kdpbookformat.com','KDP Book Format');
                $mail->addAddress($to_email,$to_name);

                if($attachment_path) $mail->addAttachment($attachment_path);

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $body_html;

                $mail->send();
                return true;
            }catch(Exception $e){
                return false;
            }
        }

        // Admin Email
        $admin_subject = "📩 New KDP Service Request - $name";
        $admin_body = "<h2>New Service Request</h2>
        <p><b>Name:</b> $name</p>
        <p><b>Email:</b> $email</p>
        <p><b>Phone:</b> $phone</p>
        <p><b>Book Language:</b> $language</p>
        <p><b>Services:</b> $interests</p>
        <h3>Message:</h3><p>$message</p>";

        $send_admin = send_email('info@kdpbookformat.com','Admin',$admin_subject,$admin_body,$tmp_path);

        if($send_admin){
            // Client Auto Reply
            $client_subject = "✅ Thank You for Your Request!";
            $client_body = "<h2>Thank You, $name!</h2>
            <p>We have received your request and will contact you shortly.</p>
            <p><b>Name:</b> $name</p>
            <p><b>Email:</b> $email</p>
            <p><b>Phone:</b> $phone</p>
            <p><b>Book Language:</b> $language</p>
            <p><b>Services:</b> $interests</p>
            <h3>Message:</h3><p>$message</p>";

            send_email($email,$name,$client_subject,$client_body,$tmp_path);
            $success = true;
        } else {
            $errorMsg = 'Error sending admin email!';
        }

        // Delete temp file
        if($tmp_path && file_exists($tmp_path)) unlink($tmp_path);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KDP Book Formatting Request</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container my-5">
<h1 class="mb-4">KDP Book Formatting Request Form</h1>

<?php if(isset($success) && $success): ?>
<div class="alert alert-success">Your request has been sent successfully!</div>
<?php elseif(!empty($errorMsg)): ?>
<div class="alert alert-danger"><?= $errorMsg ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="row g-3">

  <div class="col-md-6">
    <label class="form-label">Your Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" placeholder="Type your name" required>
  </div>

  <div class="col-md-6">
    <label class="form-label">Your Email <span class="text-danger">*</span></label>
    <input type="email" name="email" class="form-control" placeholder="Type your email" required>
  </div>

  <div class="col-md-6">
    <label class="form-label">Your Phone</label>
    <input type="text" name="phone" class="form-control" placeholder="Type your phone number with country code">
  </div>

  <div class="col-md-6">
    <label class="form-label">Upload Manuscript (Optional)</label>
    <input type="file" name="manuscript" class="form-control">
    <small class="text-muted">Max 100MB. Allowed: pdf, jpg, jpeg, png, doc, docx, epub, mobi</small>
  </div>

  <div class="col-md-6">
    <label class="form-label">Your interests:</label>
    <div class="form-check">
      <input type="checkbox" name="interests[]" value="Ebook Formatting" class="form-check-input">
      <label class="form-check-label">Ebook Formatting</label>
    </div>
    <div class="form-check">
      <input type="checkbox" name="interests[]" value="Print Formatting" class="form-check-input">
      <label class="form-check-label">Print Formatting</label>
    </div>
    <div class="form-check">
      <input type="checkbox" name="interests[]" value="Cover Design" class="form-check-input">
      <label class="form-check-label">Cover Design</label>
    </div>

    <select class="form-select mt-2" name="language" required>
      <option selected disabled>Select Language</option>
      <option value="English">English</option>
      <option value="Spanish">Spanish</option>
      <option value="Italian">Italian</option>
      <option value="German">German</option>
      <option value="Hebrew">Hebrew</option>
      <option value="Others">Others</option>
    </select>
  </div>

  <div class="col-md-6">
    <label class="form-label">Message / Special Instructions</label>
    <textarea class="form-control" name="message" rows="5" placeholder="Tell us about your book or special requests"></textarea>
  </div>

  <div class="col-12 text-center mt-3">
    <button type="submit" class="btn text-white px-4" style="background-color: #ffa20e;">Submit Request</button>
  </div>

</form>
</div>

</body>
</html>
