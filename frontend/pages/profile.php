<?php
// frontend/pages/profile.php
// User Profile — view/update name & phone, change password
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>My Profile | The Cafe Rio – Gulshan</title>
  <style>
    .card-elev{ border:0; box-shadow:0 10px 25px rgba(0,0,0,.06); border-radius:18px }
    .muted{ color:#6c757d }
  </style>
</head>
<body>

  <?php include __DIR__ . "/../partials/header.html"; ?>

  <main class="py-5 bg-light">
    <div class="container" style="max-width: 820px;">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h1 class="h3 fw-bold mb-1">My Profile</h1>
          <div class="muted">Update your name, phone and password.</div>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="/restaurant-app/index.php">
          <i class="bi bi-house-door me-1"></i> Home
        </a>
      </div>

      <!-- Alerts -->
      <div id="pageAlert" class="alert d-none" role="alert"></div>

      <div class="row g-4">
        <!-- Profile info -->
        <div class="col-lg-6">
          <div class="card card-elev h-100">
            <div class="card-body">
              <h5 class="mb-3">Basic Info</h5>
              <div id="infoAlert" class="alert d-none" role="alert"></div>

              <div class="mb-3">
                <label class="form-label">Name</label>
                <input id="name" class="form-control" placeholder="Your name">
              </div>

              <div class="mb-3">
                <label class="form-label">Email (read-only)</label>
                <input id="email" class="form-control" disabled>
                <div class="form-text">Your login email cannot be changed.</div>
              </div>

              <div class="mb-3">
                <label class="form-label">Phone</label>
                <input id="phone" class="form-control" placeholder="01XXXXXXXXX">
              </div>

              <div class="d-grid">
                <button id="btnSaveInfo" class="btn btn-danger">
                  <span id="spinInfo" class="spinner-border spinner-border-sm me-2 d-none"></span>
                  Save Changes
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Password -->
        <div class="col-lg-6">
          <div class="card card-elev h-100">
            <div class="card-body">
              <h5 class="mb-3">Change Password</h5>
              <div id="passAlert" class="alert d-none" role="alert"></div>

              <div class="mb-3">
                <label class="form-label">Current Password</label>
                <input id="curPass" type="password" class="form-control" placeholder="Current password">
              </div>
              <div class="mb-3">
                <label class="form-label">New Password</label>
                <input id="newPass" type="password" class="form-control" placeholder="New password (min 6 chars)">
              </div>
              <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input id="confPass" type="password" class="form-control" placeholder="Retype new password">
              </div>

              <div class="d-grid">
                <button id="btnSavePass" class="btn btn-outline-secondary">
                  <span id="spinPass" class="spinner-border spinner-border-sm me-2 d-none"></span>
                  Update Password
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Danger zone -->
      <div class="card card-elev mt-4">
        <div class="card-body">
          <h5 class="mb-2">Danger Zone</h5>
          <div class="muted small mb-2">Logout from this device.</div>
          <button id="btnLogoutHere" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i> Logout</button>
        </div>
      </div>

    </div>
  </main>

  <?php include __DIR__ . "/../partials/footer.html"; ?>

  <script>
    // ----------------------
    // Helpers
    // ----------------------
    const el = s => document.querySelector(s);
    function showAlert(box, type, msg){ box.className = `alert alert-${type}`; box.textContent = msg; box.classList.remove('d-none'); }
    function hideAlert(box){ box.classList.add('d-none'); }
    const getUserLS = () => { try { return JSON.parse(localStorage.getItem('cr_user')||'null'); } catch { return null; } };
    const saveUserLS = (u)=> localStorage.setItem('cr_user', JSON.stringify(u||{}));

    function guardAuth(){
      const u = getUserLS();
      if (!u || !u.user_id){
        // redirect to login → back to profile
        const red = encodeURIComponent('/restaurant-app/frontend/pages/profile.php');
        window.location.href = `/restaurant-app/frontend/pages/login.php?redirect=${red}`;
        return false;
      }
      return true;
    }

    // ----------------------
    // Load current profile (server-authoritative)
    // ----------------------
    async function loadProfile(){
      const pageAlert = el('#pageAlert');
      hideAlert(pageAlert);
      const u = getUserLS();
      try{
        // Try to fetch fresh info (if endpoint exists); fallback to LS
        let fresh = null;
        try{
          const res = await fetch(`/restaurant-app/backend/public/index.php?r=users&a=get&user_id=${encodeURIComponent(u.user_id)}`);
          if (res.ok){
            const data = await res.json();
            fresh = data?.user || null;
          }
        }catch(_){/* ignore */}
        const src = fresh || u;

        el('#name').value = src.name || '';
        el('#email').value = src.email || '';
        el('#phone').value = src.phone || '';
      }catch(err){
        showAlert(pageAlert, 'danger', 'Failed to load profile');
      }
    }

    // ----------------------
    // Save basic info (name, phone)
    // ----------------------
    async function saveInfo(){
      const infoAlert = el('#infoAlert');
      hideAlert(infoAlert);

      const u = getUserLS();
      const name = el('#name').value.trim();
      const phone = el('#phone').value.trim();

      if (!name){
        showAlert(infoAlert, 'warning', 'Name দিন।');
        return;
      }

      el('#spinInfo').classList.remove('d-none');
      el('#btnSaveInfo').disabled = true;
      try{
        // Preferred endpoint (UsersController)
        let ok = false, updatedUser = null;
        try{
          const res = await fetch('/restaurant-app/backend/public/index.php?r=users&a=update', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ user_id: u.user_id, name, phone })
          });
          const data = await res.json().catch(()=>({}));
          if (res.ok){
            ok = true;
            updatedUser = data?.user || { ...u, name, phone };
          }
        }catch(_){/* ignore */}

        // Fallback: if API not available, update LS only (temporary)
        if (!ok){
          updatedUser = { ...u, name, phone };
        }

        // Save to localStorage for header etc.
        saveUserLS(updatedUser);
        showAlert(infoAlert, 'success', 'Profile updated.');
      } catch(err){
        showAlert(infoAlert, 'danger', 'Update failed.');
      } finally {
        el('#spinInfo').classList.add('d-none');
        el('#btnSaveInfo').disabled = false;
      }
    }

    // ----------------------
    // Change password
    // ----------------------
    async function savePassword(){
      const passAlert = el('#passAlert');
      hideAlert(passAlert);

      const u = getUserLS();
      const cur = el('#curPass').value;
      const nw  = el('#newPass').value;
      const cf  = el('#confPass').value;

      if (!cur || !nw || !cf){
        showAlert(passAlert, 'warning', 'সব পাসওয়ার্ড ফিল্ড পূরণ করুন।');
        return;
      }
      if (nw.length < 6){
        showAlert(passAlert, 'warning', 'নতুন পাসওয়ার্ড কমপক্ষে ৬ অক্ষর হতে হবে।');
        return;
      }
      if (nw !== cf){
        showAlert(passAlert, 'warning', 'নতুন পাসওয়ার্ড এবং নিশ্চিতকরণ মিলছে না।');
        return;
      }

      el('#spinPass').classList.remove('d-none');
      el('#btnSavePass').disabled = true;
      try{
        // Preferred endpoint (UsersController)
        const res = await fetch('/restaurant-app/backend/public/index.php?r=users&a=change_password', {
          method:'POST', headers:{'Content-Type':'application/json'},
          body: JSON.stringify({ user_id: u.user_id, current_password: cur, new_password: nw })
        });
        const data = await res.json().catch(()=>({}));

        if (!res.ok){
          showAlert(passAlert, 'danger', data?.error || 'Password update failed.');
          return;
        }

        showAlert(passAlert, 'success', 'Password updated successfully.');
        el('#curPass').value = '';
        el('#newPass').value = '';
        el('#confPass').value = '';
      } catch(err){
        showAlert(passAlert, 'danger', 'Network error.');
      } finally {
        el('#spinPass').classList.add('d-none');
        el('#btnSavePass').disabled = false;
      }
    }

    // ----------------------
    // Logout (this device)
    // ----------------------
    function logoutHere(){
      localStorage.removeItem('cr_user');
      window.location.href = '/restaurant-app/index.php';
    }

    // ----------------------
    // Init
    // ----------------------
    window.addEventListener('load', async ()=>{
      if (!guardAuth()) return;
      await loadProfile();

      el('#btnSaveInfo').addEventListener('click', saveInfo);
      el('#btnSavePass').addEventListener('click', savePassword);
      el('#btnLogoutHere').addEventListener('click', logoutHere);
    });
  </script>
</body>
</html>
