<?php
// frontend/pages/login.php
// User Login Page — uses AuthController::login API
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login | The Cafe Rio – Gulshan</title>
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
          <h3 class="fw-bold mb-4 text-center">Login</h3>

          <div id="loginAlert" class="alert d-none" role="alert"></div>

          <form id="loginForm" novalidate>
            <div class="mb-3">
              <label for="login_email" class="form-label">Email</label>
              <input type="email" class="form-control" id="login_email" placeholder="you@example.com" required>
              <div class="invalid-feedback">Valid email দিন।</div>
            </div>
            <div class="mb-3">
              <label for="login_password" class="form-label">Password</label>
              <input type="password" class="form-control" id="login_password" placeholder="••••••" required>
              <div class="invalid-feedback">পাসওয়ার্ড দিন।</div>
            </div>
            <div class="d-grid">
              <button type="submit" id="loginBtn" class="btn btn-danger">
                <span id="loginSpin" class="spinner-border spinner-border-sm me-2 d-none"></span>
                Login
              </button>
            </div>
          </form>

          <p class="text-center mt-3 mb-0 small">
            অ্যাকাউন্ট নেই? <a href="/restaurant-app/frontend/pages/register.php">Register here</a>
          </p>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . "/../partials/footer.html"; ?>

  <script>
    const el = s => document.querySelector(s);
    const saveUser = u => localStorage.setItem('cr_user', JSON.stringify(u||{}));

    const loginForm = el('#loginForm');
    const loginBtn = el('#loginBtn');
    const loginSpin = el('#loginSpin');
    const loginAlert = el('#loginAlert');

    function setLoading(b){
      loginBtn.disabled = b;
      loginSpin.classList.toggle('d-none', !b);
    }
    function showAlert(type,msg){
      loginAlert.className = `alert alert-${type}`;
      loginAlert.textContent = msg;
      loginAlert.classList.remove('d-none');
    }
    function hideAlert(){ loginAlert.classList.add('d-none'); }

    function gotoHome(){
      // Default home route
      window.location.href = '/restaurant-app/index.php';
    }

    loginForm.addEventListener('submit', async e=>{
      e.preventDefault();
      hideAlert();
      loginForm.classList.add('was-validated');

      const email = el('#login_email').value.trim();
      const password = el('#login_password').value;

      if(!email || !password) return;

      setLoading(true);
      try {
        const res = await fetch('/restaurant-app/backend/public/index.php?r=auth&a=login', {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify({email,password})
        });
        const data = await res.json().catch(()=>({}));
        if(!res.ok){
          showAlert('danger', data?.error || 'Login failed');
          return;
        }
        if(data.user){
          saveUser(data.user);
          // success → redirect param OR home
          const params = new URLSearchParams(location.search);
          const redirect = params.get('redirect');
          if (redirect) {
            location.href = redirect;
          } else {
            gotoHome(); // <— default to home page
          }
        } else {
          showAlert('danger', 'Invalid server response');
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
