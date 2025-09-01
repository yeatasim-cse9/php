<?php
// index.php — Root File

// চাইলে DB কানেকশন লাগলে include করতে পারো:
// include __DIR__ . "/backend/config/db_connect.php";
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>The Cafe Rio – Gulshan | Buffet Restaurant</title>
</head>
<body>

  <!-- Header -->
  <?php include __DIR__ . "/frontend/partials/header.html"; ?>

  <!-- Hero Section -->
  <section id="home" class="bg-light text-dark text-center py-5" style="background: url('frontend/assets/images/hero.jpg') center/cover no-repeat; min-height: 80vh; display:flex; align-items:center;">
    <div class="container">
      <h1 class="display-4 fw-bold text-shadow">Welcome to The Cafe Rio – Gulshan</h1>
      <p class="lead mt-3 mb-4">The finest buffet in Dhaka — perfect for family, friends & celebrations.</p>

      <!-- Offer Badges -->
      <div class="d-flex justify-content-center gap-3 flex-wrap mb-4">
        <span class="badge bg-danger fs-6 px-4 py-3 rounded-pill shadow">🍴 Lunch Buffet ~ 1050৳</span>
        <span class="badge bg-dark fs-6 px-4 py-3 rounded-pill shadow">🍷 Dinner Buffet ~ 1150৳</span>
      </div>

      <a href="#reservations" class="btn btn-danger btn-lg px-5 py-3 rounded-4 shadow">
        <i class="bi bi-calendar2-check me-2"></i> Book Your Table
      </a>
    </div>
  </section>

  <!-- Menu Section -->
  <section id="menu" class="py-5">
    <div class="container">
      <h2 class="text-center mb-4">Our Menu Highlights</h2>
      <div class="row g-4">
        <div class="col-md-4">
          <div class="card h-100 shadow-sm">
            <img src="frontend/assets/images/chicken.jpg" class="card-img-top" alt="Grilled Chicken">
            <div class="card-body">
              <h5 class="card-title">Grilled Chicken</h5>
              <p class="card-text">Juicy grilled chicken with herbs & spices.</p>
              <p class="fw-bold">৳450</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card h-100 shadow-sm">
            <img src="frontend/assets/images/pasta.jpg" class="card-img-top" alt="Pasta Alfredo">
            <div class="card-body">
              <h5 class="card-title">Pasta Alfredo</h5>
              <p class="card-text">Creamy Alfredo pasta with mushrooms.</p>
              <p class="fw-bold">৳390</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card h-100 shadow-sm">
            <img src="frontend/assets/images/mousse.jpg" class="card-img-top" alt="Chocolate Mousse">
            <div class="card-body">
              <h5 class="card-title">Chocolate Mousse</h5>
              <p class="card-text">Rich dark chocolate dessert.</p>
              <p class="fw-bold">৳260</p>
            </div>
          </div>
        </div>
      </div>
      <div class="text-center mt-4">
        <a href="#menu" class="btn btn-outline-danger">View Full Menu</a>
      </div>
    </div>
  </section>

  <!-- Reservations -->
  <section id="reservations" class="py-5 bg-light">
    <div class="container text-center">
      <h2 class="mb-4">Reserve Your Table</h2>
      <p class="text-muted">Online reservation system coming soon.</p>
      <a href="#contact" class="btn btn-danger">Contact for Booking</a>
    </div>
  </section>

  <!-- Order -->
  <section id="order" class="py-5">
    <div class="container text-center">
      <h2 class="mb-4">Order Online</h2>
      <p class="text-muted">Pickup & delivery options available soon.</p>
    </div>
  </section>

  <!-- Reviews -->
  <section id="reviews" class="py-5 bg-light">
    <div class="container text-center">
      <h2 class="mb-4">Customer Reviews</h2>
      <p class="text-muted">⭐ Reviews & ratings will be shown here.</p>
    </div>
  </section>

  <!-- Contact -->
  <section id="contact" class="py-5">
    <div class="container text-center">
      <h2 class="mb-4">Contact Us</h2>
      <p>
        Address: Jabbar Tower, 7th Floor, Gulshan-1, Dhaka 1212 <br>
        Phone: <a href="tel:+8801799437172">01799-437172</a>
      </p>
      <a href="https://www.facebook.com/caferiogulshan" target="_blank" class="btn btn-outline-primary">
        <i class="bi bi-facebook me-2"></i> Visit Our Facebook Page
      </a>
    </div>
  </section>

  <!-- Footer -->
  <?php include __DIR__ . "/frontend/partials/footer.html"; ?>

</body>
</html>
