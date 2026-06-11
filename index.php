<?php ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orderly - Smart Food Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Lora:ital,wght@0,600;1,400&display=swap" rel="stylesheet">
    <style>

*, *::before, *::after {
    margin: 0; padding: 0; box-sizing: border-box;
}

:root {
    --navy:      #0b1f3a;
    --navy-md:   #132d52;
    --navy-lt:   #1e4175;
    --gold:      #c8963e;
    --gold-lt:   #e0b96a;
    --cream:     #f7f4ef;
    --cream-dk:  #ede8e0;
    --white:     #ffffff;
    --text:      #1a2535;
    --muted:     #5a6a80;
    --nav-h:     72px;
}

html { scroll-behavior: smooth; }

body {
    font-family: 'Outfit', sans-serif;
    background: var(--cream);
    color: var(--text);
    overflow-x: hidden;
}

/*  NAVBAR */
header {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 1000;
    height: var(--nav-h);
    display: flex;
    align-items: center;
    background: rgba(11, 31, 58, 0.97);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(255,255,255,0.06);
}

.navbar {
    max-width: 1200px;
    margin: auto;
    padding: 0 48px;
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-family: 'Lora', serif;
    font-size: 24px;
    font-weight: 600;
    color: white;
    text-decoration: none;
    letter-spacing: 0.5px;
}

.logo span { color: var(--gold); }

.nav-menu {
    display: flex;
    list-style: none;
    align-items: center;
    gap: 4px;
}

.nav-menu a {
    color: rgba(255,255,255,0.75);
    text-decoration: none;
    font-size: 15px;
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 8px;
    transition: all 0.2s;
}

.nav-menu a:hover {
    color: white;
    background: rgba(255,255,255,0.08);
}

.btn-login {
    color: rgba(255,255,255,0.85) !important;
    border: 1px solid rgba(255,255,255,0.25) !important;
    padding: 8px 20px !important;
    border-radius: 8px !important;
    font-weight: 500 !important;
    transition: all 0.2s !important;
}

.btn-login:hover {
    color: white !important;
    border-color: rgba(255,255,255,0.5) !important;
    background: rgba(255,255,255,0.06) !important;
}

.btn-register {
    background: var(--gold) !important;
    color: white !important;
    padding: 8px 22px !important;
    border-radius: 8px !important;
    font-weight: 600 !important;
    transition: all 0.2s !important;
}

.btn-register:hover {
    background: var(--gold-lt) !important;
    color: white !important;
}

/*  SECTIONS  */
section {
    min-height: 100vh;
    width: 100%;
    padding: calc(var(--nav-h) + 80px) 80px 80px;
}

.section-label {
    display: inline-block;
    background: rgba(200,150,62,0.15);
    color: var(--gold);
    border: 1px solid rgba(200,150,62,0.3);
    padding: 6px 16px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 20px;
}

.hero {
    background: var(--navy);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 80px;
    position: relative;
    overflow: hidden;
    padding-top: var(--nav-h);
}

.hero::before {
    content: '';
    position: absolute;
    width: 700px; height: 700px;
    background: radial-gradient(circle, rgba(200,150,62,0.12) 0%, transparent 65%);
    top: -100px; right: -100px;
    border-radius: 50%;
    pointer-events: none;
}

.hero::after {
    content: '';
    position: absolute;
    width: 400px; height: 400px;
    background: radial-gradient(circle, rgba(30,65,117,0.6) 0%, transparent 70%);
    bottom: -80px; left: -80px;
    border-radius: 50%;
    pointer-events: none;
}

.tagline {
    display: inline-block;
    background: rgba(200,150,62,0.15);
    color: var(--gold-lt);
    border: 1px solid rgba(200,150,62,0.25);
    padding: 7px 18px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 1px;
    margin-bottom: 24px;
}

.hero-text {
    width: 52%;
    position: relative;
    z-index: 1;
}

.hero-text h1 {
    font-family: 'Lora', serif;
    font-size: clamp(40px, 5vw, 62px);
    font-weight: 600;
    line-height: 1.2;
    margin-bottom: 22px;
    color: white;
}

.hero-text h1 em {
    font-style: italic;
    color: var(--gold-lt);
}

.hero-text p {
    font-size: 18px;
    line-height: 1.8;
    color: rgba(255,255,255,0.68);
    margin-bottom: 36px;
}

.hero-buttons {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
}

.btn {
    display: inline-block;
    text-decoration: none;
    padding: 14px 30px;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.25s;
    font-family: 'Outfit', sans-serif;
}

.btn-primary {
    background: var(--gold);
    color: white;
}

.btn-primary:hover {
    background: var(--gold-lt);
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(200,150,62,0.4);
}

.btn-outline {
    border: 1.5px solid rgba(255,255,255,0.3);
    color: rgba(255,255,255,0.85);
}

.btn-outline:hover {
    border-color: var(--gold);
    color: var(--gold-lt);
}

.hero-image-ring {
    position: relative;
    width: 550px;
    height: 550px;
    animation: orbit 8s linear infinite;
}

.hero-image-ring img {
    position: absolute;
    width: 450px;  
    top: 50px;
    left: 50px;
    border-radius: 0;
}

@keyframes orbit {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}


.about {
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 80px;
}

.about-image img {
    width: 420px;
    height: 460px;
    object-fit: cover;
    border-radius: 20px;
    box-shadow: 0 24px 60px rgba(11,31,58,0.15);
}

.about-text { width: 52%; }

.about-text h2 {
    font-family: 'Lora', serif;
    font-size: clamp(32px, 4vw, 48px);
    color: var(--navy);
    margin-bottom: 20px;
    line-height: 1.2;
}

.about-text p {
    font-size: 17px;
    line-height: 1.85;
    color: var(--muted);
    margin-bottom: 16px;
}

.about-cards {
    display: flex;
    gap: 16px;
    margin-top: 28px;
}

.small-card {
    background: var(--cream);
    padding: 22px;
    border-radius: 14px;
    border-left: 3px solid var(--gold);
    flex: 1;
}

.small-card h3 {
    color: var(--navy);
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 8px;
}

.small-card p {
    font-size: 14px;
    color: var(--muted);
    line-height: 1.6;
    margin: 0;
}

.services {
    background: var(--cream);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.services h2 {
    font-family: 'Lora', serif;
    font-size: clamp(32px, 4vw, 48px);
    color: var(--navy);
    margin-bottom: 12px;
}

.services > p {
    font-size: 17px;
    color: var(--muted);
    margin-bottom: 56px;
    max-width: 480px;
}

.service-container {
    display: flex;
    justify-content: center;
    gap: 24px;
    flex-wrap: wrap;
    width: 100%;
}

.service-card {
    background: white;
    width: 260px;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(11,31,58,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
    text-align: left;
}

.service-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 16px 48px rgba(11,31,58,0.15);
}

.service-card img {
    width: 100%;
    height: 175px;
    object-fit: cover;
}

.service-card-body { padding: 22px; }

.service-card h3 {
    color: var(--navy);
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 8px;
}

.service-card p {
    font-size: 14px;
    line-height: 1.65;
    color: var(--muted);
}


.register {
    background: var(--navy);
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 80px;
    position: relative;
    overflow: hidden;
}

.register::before {
    content: '';
    position: absolute;
    width: 500px; height: 500px;
    background: radial-gradient(circle, rgba(200,150,62,0.1) 0%, transparent 65%);
    bottom: -150px; right: -100px;
    border-radius: 50%;
    pointer-events: none;
}

.register-info { width: 42%; position: relative; z-index: 1; }

.register-info h2 {
    font-family: 'Lora', serif;
    font-size: clamp(30px, 4vw, 46px);
    color: white;
    margin-bottom: 18px;
    line-height: 1.2;
}

.register-info p {
    font-size: 17px;
    line-height: 1.8;
    color: rgba(255,255,255,0.65);
    margin-bottom: 28px;
}

.register-info ul {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.register-info li {
    font-size: 16px;
    color: rgba(255,255,255,0.8);
    display: flex;
    align-items: center;
    gap: 10px;
}

.register-info li::before {
    content: '✓';
    width: 22px; height: 22px;
    background: var(--gold);
    color: white;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}

.register .form-wrap {
    background: white;
    width: 440px;
    padding: 36px;
    border-radius: 20px;
    box-shadow: 0 24px 64px rgba(0,0,0,0.3);
    position: relative;
    z-index: 1;
}

.register .form-wrap h3 {
    font-family: 'Lora', serif;
    font-size: 22px;
    color: var(--navy);
    margin-bottom: 24px;
}


.contact {
    background: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
}

.contact h2 {
    font-family: 'Lora', serif;
    font-size: clamp(32px, 4vw, 48px);
    color: var(--navy);
    margin-bottom: 12px;
}

.contact > p {
    font-size: 17px;
    color: var(--muted);
    margin-bottom: 48px;
}

.contact-container {
    display: flex;
    justify-content: center;
    gap: 24px;
    flex-wrap: wrap;
}

.contact-box,
.contact-note {
    background: var(--cream);
    width: 400px;
    padding: 36px;
    border-radius: 18px;
    border: 1px solid var(--cream-dk);
    text-align: left;
    transition: box-shadow 0.25s;
}

.contact-box:hover,
.contact-note:hover {
    box-shadow: 0 8px 32px rgba(11,31,58,0.1);
}

.contact-box h3,
.contact-note h3 {
    color: var(--navy);
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 18px;
    padding-bottom: 14px;
    border-bottom: 2px solid var(--cream-dk);
}

.contact-box p,
.contact-note p {
    font-size: 16px;
    line-height: 1.8;
    color: var(--muted);
    margin-bottom: 10px;
}

.contact-box p b { color: var(--navy); font-weight: 600; }

/*  FEEDBACK  */
.feedback {
    background: var(--cream);
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 80px;
}

.feedback h2 {
    font-family: 'Lora', serif;
    font-size: clamp(32px, 4vw, 48px);
    color: var(--navy);
    margin-bottom: 20px;
}

.feedback-text { width: 42%; }

.feedback-text p {
    font-size: 17px;
    line-height: 1.8;
    color: var(--muted);
    margin-bottom: 16px;
}


form {
    width: 100%;
}

form input,
form textarea,
form select {
    width: 100%;
    padding: 13px 16px;
    margin-bottom: 14px;
    border-radius: 10px;
    border: 1.5px solid #dde3ec;
    font-size: 15px;
    font-family: 'Outfit', sans-serif;
    color: var(--text);
    background: var(--cream);
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}

form input:focus,
form textarea:focus,
form select:focus {
    border-color: var(--navy-lt);
    box-shadow: 0 0 0 3px rgba(30,65,117,0.1);
    background: white;
}

form textarea { height: 130px; resize: none; }

form button {
    width: 100%;
    padding: 14px;
    border: none;
    background: var(--navy);
    color: white;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    font-family: 'Outfit', sans-serif;
    cursor: pointer;
    transition: background 0.25s, transform 0.2s;
    margin-top: 4px;
}

form button:hover {
    background: var(--navy-lt);
    transform: translateY(-1px);
}

.form-card {
    background: white;
    width: 440px;
    padding: 36px;
    border-radius: 20px;
    box-shadow: 0 8px 40px rgba(11,31,58,0.12);
}

.form-card h3 {
    font-family: 'Lora', serif;
    font-size: 22px;
    color: var(--navy);
    margin-bottom: 24px;
}


footer {
    background: var(--navy);
    color: white;
    text-align: center;
    padding: 40px;
    border-top: 1px solid rgba(255,255,255,0.07);
}

footer h3 {
    font-family: 'Lora', serif;
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 10px;
    color: white;
}

footer h3 span { color: var(--gold); }

footer p {
    font-size: 14px;
    color: rgba(255,255,255,0.45);
    margin-bottom: 6px;
}


@media (max-width: 768px) {
    .navbar { padding: 0 24px; }
    .nav-menu { display: none; }
    section { padding: 100px 28px 60px; }

    .hero,
    .about,
    .register,
    .feedback {
        flex-direction: column;
        text-align: center;
        gap: 40px;
    }

    .hero-text,
    .about-text,
    .register-info,
    .feedback-text { width: 100%; }

    .hero-buttons { justify-content: center; flex-wrap: wrap; }

    .hero-image-ring { width: 260px; height: 260px; }
    .about-image img { width: 260px; height: 260px; }

    .register .form-wrap,
    .form-card,
    .contact-box,
    .contact-note { width: 100%; }

    .about-cards { flex-direction: column; }
}

    </style>
</head>
<body>


<header>
    <nav class="navbar">
        <a href="#home" class="logo">Order<span>ly</span></a>

        <ul class="nav-menu">
            <li><a href="#home">Home</a></li>
            <li><a href="#about">About Us</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="feedback.php">Feedback</a></li>
            <li>
                <a href="signup_login.php" class="btn-register">Register</a>
            </li>
        </ul>
    </nav>
</header>

<! HERO >
<section class="hero" id="home">
    <div class="hero-text">
        <span class="tagline">Your Food, Your Choice</span>
        <h1>Welcome to  <em>Orderly</em></h1>
        <p>
            Discover meals made for you.
From dietary preferences to allergy-friendly options and budget-friendly choices, 
Orderly helps you explore food effortlessly with a smarter and more personalized ordering experience.
        </p>

        <div class="hero-buttons">
            <a href="signup_login.php" class="btn btn-primary">Get Started</a>
            <a href="#contact" class="btn btn-outline">Contact Us</a>
        </div>
    </div>

    <div class="hero-image">
        <div class="hero-image-ring" id="heroRing">
            <img src="homepage.png" alt="Food">
        </div>
    </div>
</section>

<!ABOUT >
<section class="about" id="about">
    <div class="about-image">
        <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c" alt="Healthy food">
    </div>

    <div class="about-text">
        <span class="section-label">About Us</span>
        <h2>About Orderly</h2>
        <p>
            At Orderly, we aim to simplify food ordering by helping users find meals that match their dietary preferences, allergies, and budget.
        </p>
        <p>
             Our platform provides a safer, faster, and more personalized dining experience, making it easier for everyone to discover food that fits their lifestyle and needs.
        </p>

        <div class="about-cards">
            <div class="small-card">
                <h3>Mission</h3>
                <p>To make food selection easier for users.</p>
            </div>
            <div class="small-card">
                <h3>Goal</h3>
                <p>To provide a smooth and friendly user experience.</p>
            </div>
        </div>
    </div>
</section>


<section class="services" id="services">
    <span class="section-label">What We Provide</span>
    <h2>Our Services</h2>
    <p>Everything you need to discover and order food, all in one place.</p>

    <div class="service-container">
        <div class="service-card">
            <img src="foodrecommendation.png" alt="Food">
            <div class="service-card-body">
                <h3>Food Recommendation</h3>
                <p>Helps users choose suitable food based on their preferences.</p>
            </div>
        </div>

        <div class="service-card">
            <img src="foodservices.jpg" alt="Menu">
            <div class="service-card-body">
                <h3>View Food Services</h3>
                <p>Users can view the available food-related services clearly.</p>
            </div>
        </div>

        <div class="service-card">
            <img src="platformaccess.jpg" alt="Order">
            <div class="service-card-body">
                <h3>Easy Platform Access</h3>
                <p>Simple website navigation for users to browse information.</p>
            </div>
        </div>

        <div class="service-card">
            <img src="Youtube Assets.jpg" alt="Support">
            <div class="service-card-body">
                <h3>User Support</h3>
                <p>Users can contact the team if they need help or information.</p>
            </div>
        </div>
    </div>
</section>


<section class="contact" id="contact">
    <span class="section-label">Need Help?</span>
    <h2>Contact Us</h2>
    <p>Have any questions? You can reach us through the information below.</p>

    <div class="contact-container">
        <div class="contact-box">
            <h3>Contact Information</h3>
            <p><b>Email:</b> orderly@gmail.com</p>
            <p><b>Phone:</b> 018-2375956</p>
            <p><b>Location:</b> Kuala Lumpur, Malaysia</p>
        </div>

        <div class="contact-note">
            <h3>We Are Here To Help</h3>
            <p>
                Users can contact Orderly for questions about the platform,
                registration, services, or general information.
            </p>
        </div>
    </div>
</section>


<footer>
    <h3>Order<span>ly</span></h3>
    <p>Smart Food Selection and Ordering Platform</p>
    <p style="margin-top:16px;">© 2026 Orderly. All Rights Reserved.</p>
</footer>

<script>
   
    const ring = document.getElementById('heroRing');

    window.addEventListener('scroll', () => {
        const scrolled = window.scrollY;
        
        const deg = scrolled / 5;
        ring.style.setProperty('--ring-deg', deg + 'deg');
    }, { passive: true });
</script>

<style>
    
    #heroRing { --ring-deg: 0deg; }
    #heroRing::before { transform: rotate(var(--ring-deg)); }
</style>

</body>
</html>