<?php
// frontend/pages/reservations.php
// Table Reservation (Frontend) – Bootstrap form + AJAX to backend/public API
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reserve a Table | The Cafe Rio – Gulshan</title>
</head>
<body>

  <!-- Header -->
  <?php include __DIR__ . "/../partials/header.html"; ?>

  <section id="reservations" class="py-5 bg-light">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="text-center mb-4">
            <h1 class="fw-bold">Reserve Your Table</h1>
            <p class="text-muted mb-0">আজ থেকে ৩০ দিনের মধ্যে যে কোনো তারিখ ও সময় সিলেক্ট করে বুক করতে পারবেন।</p>
          </div>

          <div class="card shadow-sm">
            <div class="card-body p-4">

              <!-- Login notice (shown only if not logged in) -->
              <div id="loginNotice" class="alert alert-warning d-none" role="alert">
                আপনি লগইন করেননি। <a class="alert-link" id="loginLink" href="/restaurant-app/frontend/pages/login.php">Login</a> করলে আপনার তথ্য অটো–ফিল হবে।
              </div>

              <!-- Alert placeholders -->
              <div id="alertBox" class="alert d-none" role="alert"></div>

              <form id="reservationForm" novalidate>
                <div class="row g-3">
                  <!-- User ID (auto-filled & hidden when logged in) -->
                  <div class="col-md-4" id="userIdWrap">
                    <label for="user_id" class="form-label">User ID <span class="text-danger">*</span></label>
                    <input type="number" inputmode="numeric" class="form-control" id="user_id" name="user_id" min="1" placeholder="e.g. 2" required>
                    <div class="form-text">লগইন করলে এটি অটো-ফিল ও হাইড হবে।</div>
                    <div class="invalid-feedback">Valid User ID দিন।</div>
                  </div>

                  <div class="col-md-4">
                    <label for="reservation_date" class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="reservation_date" name="reservation_date" required>
                    <div class="invalid-feedback">তারিখ সিলেক্ট করুন (আজ থেকে ৩০ দিনের মধ্যে)।</div>
                  </div>

                  <div class="col-md-4">
                    <label for="reservation_time" class="form-label">Time <span class="text-danger">*</span></label>
                    <input type="time" class="form-control" id="reservation_time" name="reservation_time" required>
                    <div class="invalid-feedback">সময় সিলেক্ট করুন (HH:MM)।</div>
                  </div>

                  <div class="col-md-4">
                    <label for="people_count" class="form-label">People <span class="text-danger">*</span></label>
                    <input type="number" inputmode="numeric" class="form-control" id="people_count" name="people_count" min="1" value="2" required>
                    <div class="invalid-feedback">লোকসংখ্যা ১ বা তার বেশি দিন।</div>
                  </div>

                  <div class="col-md-4">
                    <label for="table_type" class="form-label">Table Type</label>
                    <select id="table_type" name="table_type" class="form-select">
                      <option value="family" selected>Family</option>
                      <option value="couple">Couple</option>
                      <option value="window">Window</option>
                    </select>
                  </div>

                  <div class="col-12">
                    <label for="special_request" class="form-label">Special Request</label>
                    <textarea id="special_request" name="special_request" class="form-control" rows="3" placeholder="Any dietary preference, birthday setup, window side etc."></textarea>
                  </div>
                </div>

                <div class="d-flex align-items-center gap-3 mt-4">
                  <button type="submit" class="btn btn-danger px-4" id="submitBtn">
                    <span class="spinner-border spinner-border-sm me-2 d-none" id="btnSpinner" aria-hidden="true"></span>
                    Book Now
                  </button>
                  <button type="reset" class="btn btn-outline-secondary">Reset</button>
                </div>
              </form>

              <hr class="my-4">

              <p class="mb-0 text-muted">
                নোট: বুকিং সাবমিট করার পর স্ট্যাটাস থাকবে <b>pending</b>। কনফার্মেশনের জন্য SMS/ফোনে জানানো হবে।
              </p>
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>

  <!-- Success Modal -->
  <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0">
        <div class="modal-header border-0">
          <h5 class="modal-title" id="successLabel"><i class="bi bi-check2-circle me-2"></i>Reservation Created</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="mb-1">আপনার রিজার্ভেশন রিকোয়েস্ট রিসিভ করা হয়েছে।</p>
          <div id="successDetails" class="small text-muted"></div>
        </div>
        <div class="modal-footer border-0">
          <a href="/restaurant-app/index.php#reservations" class="btn btn-danger">OK</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <?php include __DIR__ . "/../partials/footer.html"; ?>

  <script>
    // ---------- Auth integration ----------
    const loadUser = () => { try { return JSON.parse(localStorage.getItem('cr_user')||'null'); } catch { return null; } };
    function applyUser(){
      const u = loadUser();
      const userWrap = document.getElementById('userIdWrap');
      const userInput = document.getElementById('user_id');
      const loginNotice = document.getElementById('loginNotice');
      const loginLink = document.getElementById('loginLink');

      // set login redirect
      const redirectUrl = encodeURIComponent('/restaurant-app/frontend/pages/reservations.php');
      loginLink.href = `/restaurant-app/frontend/pages/login.php?redirect=${redirectUrl}`;

      if (u && u.user_id){
        userInput.value = u.user_id;
        // hide the field when logged in
        userWrap.classList.add('d-none');
        loginNotice.classList.add('d-none');
      } else {
        // show field + login notice
        userWrap.classList.remove('d-none');
        loginNotice.classList.remove('d-none');
      }
    }

    // ---------- Date limits: today .. today+30 ----------
    (function setDateLimits(){
      const d = new Date();
      const pad = n => String(n).padStart(2,'0');

      const today = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
      const max = new Date(d);
      max.setDate(max.getDate() + 30);
      const maxStr = `${max.getFullYear()}-${pad(max.getMonth()+1)}-${pad(max.getDate())}`;

      const dateInput = document.getElementById('reservation_date');
      dateInput.min = today;
      dateInput.max = maxStr;
      dateInput.value = today;
    })();

    // ---------- Form submit ----------
    const form = document.getElementById('reservationForm');
    const alertBox = document.getElementById('alertBox');
    const submitBtn = document.getElementById('submitBtn');
    const btnSpinner = document.getElementById('btnSpinner');

    function showAlert(type, msg){
      alertBox.className = `alert alert-${type}`;
      alertBox.textContent = msg;
      alertBox.classList.remove('d-none');
    }
    function hideAlert(){ alertBox.classList.add('d-none'); }
    function setLoading(loading){
      submitBtn.disabled = loading;
      btnSpinner.classList.toggle('d-none', !loading);
    }

    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      hideAlert();

      // HTML5 validity
      if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
      }

      const payload = {
        user_id: parseInt(document.getElementById('user_id').value, 10),
        reservation_date: document.getElementById('reservation_date').value,
        reservation_time: document.getElementById('reservation_time').value,
        people_count: parseInt(document.getElementById('people_count').value, 10),
        table_type: document.getElementById('table_type').value,
        special_request: document.getElementById('special_request').value.trim()
      };

      if (!payload.user_id || payload.user_id < 1) {
        showAlert('warning', 'Valid User ID দিন বা আগে লগইন করুন।');
        return;
      }

      setLoading(true);

      try {
        const res = await fetch('/restaurant-app/backend/public/index.php?r=reservations&a=create', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });

        const data = await res.json().catch(()=> ({}));

        if (!res.ok) {
          const msg = data?.error || 'Something went wrong while creating reservation';
          showAlert('danger', msg);
        } else {
          // Success modal
          document.getElementById('successDetails').innerHTML =
            `<div>Reservation ID: <b>${data.reservation?.reservation_id ?? '—'}</b></div>
             <div>Date: <b>${payload.reservation_date}</b> &nbsp; Time: <b>${payload.reservation_time}</b></div>
             <div>People: <b>${payload.people_count}</b> &nbsp; Table: <b>${payload.table_type}</b></div>
             <div class="mt-2 text-success"><i class="bi bi-check2"></i> Status: pending</div>`;

          const modal = new bootstrap.Modal(document.getElementById('successModal'));
          modal.show();

          form.reset();
          // Reset default/limits again
          document.getElementById('people_count').value = 2;
          (function resetDate(){
            const d = new Date();
            const pad = n => String(n).padStart(2,'0');
            const today = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
            document.getElementById('reservation_date').value = today;
          })();
          applyUser(); // keep user field state consistent after reset
        }
      } catch (err) {
        showAlert('danger', 'Network error. Please try again.');
      } finally {
        setLoading(false);
      }
    });

    // Init
    window.addEventListener('load', applyUser);
  </script>
</body>
</html>
