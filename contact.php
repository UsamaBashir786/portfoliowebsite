<?php
// Set error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Access denied"]);
    exit;
}

// Collect and sanitize form data
$name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING);
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$subject = filter_var($_POST['subject'] ?? '', FILTER_SANITIZE_STRING);
$message = filter_var($_POST['message'] ?? '', FILTER_SANITIZE_STRING);

// Validate form data
if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid email format"]);
    exit;
}

// Admin email to receive the contact form
$admin_email = "jubranyounas@gmail.com"; // Change this to your email

// Prepare headers - important for proper email delivery
$headers = [
    'From' => $email,
    'Reply-To' => $email,
    'X-Mailer' => 'PHP/' . phpversion(),
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8'
];

// Format headers for mail() function
$header_string = '';
foreach($headers as $key => $value) {
    $header_string .= "$key: $value\r\n";
}

// Prepare the email for the admin
$admin_subject = "New Contact Form Submission from $name";
$admin_message = "Name: $name\r\n";
$admin_message .= "Email: $email\r\n";
$admin_message .= "Subject: $subject\r\n\r\n";
$admin_message .= "Message:\r\n$message\r\n";

// Log attempt (helpful for debugging)
error_log("Attempting to send email to $admin_email from $email");

// Send the email to the admin
$admin_sent = mail($admin_email, $admin_subject, $admin_message, $header_string);

// Only send confirmation to user if admin email was successful
if ($admin_sent) {
    // Prepare the email to send to the user
    $user_subject = "Thank You for Contacting Us, $name!";
    $user_message = "Hi $name,\r\n\r\n";
    $user_message .= "Thank you for reaching out to us. We have received your message and will get back to you as soon as possible.\r\n\r\n";
    $user_message .= "Your Message:\r\n$message\r\n";
    
    // Different from header to show website as sender
    $user_headers = [
        'From' => $admin_email,
        'Reply-To' => $admin_email,
        'X-Mailer' => 'PHP/' . phpversion(),
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8'
    ];
    
    // Format headers
    $user_header_string = '';
    foreach($user_headers as $key => $value) {
        $user_header_string .= "$key: $value\r\n";
    }
    
    // Send email to user
    $user_sent = mail($email, $user_subject, $user_message, $user_header_string);
    
    // Return success
    echo json_encode(["success" => true, "message" => "Message sent successfully!"]);
} else {
    // Log error
    error_log("Failed to send email: " . error_get_last()['message']);
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to send message. Please try again later."]);
}
?>