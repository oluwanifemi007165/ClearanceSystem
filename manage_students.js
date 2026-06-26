let allStudents = [];

// ── Login ────────────────────────────────────────────────────
document.getElementById('loginForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const password = document.getElementById('adminPassword').value;

  fetch('manage_students_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'login', password })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      document.getElementById('loginCard').style.display = 'none';
      document.getElementById('mainContent').style.display = 'block';
      loadStudents();
      loadRequests();
    } else {
      showMsg(data.message, 'err');
    }
  });
});

// ── Show message ─────────────────────────────────────────────
function showMsg(text, type) {
  const box = document.getElementById('msgBox');
  box.innerHTML = `<div class="msg ${type}">${text}</div>`;
  setTimeout(() => { box.innerHTML = ''; }, 5000);
}

// ── Tab switching ───────────────────────────────────────────
function showTab(tab, btn) {
  document.getElementById('tab-single').style.display   = tab === 'single'   ? 'block' : 'none';
  document.getElementById('tab-bulk').style.display     = tab === 'bulk'     ? 'block' : 'none';
  document.getElementById('tab-list').style.display     = tab === 'list'     ? 'block' : 'none';
  document.getElementById('tab-requests').style.display = tab === 'requests' ? 'block' : 'none';
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  if (tab === 'list') loadStudents();
  if (tab === 'requests') loadRequests();
}

// ── Load pending registration requests ────────────────────────
function loadRequests() {
  fetch('manage_students_api.php?action=list_requests')
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;
      document.getElementById('requestCount').textContent = data.requests.length;
      renderRequests(data.requests);
    });
}

function renderRequests(list) {
  const tbody = document.getElementById('requestsBody');
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="7" class="empty-note">No pending requests.</td></tr>';
    return;
  }
  tbody.innerHTML = list.map(r => `
    <tr>
      <td>${r.full_name}</td>
      <td>${r.matric_number}</td>
      <td>${r.department}</td>
      <td>${r.level}</td>
      <td>${r.email}</td>
      <td style="max-width:160px;font-size:.76rem;color:#64748b">${r.reason || '—'}</td>
      <td>
        <a class="link" style="color:var(--green)" onclick="approveRequest(${r.id})">Approve</a>
        <a class="link danger" onclick="rejectRequest(${r.id})">Reject</a>
      </td>
    </tr>`).join('');
}

function approveRequest(id) {
  if (!confirm('Approve this request? The student will be added to the system and can log in immediately.')) return;

  fetch('manage_students_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'approve_request', request_id: id })
  })
  .then(r => r.json())
  .then(data => {
    showMsg(data.message, data.success ? 'ok' : 'err');
    if (data.success) { loadRequests(); loadStudents(); }
  });
}

function rejectRequest(id) {
  const note = prompt('Reason for rejecting this request (optional):');
  if (note === null) return; // cancelled

  fetch('manage_students_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'reject_request', request_id: id, note })
  })
  .then(r => r.json())
  .then(data => {
    showMsg(data.message, data.success ? 'ok' : 'err');
    if (data.success) loadRequests();
  });
}

// ── Load all students ───────────────────────────────────────
function loadStudents() {
  fetch('manage_students_api.php?action=list')
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;
      allStudents = data.students;
      document.getElementById('studentCount').textContent = allStudents.length;
      renderStudents(allStudents);
    });
}

function renderStudents(list) {
  const tbody = document.getElementById('studentsBody');
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="empty-note">No students registered yet.</td></tr>';
    return;
  }
  tbody.innerHTML = list.map(s => `
    <tr>
      <td>${s.full_name}</td>
      <td>${s.matric_number}</td>
      <td>${s.department || '—'}</td>
      <td>${s.level || '—'}</td>
      <td>${s.email || '—'}</td>
      <td>
        <a class="link" onclick='openEdit(${JSON.stringify(s)})'>Edit</a>
        <a class="link danger" onclick="deleteStudent(${s.id})">Delete</a>
      </td>
    </tr>`).join('');
}

function filterTable() {
  const q = document.getElementById('searchBox').value.toLowerCase();
  const filtered = allStudents.filter(s =>
    s.full_name.toLowerCase().includes(q) || s.matric_number.toLowerCase().includes(q)
  );
  renderStudents(filtered);
}

// ── Add single student ──────────────────────────────────────
document.getElementById('addForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  const payload = {
    action: 'add',
    full_name: formData.get('full_name'),
    matric_number: formData.get('matric_number'),
    department: formData.get('department'),
    level: formData.get('level'),
    email: formData.get('email'),
  };

  fetch('manage_students_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(r => r.json())
  .then(data => {
    showMsg(data.message, data.success ? 'ok' : 'err');
    if (data.success) {
      this.reset();
      loadStudents();
    }
  });
});

// ── Edit student ─────────────────────────────────────────────
function openEdit(s) {
  document.getElementById('editId').value     = s.id;
  document.getElementById('editName').value   = s.full_name;
  document.getElementById('editMatric').value = s.matric_number;
  document.getElementById('editDept').value   = s.department || '';
  document.getElementById('editLevel').value  = s.level || '';
  document.getElementById('editEmail').value  = s.email || '';
  document.getElementById('editOverlay').classList.add('show');
}

function closeEdit() {
  document.getElementById('editOverlay').classList.remove('show');
}

document.getElementById('editForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const payload = {
    action: 'edit',
    student_id: document.getElementById('editId').value,
    full_name: document.getElementById('editName').value,
    matric_number: document.getElementById('editMatric').value,
    department: document.getElementById('editDept').value,
    level: document.getElementById('editLevel').value,
    email: document.getElementById('editEmail').value,
  };

  fetch('manage_students_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(r => r.json())
  .then(data => {
    showMsg(data.message, data.success ? 'ok' : 'err');
    if (data.success) {
      closeEdit();
      loadStudents();
    }
  });
});

// ── Delete student (protected) 
function deleteStudent(id) {
  if (!confirm('Delete this student? This is blocked if they have payment/clearance history.')) return;

  fetch('manage_students_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'delete', student_id: id })
  })
  .then(r => r.json())
  .then(data => {
    showMsg(data.message, data.success ? 'ok' : 'err');
    if (data.success) loadStudents();
  });
}

//  Bulk CSV import 
document.getElementById('csvForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const fileInput = document.getElementById('csvFile');
  if (!fileInput.files[0]) return;

  const formData = new FormData();
  formData.append('csv_file', fileInput.files[0]);
  formData.append('action', 'bulk_import');

  fetch('manage_students_api.php', { 
    method: 'POST', 
    body: formData
 })
    .then(r => r.json())
    .then(data => {
      showMsg(data.message, data.success ? 'ok' : 'err');
      if (data.success) {
        this.reset();
        loadStudents();
      }
    });
});