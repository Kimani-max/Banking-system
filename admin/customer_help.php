<?php
// customer_help.php
session_start();
include('config.php'); // your DB connection file

// Optional: get customer_id from session if logged in
$customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : null;

$message_feedback = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

     if (!empty($name) && !empty($email) && !empty($message)) {
        $sql = "INSERT INTO support_requests (customer_id, name, email, message) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $customer_id, $name, $email, $message);

        if ($stmt->execute()) {
            $message_feedback = "<div class='message success'>✅ Thank you, $name! Your message has been sent. Our team will get back to you soon.</div>";
        } else {
            $message_feedback = "<div class='message error'>⚠️ Error: Unable to save your message. Please try again later.</div>";
        }

        $stmt->close();
    } else {
        $message_feedback = "<div class='message error'>⚠️ Please fill in all fields before submitting.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Help | Bank System</title>
    <link rel="stylesheet" href="assets/css/style.css"> <!-- adjust path if needed -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .help-container {
            width: 90%;
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 30px;
        }

        h2 {
            text-align: center;
            color: #8A2BE2;
        }

        .faq-section {
            margin-top: 30px;
        }

        .faq {
            background: #f9fafc;
            border-left: 4px solid #8A2BE2;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 6px;
        }

        .faq h4 {
            margin: 0;
            color: #8A2BE2;
        }

        .faq p {
            margin: 5px 0 0 0;
            color: #555;
        }

        .contact-form {
            margin-top: 40px;
        }

        .contact-form form {
            display: flex;
            flex-direction: column;
        }

        .contact-form input, .contact-form textarea {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }

        .contact-form button {
            background: #8A2BE2;
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        .contact-form button:hover {
            background: #8A2BE2;
        }

        .message {
            text-align: center;
            padding: 10px;
            border-radius: 5px;
        }

        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="help-container">
    <h2>Customer Help Center</h2>
    <p style="text-align:center;">Find answers to common questions or reach out to our support team.</p>

    <!-- FAQ Section -->
    <div class="faq-section">
        <h3>Frequently Asked Questions</h3>

        <div class="faq">
            <h4>1. How can I view my account balance?</h4>
            <p>Log in to your account and go to the "Accounts" section. Your current balance will be displayed next to each account.</p>
        </div>

        <div class="faq">
            <h4>2. How do I transfer money between my accounts?</h4>
            <p>Navigate to the "Transfers" page, select the source and destination accounts, enter the amount, and click "Submit".</p>
        </div>

        <div class="faq">
            <h4>3. What should I do if I forget my password?</h4>
            <p>Use the "Forgot Password" link on the login page. Enter your registered email to receive password reset instructions.</p>
        </div>

        <div class="faq">
            <h4>4. How can I contact customer support?</h4>
            <p>You can use the form below to send us a message, and our support team will get back to you within 24 hours.</p>
        </div>
    </div>

    <!-- Contact Form Section -->
    <div class="contact-form">
        <h3>Contact Support</h3>

        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $name = htmlspecialchars($_POST['name']);
            $email = htmlspecialchars($_POST['email']);
            $message = htmlspecialchars($_POST['message']);

            if (!empty($name) && !empty($email) && !empty($message)) {
                // For now, just show a success message
                // Later you can send email or save to DB
                echo "<div class='message success'>Thank you, $name. Your message has been received. We'll contact you soon!</div>";
            } else {
                echo "<div class='message error'>Please fill in all fields before submitting.</div>";
            }
        }
        ?>

        <form method="POST" action="">
            <input type="text" name="name" placeholder="Your Full Name" required>
            <input type="email" name="email" placeholder="Your Email Address" required>
            <textarea name="message" rows="5" placeholder="Describe your issue or question..." required></textarea>
            <button type="submit">Send Message</button>
        </form>
    </div>
</div>

</body>
</html>
