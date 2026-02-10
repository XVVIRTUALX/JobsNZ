<?php
// Configuration
$employer_email = "employer@example.com"; // replace with real employer email
$vocc_email = "voccsupport@example.com"; // optional VOCC CC

// Get form data
$name   = $_POST['name'] ?? '';
$email  = $_POST['email'] ?? '';
$jobid  = $_POST['jobid'] ?? '';
$quote  = $_POST['quote'] ?? '';
$notes  = $_POST['notes'] ?? '';

// Validate required fields
if(empty($name) || empty($email) || empty($jobid) || empty($quote)) {
    die("Please fill in all required fields.");
}

// Prepare email
$subject = "New Painting Quote Submission - Job ID: $jobid";
$message = "You have received a new quote submission for Job ID: $jobid\n\n";
$message .= "Name: $name\n";
$message .= "Email: $email\n";
$message .= "Quote Amount: $quote NZD\n";
$message .= "Notes: $notes\n";

// Handle file uploads
$attachments = [];
if(isset($_FILES['files'])) {
    foreach($_FILES['files']['tmp_name'] as $key => $tmp_name) {
        $file_name = $_FILES['files']['name'][$key];
        $file_tmp  = $_FILES['files']['tmp_name'][$key];
        $file_size = $_FILES['files']['size'][$key];

        // Limit file size (e.g., 10MB)
        if($file_size > 10*1024*1024) continue;

        // Move to temporary folder
        $upload_dir = "uploads/";
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $file_path = $upload_dir . basename($file_name);
        if(move_uploaded_file($file_tmp, $file_path)) {
            $attachments[] = $file_path;
        }
    }
}

// Send email using PHPMailer (recommended for attachments)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.example.com'; // your SMTP server
    $mail->SMTPAuth   = true;
    $mail->Username   = 'you@example.com';
    $mail->Password   = 'yourpassword';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    //Recipients
    $mail->setFrom($email, $name);
    $mail->addAddress($employer_email);  
    if(!empty($vocc_email)) $mail->addCC($vocc_email);

    // Attachments
    foreach($attachments as $file) {
        $mail->addAttachment($file);
    }

    // Content
    $mail->isHTML(false);
    $mail->Subject = $subject;
    $mail->Body    = $message;

    $mail->send();
    echo "<p>Quote submitted successfully! <a href='/'>Back to Home</a></p>";

    // Optional: delete uploaded files after sending to keep server clean
    foreach($attachments as $file) {
        unlink($file);
    }

} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>
