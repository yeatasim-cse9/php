<?php
// frontend/pages/register.php
// User Registration Page — uses AuthController::register API
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register | The Cafe Rio – Gulshan</title>
  <style>
    .card-auth{ border:0; box-shadow:0 10px 25px rgba(0,0,0,.06); border-radius:18px }
  </style>
</head>
<body>

  <?php include __DIR__ . "/../partials/header.html"; ?>

  <main class="py-5 bg-light">
    <div class="container" style="max-width: 500px;">
      <div class="card card-auth">
        <div class="card-body p-4 p-md-5">
          <h3 class="fw-bold mb-4 text-center">Create Account</h3>

          <div id="regAlert" class="alert d-none" role="alert"></div>

          <form id="regForm" novalidate>
            <div class="mb-3">
              <label for="reg_name" class="form-label">Full Name</label>
              <input type="text" class="form-control" id="reg_name" required>
              <div class="invalid-feedback">নাম দিন।</div>
            </div>
            <div class="mb-3">
              <label for="reg_email" class="form-label">Email</label>
              <input type="email" class="form-control" id="reg_email" required>
              <div class="invalid-feedback">Valid email দিন।</div>
            </div>
            <div class="mb-3">
              <label for="reg_phone" class="form-label">Phone</label>
              <input type="tel" class="form-control" id="reg_phone" placeholder="017xxxxxxxx">
            </div>
            <div class="mb-3">
              <label for="reg_password" class="form-label">Password</label>
              <input type="password" class="form-control" id="reg_password" required>
              <div class="invalid-feedback">৬ অক্ষরের বেশি পাসওয়ার্ড দিন।</div>
            </div>
            <div class="d-grid">
              <button type="submit" id="regBtn" class="btn btn-dark">
                <span id="regSpin" class="spinner-border spinner-border-sm me-2 d-none"></span>
                Register
              </button>
            </div>
          </form>

          <p class="text-center mt-3 mb-0 small">
            Already have an account? <a href="/restaurant-app/frontend/pages/login.php">Login here</a>
          </p>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . "/../partials/footer.html"; ?>

  <script>
    const el = s => document.querySelector(s);
    const saveUser = u => localStorage.setItem('cr_user', JSON.stringify(u||{}));

    const regForm = el('#regForm');
    const regBtn = el('#regBtn');
    const regSpin = el('#regSpin');
    const regAlert = el('#regAlert');

    function setLoading(b){
      regBtn.disabled = b;
      regSpin.classList.toggle('d-none', !b);
    }
    function showAlert(type,msg){
      regAlert.className = `alert alert-${type}`;
      regAlert.textContent = msg;
      regAlert.classList.remove('d-none');
    }
    function hideAlert(){ regAlert.classList.add('d-none'); }

    regForm.addEventListener('submit', async e=>{
      e.preventDefault();
      hideAlert();
      regForm.classList.add('was-validated');

      const name = el('#reg_name').value.trim();
      const email = el('#reg_email').value.trim();
      const phone = el('#reg_phone').value.trim();
      const password = el('#reg_password').value;

      if(!name || !email || !password) return;

      setLoading(true);
      try {
        const res = await fetch('/restaurant-app/backend/public/index.php?r=auth&a=register', {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify({name,email,phone,password})
        });
        const data = await res.json().catch(()=>({}));
        if(!res.ok){
          showAlert('danger', data?.error || 'Registration failed');
          return;
        }
        if(data.user){
          saveUser(data.user);
          showAlert('success','Registration successful! Logged in.');
          const params = new URLSearchParams(location.search);
          const redirect = params.get('redirect');
          if(redirect){ location.href = redirect; }
        }
      } catch(err){
        showAlert('danger','Network error');
      } finally {
        setLoading(false);
      }
    });
  </script>
</body>
</html>
