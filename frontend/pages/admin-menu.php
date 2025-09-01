<?php
// frontend/pages/admin-menu.php
// Admin Menu Management UI — list/search/filter + create/edit/delete + status toggle + image upload
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin • Menu Management | The Cafe Rio – Gulshan</title>
  <style>
    .card-elev{ border:0; box-shadow:0 10px 25px rgba(0,0,0,.06); border-radius:18px }
    .muted{ color:#6c757d }
    .status-pill{ padding:.28rem .6rem; border-radius:18px; font-size:.8rem; display:inline-block }
    .st-available{ background:rgba(25,135,84,.15); color:#198754 }
    .st-unavailable{ background:rgba(220,53,69,.12); color:#dc3545 }
    .thumb{ width:64px; height:48px; object-fit:cover; border-radius:8px; border:1px solid #eee }
    .table thead th{ white-space:nowrap }
    .img-drop{ border:2px dashed #ced4da; border-radius:12px; padding:14px; text-align:center; cursor:pointer; background:#fafbfc }
    .img-drop.drag{ background:#f1f8ff; border-color:#9ec5fe }
  </style>
</head>
<body>

  <?php include __DIR__ . "/../partials/header.html"; ?>

  <main class="py-5 bg-light">
    <div class="container">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h1 class="h3 fw-bold mb-1">Menu Management</h1>
          <div class="muted">Create, edit, disable/enable dishes and upload images.</div>
        </div>
        <div class="d-flex gap-2">
          <a href="/restaurant-app/frontend/pages/admin-dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Dashboard
          </a>
          <a href="/restaurant-app/frontend/pages/admin-orders.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-bag me-1"></i> Orders
          </a>
          <a href="/restaurant-app/frontend/pages/admin-reservations.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-calendar2-check me-1"></i> Reservations
          </a>
        </div>
      </div>

      <!-- Filters Toolbar -->
      <div class="card card-elev mb-4">
        <div class="card-body">
          <div class="row g-2 align-items-end">
            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select id="fStatus" class="form-select">
                <option value="">All</option>
                <option value="available">Available</option>
                <option value="unavailable">Unavailable</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Category</label>
              <input id="fCategory" class="form-control" placeholder="e.g. Dessert">
            </div>
            <div class="col-md-4">
              <label class="form-label">Search</label>
              <input id="fQ" class="form-control" placeholder="Type name/description">
            </div>
            <div class="col-md-2 d-flex gap-2">
              <button id="btnApply" class="btn btn-danger flex-fill"><i class="bi bi-filter-circle me-1"></i> Apply</button>
              <button id="btnReset" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></button>
            </div>
          </div>
        </div>
      </div>

      <!-- Alerts -->
      <div id="alertBox" class="alert d-none" role="alert"></div>

      <!-- Table + Create -->
      <div class="card card-elev">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Menu Items</h5>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#itemModal" id="btnNew">
              <i class="bi bi-plus-lg me-1"></i> New Item
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Image</th>
                  <th>Name</th>
                  <th>Category</th>
                  <th class="text-end">Price</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody id="tblMenu">
                <tr><td colspan="7" class="text-center muted py-4">Loading…</td></tr>
              </tbody>
            </table>
          </div>

        </div>
      </div>

    </div>
  </main>

  <!-- Modal: Create/Edit -->
  <div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">New Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="modalAlert" class="alert d-none" role="alert"></div>

          <input type="hidden" id="m_item_id">

          <div class="mb-3">
            <label class="form-label">Name</label>
            <input id="m_name" class="form-control" placeholder="Dish name">
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea id="m_description" class="form-control" rows="3" placeholder="Short description"></textarea>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Price (৳)</label>
              <input id="m_price" type="number" step="0.01" min="1" class="form-control" placeholder="e.g. 499">
            </div>
            <div class="col-md-6">
              <label class="form-label">Category</label>
              <input id="m_category" class="form-control" placeholder="Main Course / Dessert / Drinks">
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select id="m_status" class="form-select">
                <option value="available" selected>Available</option>
                <option value="unavailable">Unavailable</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Image filename</label>
              <input id="m_image" class="form-control" placeholder="auto-filled after upload">
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label">Upload Image</label>
            <div id="dropZone" class="img-drop">
              <div class="small muted"><i class="bi bi-image me-1"></i> Click or drop an image (JPG/PNG/GIF/WEBP ≤ 5MB)</div>
              <input id="fileInput" type="file" accept="image/*" class="d-none">
            </div>
            <div class="mt-2 d-flex align-items-center gap-2">
              <img id="preview" class="thumb d-none" alt="preview">
              <a id="previewLink" href="#" target="_blank" class="small d-none">Open</a>
              <div id="uploadState" class="small muted"></div>
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          <button class="btn btn-danger" id="btnSave">
            <span id="btnSpin" class="spinner-border spinner-border-sm me-2 d-none"></span>
            Save
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <?php include __DIR__ . "/../partials/footer.html"; ?>

  <script>
    // -----------------------
    // Helpers
    // -----------------------
    const el = s => document.querySelector(s);
    const elAll = s => [...document.querySelectorAll(s)];
    function showAlert(box, type, msg){ box.className = `alert alert-${type}`; box.textContent = msg; box.classList.remove('d-none'); }
    function hideAlert(box){ box.classList.add('d-none'); }
    const bd = v => `৳${Number(v||0).toFixed(0)}`;

    function pill(status){
      const cls = status === 'available' ? 'st-available' : 'st-unavailable';
      return `<span class="status-pill ${cls} text-capitalize">${status}</span>`;
    }

    // -----------------------
    // State
    // -----------------------
    let rows = [];
    let editingId = null;

    // -----------------------
    // API
    // -----------------------
    async function listMenu(){
      const params = [];
      const s = el('#fStatus').value;
      const c = el('#fCategory').value.trim();
      const q = el('#fQ').value.trim();
      if (s) params.push(`status=${encodeURIComponent(s)}`);
      if (c) params.push(`category=${encodeURIComponent(c)}`);
      if (q) params.push(`q=${encodeURIComponent(q)}`);
      let url = '/restaurant-app/backend/public/index.php?r=menu&a=list';
      if (params.length) url += '&' + params.join('&');

      const res = await fetch(url);
      const data = await res.json().catch(()=>({}));
      if (!res.ok) throw new Error(data?.error || 'Failed to load menu');
      return data.items || [];
    }

    async function uploadImage(file){
      const fd = new FormData();
      fd.append('uploadfile', file);
      const res = await fetch('/restaurant-app/backend/public/index.php?r=menu&a=upload_image', { method:'POST', body: fd });
      const data = await res.json().catch(()=>({}));
      if (!res.ok) throw new Error(data?.error || 'Upload failed');
      return data;
    }

    async function createItem(payload){
      const res = await fetch('/restaurant-app/backend/public/index.php?r=menu&a=create', {
        method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
      });
      const data = await res.json().catch(()=>({}));
      if (!res.ok) throw new Error(data?.error || 'Create failed');
      return data;
    }

    async function updateItem(payload){
      const res = await fetch('/restaurant-app/backend/public/index.php?r=menu&a=update', {
        method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
      });
      const data = await res.json().catch(()=>({}));
      if (!res.ok) throw new Error(data?.error || 'Update failed');
      return data;
    }

    async function deleteItem(id){
      const res = await fetch('/restaurant-app/backend/public/index.php?r=menu&a=delete', {
        method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ item_id:id })
      });
      const data = await res.json().catch(()=>({}));
      if (!res.ok) throw new Error(data?.error || 'Delete failed');
      return data;
    }

    async function toggleStatus(id, status){
      const res = await fetch('/restaurant-app/backend/public/index.php?r=menu&a=toggle_status', {
        method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ item_id:id, status })
      });
      const data = await res.json().catch(()=>({}));
      if (!res.ok) throw new Error(data?.error || 'Status update failed');
      return data;
    }

    // -----------------------
    // UI render
    // -----------------------
    function renderTable(){
      const tbody = el('#tblMenu');
      tbody.innerHTML = '';
      if (!rows.length){
        tbody.innerHTML = `<tr><td colspan="7" class="text-center muted py-4">No items found.</td></tr>`;
        return;
      }
      rows.forEach(r=>{
        const tr = document.createElement('tr');
        const imgUrl = r.image ? `/restaurant-app/frontend/assets/images/${r.image}` : '';
        tr.innerHTML = `
          <td>#${r.item_id}</td>
          <td>${imgUrl ? `<img class="thumb" src="${imgUrl}" alt="${escapeHtml(r.name)}">` : `<span class="muted small">No image</span>`}</td>
          <td>
            <div class="fw-semibold">${escapeHtml(r.name)}</div>
            <div class="small muted">${escapeHtml(r.description || '')}</div>
          </td>
          <td>${escapeHtml(r.category || '')}</td>
          <td class="text-end fw-semibold">${bd(r.price)}</td>
          <td>${pill(r.status)}</td>
          <td class="text-end">
            <div class="btn-group btn-group-sm">
              ${r.status==='available'
                ? `<button class="btn btn-outline-warning" data-action="status" data-id="${r.item_id}" data-to="unavailable" title="Make Unavailable"><i class="bi bi-eye-slash"></i></button>`
                : `<button class="btn btn-outline-success" data-action="status" data-id="${r.item_id}" data-to="available" title="Make Available"><i class="bi bi-eye"></i></button>`
              }
              <button class="btn btn-outline-secondary" data-action="edit" data-id="${r.item_id}" title="Edit">
                <i class="bi bi-pencil"></i>
              </button>
              <button class="btn btn-outline-danger" data-action="delete" data-id="${r.item_id}" title="Delete">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </td>
        `;
        tbody.appendChild(tr);
      });

      // bind actions
      tbody.querySelectorAll('[data-action="edit"]').forEach(btn=>{
        btn.addEventListener('click', ()=> openEdit(parseInt(btn.dataset.id,10)));
      });
      tbody.querySelectorAll('[data-action="delete"]').forEach(btn=>{
        btn.addEventListener('click', ()=> {
          const id = parseInt(btn.dataset.id,10);
          if (confirm('Delete this item?')) handleDelete(id);
        });
      });
      tbody.querySelectorAll('[data-action="status"]').forEach(btn=>{
        btn.addEventListener('click', ()=> {
          const id = parseInt(btn.dataset.id,10);
          const to = btn.dataset.to;
          handleToggle(id, to);
        });
      });
    }

    function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, t=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[t])); }

    // -----------------------
    // Load & Filters
    // -----------------------
    async function loadMenu(){
      hideAlert(el('#alertBox'));
      el('#tblMenu').innerHTML = `<tr><td colspan="7" class="text-center muted py-4">Loading…</td></tr>`;
      try{
        rows = await listMenu();
        renderTable();
      } catch(err){
        showAlert(el('#alertBox'), 'danger', err.message || 'Unable to load menu');
      }
    }

    el('#btnApply').addEventListener('click', loadMenu);
    el('#btnReset').addEventListener('click', ()=>{
      el('#fStatus').value = '';
      el('#fCategory').value = '';
      el('#fQ').value = '';
      loadMenu();
    });

    // -----------------------
    // New / Edit Modal
    // -----------------------
    const modalEl = document.getElementById('itemModal');
    const modal = new bootstrap.Modal(modalEl);

    el('#btnNew').addEventListener('click', ()=>{
      editingId = null;
      resetModal();
      el('#modalTitle').textContent = 'New Item';
    });

    function fillModal(r){
      el('#m_item_id').value = r.item_id;
      el('#m_name').value = r.name || '';
      el('#m_description').value = r.description || '';
      el('#m_price').value = r.price || '';
      el('#m_category').value = r.category || '';
      el('#m_status').value = r.status || 'available';
      el('#m_image').value = r.image || '';

      if (r.image){
        const url = `/restaurant-app/frontend/assets/images/${r.image}`;
        el('#preview').src = url; el('#preview').classList.remove('d-none');
        el('#previewLink').href = url; el('#previewLink').classList.remove('d-none');
      } else {
        el('#preview').classList.add('d-none');
        el('#previewLink').classList.add('d-none');
      }
    }

    function resetModal(){
      hideAlert(el('#modalAlert'));
      el('#m_item_id').value = '';
      el('#m_name').value = '';
      el('#m_description').value = '';
      el('#m_price').value = '';
      el('#m_category').value = '';
      el('#m_status').value = 'available';
      el('#m_image').value = '';
      el('#preview').classList.add('d-none');
      el('#previewLink').classList.add('d-none');
      el('#uploadState').textContent = '';
    }

    function openEdit(id){
      const r = rows.find(x => x.item_id === id);
      if (!r) return;
      editingId = id;
      resetModal();
      el('#modalTitle').textContent = `Edit: #${id}`;
      fillModal(r);
      modal.show();
    }

    function setSaving(b){
      el('#btnSave').disabled = b;
      el('#btnSpin').classList.toggle('d-none', !b);
    }

    el('#btnSave').addEventListener('click', async ()=>{
      hideAlert(el('#modalAlert'));
      const name = el('#m_name').value.trim();
      const price = parseFloat(el('#m_price').value);
      if (!name || !price || price <= 0){
        showAlert(el('#modalAlert'), 'warning', 'Name ও বৈধ Price দিন।');
        return;
      }

      const payload = {
        name,
        description: el('#m_description').value.trim(),
        price,
        category: el('#m_category').value.trim(),
        image: el('#m_image').value.trim(),
        status: el('#m_status').value
      };
      try{
        setSaving(true);
        if (editingId){
          payload.item_id = editingId;
          await updateItem(payload);
        } else {
          await createItem(payload);
        }
        modal.hide();
        await loadMenu();
      } catch(err){
        showAlert(el('#modalAlert'), 'danger', err.message || 'Save failed');
      } finally {
        setSaving(false);
      }
    });

    // -----------------------
    // Delete & Toggle
    // -----------------------
    async function handleDelete(id){
      hideAlert(el('#alertBox'));
      try{
        await deleteItem(id);
        await loadMenu();
      } catch(err){
        showAlert(el('#alertBox'), 'danger', err.message || 'Delete failed');
      }
    }
    async function handleToggle(id, to){
      hideAlert(el('#alertBox'));
      try{
        await toggleStatus(id, to);
        // mutate local quickly
        const row = rows.find(x => x.item_id === id);
        if (row) row.status = to;
        renderTable();
      } catch(err){
        showAlert(el('#alertBox'), 'danger', err.message || 'Status update failed');
      }
    }

    // -----------------------
    // Image upload (click or drop)
    // -----------------------
    const dropZone = el('#dropZone');
    const fileInput = el('#fileInput');

    dropZone.addEventListener('click', ()=> fileInput.click());
    ['dragenter','dragover'].forEach(evt => dropZone.addEventListener(evt, e=>{ e.preventDefault(); dropZone.classList.add('drag'); }));
    ['dragleave','drop'].forEach(evt => dropZone.addEventListener(evt, e=>{ e.preventDefault(); dropZone.classList.remove('drag'); }));

    dropZone.addEventListener('drop', e=>{
      const f = e.dataTransfer.files?.[0];
      if (f) handleUpload(f);
    });
    fileInput.addEventListener('change', ()=> {
      const f = fileInput.files?.[0];
      if (f) handleUpload(f);
    });

    async function handleUpload(file){
      const state = el('#uploadState');
      state.textContent = 'Uploading…';
      try{
        const data = await uploadImage(file);
        el('#m_image').value = data.filename;
        const url = data.url;
        el('#preview').src = url; el('#preview').classList.remove('d-none');
        el('#previewLink').href = url; el('#previewLink').classList.remove('d-none');
        state.textContent = 'Uploaded ✓';
      } catch(err){
        state.textContent = '';
        showAlert(el('#modalAlert'),'danger', err.message || 'Upload failed');
      }
    }

    // -----------------------
    // Init
    // -----------------------
    window.addEventListener('load', loadMenu);
  </script>
</body>
</html>
