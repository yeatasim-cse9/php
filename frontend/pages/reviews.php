<?php
// frontend/pages/reviews.php
// User Reviews & Ratings — create + browse (uses backend ReviewsController + MenuController)
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reviews & Ratings | The Cafe Rio – Gulshan</title>
  <style>
    .card-elev{ border:0; box-shadow:0 10px 25px rgba(0,0,0,.06); border-radius:18px }
    .rating input{ display:none }
    .rating label{ font-size:1.6rem; cursor:pointer; transition:transform .1s }
    .rating label:hover{ transform: scale(1.08) }
    .star{ color:#ffc107 }  /* Bootstrap warning color */
    .star-muted{ color:#e9ecef }
    .muted{ color:#6c757d }
    .chip{ display:inline-block; padding:.25rem .55rem; border-radius: 16px; background:#f1f3f5; font-size:.85rem }
    .review-item{ border-bottom:1px dashed #eee; padding: .9rem 0 }
    .progress{ height:.6rem }
  </style>
</head>
<body>

  <?php include __DIR__ . "/../partials/header.html"; ?>

  <main class="py-5 bg-light">
    <div class="container">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h1 class="h3 fw-bold mb-1">Reviews & Ratings</h1>
          <div class="muted">Give feedback on dishes or the whole restaurant.</div>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="/restaurant-app/frontend/pages/order.php">
          <i class="bi bi-bag me-1"></i> Order Now
        </a>
      </div>

      <!-- Summary + Filter -->
      <div class="row g-4">
        <div class="col-lg-5">
          <div class="card card-elev h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h5 class="mb-1">Average Rating</h5>
                  <div class="display-5 fw-bold" id="avgScore">0.0</div>
                  <div id="avgStars" class="fs-5"></div>
                  <div class="muted small mt-1" id="totalReviews">—</div>
                </div>
                <div class="text-end">
                  <span class="chip" id="summaryScope">Global</span>
                </div>
              </div>

              <hr>

              <div class="mb-2 d-flex justify-content-between"><span>5 ★</span><span id="cnt5">0</span></div>
              <div class="progress mb-2"><div id="bar5" class="progress-bar bg-warning" style="width:0%"></div></div>

              <div class="mb-2 d-flex justify-content-between"><span>4 ★</span><span id="cnt4">0</span></div>
              <div class="progress mb-2"><div id="bar4" class="progress-bar bg-warning" style="width:0%"></div></div>

              <div class="mb-2 d-flex justify-content-between"><span>3 ★</span><span id="cnt3">0</span></div>
              <div class="progress mb-2"><div id="bar3" class="progress-bar bg-warning" style="width:0%"></div></div>

              <div class="mb-2 d-flex justify-content-between"><span>2 ★</span><span id="cnt2">0</span></div>
              <div class="progress mb-2"><div id="bar2" class="progress-bar bg-warning" style="width:0%"></div></div>

              <div class="mb-2 d-flex justify-content-between"><span>1 ★</span><span id="cnt1">0</span></div>
              <div class="progress"><div id="bar1" class="progress-bar bg-warning" style="width:0%"></div></div>
            </div>
          </div>
        </div>

        <div class="col-lg-7">
          <div class="card card-elev h-100">
            <div class="card-body">
              <div class="d-flex flex-wrap gap-2 align-items-end">
                <div class="flex-grow-1">
                  <label class="form-label">Filter by Item</label>
                  <select id="itemFilter" class="form-select">
                    <option value="">All (Restaurant)</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Show</label>
                  <select id="limitFilter" class="form-select">
                    <option value="10">10</option>
                    <option value="20" selected>20</option>
                    <option value="50">50</option>
                  </select>
                </div>
                <div>
                  <button id="applyFilter" class="btn btn-danger"><i class="bi bi-filter-circle me-1"></i> Apply</button>
                </div>
              </div>

              <div id="listAlert" class="alert d-none mt-3" role="alert"></div>

              <div id="reviewsList" class="mt-3">
                <!-- reviews go here -->
              </div>

              <div class="d-grid mt-3">
                <button id="loadMore" class="btn btn-outline-secondary d-none">
                  <i class="bi bi-plus-lg me-1"></i> Load more
                </button>
              </div>

            </div>
          </div>
        </div>
      </div>

      <!-- Write Review -->
      <div class="card card-elev mt-4">
        <div class="card-body">
          <h5 class="mb-3">Write a Review</h5>

          <div id="loginNotice" class="alert alert-warning d-none" role="alert">
            আপনি লগইন করেননি। <a id="loginLink" class="alert-link" href="/restaurant-app/frontend/pages/login.php">Login</a> করলে রিভিউ দিতে পারবেন।
          </div>

          <div id="writeWrap" class="row g-3">
            <div class="col-lg-4">
              <label class="form-label">Select Item (optional)</label>
              <select id="itemSelect" class="form-select">
                <option value="">Restaurant (overall)</option>
              </select>
              <div class="form-text">ডিশ নির্বাচন করলে সেই ডিশের রিভিউ হবে; না করলে রেস্টুরেন্ট-লেভেল।</div>
            </div>

            <div class="col-lg-4">
              <label class="form-label d-block">Your Rating</label>
              <div class="rating" id="ratingStars" aria-label="Choose rating 1 to 5">
                <!-- stars injected -->
              </div>
            </div>

            <div class="col-lg-4">
              <label class="form-label">Your Name</label>
              <input id="userName" class="form-control" type="text" placeholder="Auto from login" disabled>
              <div class="form-text">লগইন করা প্রোফাইল থেকে নাম নেওয়া হবে।</div>
            </div>

            <div class="col-12">
              <label class="form-label">Comment (optional)</label>
              <textarea id="comment" class="form-control" rows="3" placeholder="Share your experience..."></textarea>
            </div>

            <div class="col-12 d-flex align-items-center gap-2">
              <button id="btnSubmit" class="btn btn-danger">
                <span id="btnSpin" class="spinner-border spinner-border-sm me-2 d-none"></span>
                Submit Review
              </button>
              <div id="writeAlert" class="alert d-none mb-0 py-1 px-2" role="alert"></div>
            </div>
          </div>
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
    const elAll = s => [...document.querySelectorAll(s)];
    const loadUser = () => { try { return JSON.parse(localStorage.getItem('cr_user')||'null'); } catch { return null; } };
    const starHtml = filled => `<i class="bi bi-star${filled?'-fill':''} ${filled?'star':'star-muted'}"></i>`;
    function showAlert(box, type, msg){ box.className = `alert alert-${type}`; box.textContent = msg; box.classList.remove('d-none'); }
    function hideAlert(box){ box.classList.add('d-none'); }

    // ----------------------
    // Auth-aware UI
    // ----------------------
    function applyAuthUI(){
      const u = loadUser();
      const loginNotice = el('#loginNotice');
      const writeWrap = el('#writeWrap');
      const loginLink = el('#loginLink');
      const redirect = encodeURIComponent('/restaurant-app/frontend/pages/reviews.php');
      loginLink.href = `/restaurant-app/frontend/pages/login.php?redirect=${redirect}`;

      if (!u || !u.user_id){
        loginNotice.classList.remove('d-none');
        writeWrap.classList.add('d-none');
      } else {
        loginNotice.classList.add('d-none');
        writeWrap.classList.remove('d-none');
        el('#userName').value = `${u.name} (User #${u.user_id})`;
      }
    }

    // ----------------------
    // Load menu items for dropdowns
    // ----------------------
    async function loadMenuItems(){
      const res = await fetch('/restaurant-app/backend/public/index.php?r=menu&a=list');
      const data = await res.json().catch(()=>({}));
      if (!res.ok) throw new Error(data?.error || 'Menu load failed');

      const items = data.items || [];
      const filterSel = el('#itemFilter');
      const writeSel  = el('#itemSelect');

      items.forEach(it=>{
        const opt1 = document.createElement('option');
        opt1.value = it.item_id;
        opt1.textContent = it.name;
        filterSel.appendChild(opt1);

        const opt2 = opt1.cloneNode(true);
        writeSel.appendChild(opt2);
      });
    }

    // ----------------------
    // Summary (global or item)
    // ----------------------
    async function loadSummary(item_id){
      let url = '/restaurant-app/backend/public/index.php?r=reviews&a=summary';
      if (item_id) url += `&item_id=${encodeURIComponent(item_id)}`;
      const res = await fetch(url);
      const data = await res.json().catch(()=>({}));
      if (!res.ok) throw new Error(data?.error || 'Summary load failed');

      // scope & counts
      const scope = data.scope || 'global';
      el('#summaryScope').textContent = scope === 'item' ? (data.item?.name || 'Item') : 'Global';

      let total = 0, avg = 0, stars = {1:0,2:0,3:0,4:0,5:0};
      if (scope === 'item'){
        total = data.item?.total || 0;
        avg = data.item?.avg || 0;
        stars = data.item?.stars || stars;
      } else {
        total = data.global?.total || 0;
        avg = data.global?.avg || 0;
        stars = data.global?.stars || stars;
      }

      // set numbers
      el('#avgScore').textContent = Number(avg).toFixed(1);
      el('#avgStars').innerHTML = [1,2,3,4,5].map(i => starHtml(i <= Math.round(avg))).join(' ');
      el('#totalReviews').textContent = `${total} review${total!==1?'s':''}`;

      // bars
      const pct = v => total ? Math.round((v/total)*100) : 0;
      el('#cnt5').textContent = stars[5]||0; el('#bar5').style.width = pct(stars[5]||0)+'%';
      el('#cnt4').textContent = stars[4]||0; el('#bar4').style.width = pct(stars[4]||0)+'%';
      el('#cnt3').textContent = stars[3]||0; el('#bar3').style.width = pct(stars[3]||0)+'%';
      el('#cnt2').textContent = stars[2]||0; el('#bar2').style.width = pct(stars[2]||0)+'%';
      el('#cnt1').textContent = stars[1]||0; el('#bar1').style.width = pct(stars[1]||0)+'%';
    }

    // ----------------------
    // Reviews list (with "load more")
    // ----------------------
    let listCache = [];
    let cursor = 0;
    function renderListChunk(limit){
      const box = el('#reviewsList');
      if (cursor === 0) box.innerHTML = '';
      const next = listCache.slice(cursor, cursor + limit);
      next.forEach(r=>{
        const wrap = document.createElement('div');
        wrap.className = 'review-item';
        wrap.innerHTML = `
          <div class="d-flex justify-content-between">
            <div class="fw-semibold">${r.user?.name || 'Anonymous'}</div>
            <div class="muted small">${new Date(r.created_at).toLocaleString()}</div>
          </div>
          <div>${[1,2,3,4,5].map(i => starHtml(i<=r.rating)).join(' ')}</div>
          <div class="small muted">${r.item?.name ? 'On: '+r.item.name : 'Restaurant'}</div>
          ${r.comment ? `<div class="mt-1">${escapeHtml(r.comment)}</div>` : ''}
        `;
        box.appendChild(wrap);
      });
      cursor += next.length;
      el('#loadMore').classList.toggle('d-none', cursor >= listCache.length);
    }
    function escapeHtml(s){
      return (s || '').replace(/[&<>"']/g, t => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[t]));
    }

    async function loadReviews(item_id, limit){
      const listAlert = el('#listAlert');
      hideAlert(listAlert);

      let url = `/restaurant-app/backend/public/index.php?r=reviews&a=list&limit=${encodeURIComponent(limit)}`;
      if (item_id) url += `&item_id=${encodeURIComponent(item_id)}`;

      try{
        const res = await fetch(url);
        const data = await res.json().catch(()=>({}));
        if (!res.ok) throw new Error(data?.error || 'Failed to load reviews');

        listCache = data.items || [];
        cursor = 0;
        if (!listCache.length){
          el('#reviewsList').innerHTML = `<div class="muted">No reviews yet. Be the first to review!</div>`;
          el('#loadMore').classList.add('d-none');
        } else {
          renderListChunk(Math.min(10, listCache.length));
        }
      } catch(err){
        showAlert(listAlert, 'danger', err.message || 'Unable to load reviews');
      }
    }

    // ----------------------
    // Write Review
    // ----------------------
    let currentRating = 0;
    function renderRatingStars(){
      const box = el('#ratingStars');
      box.innerHTML = '';
      for (let i=1;i<=5;i++){
        const id = `r${i}`;
        const input = document.createElement('input');
        input.type = 'radio'; input.name = 'rating'; input.id = id; input.value = i;
        const label = document.createElement('label');
        label.setAttribute('for', id);
        label.innerHTML = starHtml(false);
        input.addEventListener('change', ()=> setRating(i));
        label.addEventListener('mouseenter', ()=> preview(i));
        label.addEventListener('mouseleave', ()=> preview(currentRating));
        box.appendChild(input);
        box.appendChild(label);
      }
    }
    function setRating(n){
      currentRating = n;
      const labels = elAll('#ratingStars label');
      labels.forEach((lb, idx)=>{
        const i = idx+1;
        lb.innerHTML = starHtml(i <= currentRating);
      });
    }
    function preview(n){
      const labels = elAll('#ratingStars label');
      labels.forEach((lb, idx)=>{
        const i = idx+1;
        lb.innerHTML = starHtml(i <= n);
      });
    }

    async function submitReview(){
      const u = loadUser();
      const writeAlert = el('#writeAlert');
      hideAlert(writeAlert);

      if (!u || !u.user_id){
        showAlert(writeAlert, 'warning', 'Please login first.');
        return;
      }
      if (!currentRating){
        showAlert(writeAlert, 'warning', 'Select a star rating.');
        return;
      }

      const item_id = el('#itemSelect').value || null;
      const comment = el('#comment').value.trim();

      el('#btnSubmit').disabled = true;
      el('#btnSpin').classList.remove('d-none');

      try{
        const res = await fetch('/restaurant-app/backend/public/index.php?r=reviews&a=create', {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify({ user_id: u.user_id, item_id: item_id ? Number(item_id) : null, rating: currentRating, comment })
        });
        const data = await res.json().catch(()=>({}));
        if (!res.ok){
          showAlert(writeAlert, 'danger', data?.error || 'Failed to submit review');
          return;
        }

        showAlert(writeAlert, 'success', 'Thanks! Your review has been added.');
        // refresh summary + list (respect current filter/limit)
        const fItem = el('#itemFilter').value || '';
        const lim = Number(el('#limitFilter').value || 20);
        await Promise.all([loadSummary(fItem), loadReviews(fItem, lim)]);
        // reset rating/comment
        setRating(0); el('#comment').value = '';
      } catch(err){
        showAlert(writeAlert, 'danger', 'Network error. Try again.');
      } finally {
        el('#btnSubmit').disabled = false;
        el('#btnSpin').classList.add('d-none');
      }
    }

    // ----------------------
    // Events
    // ----------------------
    el('#applyFilter').addEventListener('click', async ()=>{
      const item = el('#itemFilter').value || '';
      const lim = Number(el('#limitFilter').value || 20);
      await Promise.all([loadSummary(item), loadReviews(item, lim)]);
    });
    el('#loadMore').addEventListener('click', ()=> renderListChunk(10));
    el('#btnSubmit').addEventListener('click', submitReview);

    // Init
    window.addEventListener('load', async ()=>{
      applyAuthUI();
      renderRatingStars();
      try{
        await loadMenuItems();
      }catch(e){ /* ignore menu load fail */ }

      // default: global view
      await Promise.all([loadSummary(null), loadReviews(null, 20)]);
    });
  </script>
</body>
</html>
