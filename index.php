<?php include("includes/header.php"); ?>

<section class="slider">

  <div class="slide active">
    <img src="/Findex/images/slide1.jpg">

    <div class="slide-overlay">
      <div class="slide-content">
        <h1>Recover Lost Items Faster</h1>
        <p>Structured reports and smart matching system</p>

        <a href="<?= isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php'; ?>" class="btn">
          Get Started
        </a>
      </div>
    </div>
  </div>

  <div class="slide">
    <img src="/Findex/images/slide2.jpg">

    <div class="slide-overlay">
      <div class="slide-content">
        <h1>Secure Communication</h1>
        <p>Connect directly with verified users</p>
      </div>
    </div>
  </div>

  <div class="slide">
    <img src="/Findex/images/slide3.jpg">

    <div class="slide-overlay">
      <div class="slide-content">
        <h1>Organized Tracking</h1>
        <p>Everything managed in one platform</p>
      </div>
    </div>
  </div>

</section>


<!-- HERO -->
<section class="hero">

  <div class="hero-left">
    <h1 class="title">
      Recover Lost Jewelry <br><span>Smarter & Faster</span>
    </h1>

    <p class="subtitle">
      Findex provides a structured platform where users can report lost items, 
      analyze potential matches, and communicate securely to recover valuables efficiently.
    </p>

<div class="hero-buttons">
  <a href="<?= isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php'; ?>" class="btn">
    <?= isset($_SESSION['user_id']) ? 'Go to Dashboard' : 'Get Started'; ?>
  </a>
</div>
  </div>

  <div class="hero-right">
    <div class="floating-card">Searching active reports</div>
    <div class="floating-card delay">Processing matches</div>
    <div class="floating-card delay2">Users communicating</div>
  </div>

</section>

<!-- HIGHLIGHT -->
<section class="highlight">
  <div class="highlight-card">
    <h2>All-in-One Recovery Platform</h2>
    <p>
      A centralized environment designed to simplify reporting, tracking, 
      and communication — ensuring faster and more reliable recovery processes.
    </p>
  </div>
</section>

<!-- FEATURES -->
<section class="grid">

  <div class="card">
    <h3>Smart Matching</h3>
    <p>Identify potential matches between reports using structured data and filtering.</p>
  </div>

  <div class="card">
    <h3>Image Enhancement</h3>
    <p>Improve visibility of items to increase recognition and identification accuracy.</p>
  </div>

  <div class="card">
    <h3>Real-Time Chat</h3>
    <p>Communicate instantly with users to verify and coordinate item recovery.</p>
  </div>

  <div class="card">
    <h3>Verified Shops</h3>
    <p>Interact with trusted sources to increase reliability and security.</p>
  </div>

</section>

<!-- LIVE ACTIVITY -->
<section class="activity">
  <h2>Live Platform Activity</h2>

  <div class="activity-box" id="activityBox">
    A user is currently searching for a lost item...
  </div>
</section>

<!-- CTA -->
<section class="cta">
  <h2>Start Your Recovery Journey Today</h2>
  <a href="<?= isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php'; ?>" class="btn">
    <?= isset($_SESSION['user_id']) ? 'Continue to Dashboard' : 'Create Account'; ?>
  </a>
</section>

<section class="process">

  <h2 class="section-title">How It Works</h2>

  <div class="process-grid">

    <div class="process-card">
      <div class="process-number">01</div>
      <h3>Submit Report</h3>
      <p>Provide structured details about the item including description and category.</p>
    </div>

    <div class="process-card">
      <div class="process-number">02</div>
      <h3>System Analysis</h3>
      <p>The platform compares reports and identifies possible matches.</p>
    </div>

    <div class="process-card">
      <div class="process-number">03</div>
      <h3>Communication</h3>
      <p>Users connect directly through private messaging.</p>
    </div>

    <div class="process-card">
      <div class="process-number">04</div>
      <h3>Recovery</h3>
      <p>Items are verified and returned through coordinated interaction.</p>
    </div>

  </div>

</section>


<!-- STATS -->
<section class="stats">

  <div class="stats-grid">

    <div class="stat">
      <h2>120+</h2>
      <p>Reports Submitted</p>
    </div>

    <div class="stat">
      <h2>85%</h2>
      <p>Successful Matches</p>
    </div>

    <div class="stat">
      <h2>300+</h2>
      <p>Active Users</p>
    </div>

  </div>

</section>


<!-- FINAL -->
<section class="final">

  <div class="final-box">
    <h2>Built for Efficiency and Trust</h2>

    <p>
      The platform is designed to reduce uncertainty by organizing information, 
      enabling direct communication, and improving the recovery process.
    </p>

    <a href="<?= isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php'; ?>" class="btn">
      Get Started
    </a>
  </div>

</section>

<?php include("includes/footer.php"); ?>