<?php include("includes/header.php"); ?>

<!-- SLIDER -->
<section class="slider">

  <div class="slide active">
    <img src="/Findex/images/slide1.jpg">

    <div class="slide-overlay">
      <div class="slide-content glass-box">
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
      <div class="slide-content glass-box">
        <h1>Secure Communication</h1>
        <p>Connect directly with verified users</p>
      </div>
    </div>
  </div>

  <div class="slide">
    <img src="/Findex/images/slide3.jpg">
    <div class="slide-overlay">
      <div class="slide-content glass-box">
        <h1>Organized Tracking</h1>
        <p>Everything managed in one platform</p>
      </div>
    </div>
  </div>

</section>


<!-- HERO -->
<section class="hero reveal">

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


<section class="grid reveal">

  <div class="flip-card">
    <div class="flip-inner">
      <div class="flip-front blue">
        <h3>Centralized Reporting</h3>
      </div>
      <div class="flip-back">
        <p>Submit and manage all lost and found items in one structured system.</p>
      </div>
    </div>
  </div>

  <div class="flip-card">
    <div class="flip-inner">
      <div class="flip-front green">
        <h3>Faster Recovery</h3>
      </div>
      <div class="flip-back">
        <p>Reduce recovery time through smart organization and communication.</p>
      </div>
    </div>
  </div>

  <div class="flip-card">
    <div class="flip-inner">
      <div class="flip-front blue">
        <h3>Secure Interaction</h3>
      </div>
      <div class="flip-back">
        <p>Communicate safely with verified users inside the platform.</p>
      </div>
    </div>
  </div>

</section>


<!-- HIGHLIGHT -->
<section class="highlight reveal">
  <div class="highlight-card">
    <h2>All-in-One Recovery Platform</h2>
    <p>
      A centralized environment designed to simplify reporting, tracking, 
      and communication — ensuring faster and more reliable recovery processes.
    </p>
  </div>
</section>


<!-- FEATURES -->
<section class="grid reveal">

  <div class="card">
    <h3>Smart Matching</h3>
    <p>Identify potential matches between reports using structured data.</p>
  </div>

  <div class="card">
    <h3>Image Enhancement</h3>
    <p>Improve visibility of items for better identification.</p>
  </div>

  <div class="card">
    <h3>Real-Time Chat</h3>
    <p>Communicate instantly with users to coordinate recovery.</p>
  </div>

  <div class="card">
    <h3>Verified Shops</h3>
    <p>Interact with trusted sources to increase reliability.</p>
  </div>

</section>


<div class="home-sections">

  <!-- LIVE ACTIVITY -->
  <section class="activity reveal section-card">
    <h2>Live Platform Activity</h2>

    <div class="activity-box enhanced">
      <span class="status-dot"></span>
      <strong>Status:</strong> 
      <span id="activityText">Loading...</span>
    </div>
  </section>


  <!-- CTA -->
  <section class="cta reveal section-card gradient">
    <h2>Start Your Recovery Journey Today</h2>

    <p class="cta-text">
      Join a structured platform designed for efficient reporting and secure communication.
    </p>

    <a href="<?= isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php'; ?>" class="btn">
      <?= isset($_SESSION['user_id']) ? 'Continue to Dashboard' : 'Create Account'; ?>
    </a>
  </section>


  <!-- PROCESS -->
  <section class="process reveal section-card">

    <h2 class="section-title">How It Works</h2>

    <div class="process-grid">

      <div class="process-card">
        <div class="process-number">01</div>
        <h3>Submit Report</h3>
        <p>Provide structured details about the item.</p>
      </div>

      <div class="process-card">
        <div class="process-number">02</div>
        <h3>System Analysis</h3>
        <p>Platform identifies potential matches.</p>
      </div>

      <div class="process-card">
        <div class="process-number">03</div>
        <h3>Communication</h3>
        <p>Users connect directly via chat.</p>
      </div>

      <div class="process-card">
        <div class="process-number">04</div>
        <h3>Recovery</h3>
        <p>Items are verified and returned.</p>
      </div>

    </div>

  </section>


  <!-- STATS -->
  <section class="stats reveal section-card">

    <div class="stats-grid">

<div class="stat">
  <h3>Structured System</h3>
  <p>All data is organized to reduce confusion and improve tracking.</p>
</div>

<div class="stat">
  <h3>Reliable Matching</h3>
  <p>Advanced logic helps identify potential matches efficiently.</p>
</div>

<div class="stat">
  <h3>Secure Interaction</h3>
  <p>Communicate with users safely within the platform.</p>
</div>

    </div>

  </section>

</div>


<!-- FINAL -->
<section class="final reveal">

  <div class="final-box">

    <div class="final-text">
      <h2>Built for Efficiency and Trust</h2>

      <p>
        The platform is designed to reduce uncertainty by organizing information, 
        enabling direct communication, and improving the recovery process.
      </p>

      <a href="<?= isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php'; ?>" class="btn">
        Get Started
      </a>
    </div>

    <!-- visual element (simple, clean) -->
    <div class="final-visual">
      <div class="visual-card"></div>
    </div>

  </div>

</section>

<?php include("includes/footer.php"); ?>

<script src="/Findex/js/home.js?v=2"></script>