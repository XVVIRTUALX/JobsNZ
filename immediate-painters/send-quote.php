<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Email for VOCC notifications
$voccs_email = "virtualonlinenz@outlook.com";

// Determine submission type
$type = $_POST['type'] ?? '';

if ($type === 'jobpost') {
    // -----------------------
    // Handle Job Post
    // -----------------------
    $title       = $_POST['title'] ?? '';
    $location    = $_POST['location'] ?? '';
    $budget      = $_POST['budget'] ?? '';
    $description = $_POST['description'] ?? '';

    if (empty($title) || empty($location) || empty($budget) || empty($description)) {
        die("Please fill in all required fields.");
    }

    // Save job to jobs.json
    $jobsFile = __DIR__ . '/jobs/jobs.json';
    if (!file_exists($jobsFile)) file_put_contents($jobsFile, json_encode([]));
    $jobs = json_decode(file_get_contents($jobsFile), true);

    $jobId = uniqid('job_');
    $newJob = [
        'id' => $jobId,
        'title' => $title,
        'location' => $location,
        'budget' => $budget,
        'description' => $description,
        'posted_at' => date('Y-m-d H:i:s')
    ];
    $jobs[] = $newJob;
    file_put_contents($jobsFile, json_encode($jobs, JSON_PRETTY_PRINT));

    // Email VOCC
    $subject = "New Painting Job Posted: $title";
    $message = "A new painting job has been posted.\n\n";
    $message .= "Title: $title\nLocation: $location\nBudget: $budget\nDescription: $description\nJob ID: $jobId";

    mail($voccs_email, $subject, $message);

    echo "<p>Job posted successfully! <a href='worker-jobs.html'>View Jobs</a></p>";
    exit;
}

// -----------------------
// Handle Quote Submission
// -----------------------
$name   = $_POST['name'] ?? '';
$email  = $_POST['email'] ?? '';
$jobid  = $_POST['jobid'] ?? '';
$quote  = $_POST['quote'] ?? '';
$notes  = $_POST['notes'] ?? '';

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

        // Limit file size (10MB)
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

// Send email using PHPMailer
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.office365.com'; // Outlook SMTP
    $mail->SMTPAuth   = true;
    $mail->Username   = $voccs_email;
    $mail->Password   = 'YOUR_EMAIL_PASSWORD'; // Replace with actual password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom($email, $name);
    $mail->addAddress($voccs_email);

    foreach($attachments as $file) {
        $mail->addAttachment($file);
    }

    $mail->isHTML(false);
    $mail->Subject = $subject;
    $mail->Body    = $message;

    $mail->send();

    echo "<p>Quote submitted successfully! <a href='index.html'>Back to Home</a></p>";

    // Delete uploaded files to keep server clean
    foreach($attachments as $file) {
        unlink($file);
    }

} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>

