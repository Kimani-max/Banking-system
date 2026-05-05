<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Bank Management System - Home</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<!-- Navbar -->
<header class="nav">
    <div class="brand">
        <img src="../assets/images/bank_logo.png" alt="Bank Logo" class="logo">
        <span>PHARNYBIC BANK</span>
    </div>
    <div class="nav-actions">
        <a href="login.php" class="btn">Login</a>
        <a href="register.php" class="btn btn-primary">Signup</a>
        <a href="tickets.php" class="btn">Ask a Question</a>
        <button class="btn" onclick="toggleSearch()">Search</button>
    </div>
</header>

<!-- Search Box -->
<div id="searchBox" class="search-box" style="display:none;">
    <form method="get" action="search.php">
        <input type="text" class="input" name="q" placeholder="Search...">
        <button type="submit" class="btn btn-go-primary">Go</button>
    </form>
</div>

<!-- Slideshow -->
<section class="hero">
    <div class="slider">
        <div class="slideshow">
             <img  class="slide active" src="../assets/images/image1.jpg" alt="Bank Service 1">
             <img  class="slide " src="../assets/images/image2.avif" alt="Bank Service 2">
             <img  class="slide" src="../assets/images/image10.jpg" alt="Bank Service 3">
             <img  class="slide" src="../assets/images/image7.jpg" alt="Bank Service 4">
             <img  class="slide" src="../assets/images/image4.jpg" alt="Bank Service 5">
             <img  class="slide" src="../assets/images/image8.jpg" alt="Bank Service 6">
             <img  class="slide" src="../assets/images/image9.jpg" alt="Bank Service 7">
             <img  class="slide" src="../assets/images/image5.jpg" alt="Bank Service 8">
             <img  class="slide" src="../assets/images/image6.jpg" alt="Bank Service 9">
        </div>
    </div>
</section>

<!-- About -->
<section class="section">
    <h2 class="section-title">About Our Bank</h2>
    <p>Welcome to our Bank Management System. We provide safe, reliable, and modern banking services to our customers.</p>
</section>

<!-- Services -->
<section class="section">
    <h2 class="section-title">Our Services</h2>
    <ul class="services">
        <li>Account Opening</li>
        <li>Deposits & Withdrawals</li>
        <li>Loan Applications</li>
        <li>Online Banking</li>
    </ul>
</section>

<!-- Contact -->
<section class="section">
    <h2 class="section-title">Contact Us</h2>
    <p>Email: <a href="mailto:support@yourbank.com">support@yourbank.com</a> | Phone: +254 723 456 789</p>
</section>

<?php include '../admin/includes/footer.php'; ?>

<script>
    // Search toggle
    function toggleSearch() {
        var box = document.getElementById("searchBox");
        box.style.display = (box.style.display === "block") ? "none" : "block";
    }

    // Run slideshow after page loads
    window.onload = function() {
        let slideIndex = 0;
        const slides = document.querySelectorAll(".slide");

        function showSlides() {
            slides.forEach((s) => s.classList.remove("active"));
            slideIndex = (slideIndex + 1) % slides.length;
            slides[slideIndex].classList.add("active");
        }

        setInterval(showSlides, 4000); // change every 4 seconds
    }
</script>
