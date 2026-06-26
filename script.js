let currentTab = 'student';

function handleLogin(e, type) {
  e.preventDefault();
  const err = document.getElementById('errorMsg');
  err.classList.remove('visible');

  //  STUDENT 
  if (type === 'student') {

    // CHANGED: reads from text input instead of select dropdown
    const matric = document.getElementById('matricInput').value.trim().toUpperCase();

    if (!matric) {
      err.textContent = 'Please enter your matric number.';
      err.classList.add('visible');
      return;
    }

    const btn = document.getElementById('studentBtn');
    btn.textContent = 'Logging in…';
    btn.classList.add('loading');

    fetch('login.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ role: 'student', matric_number: matric }),
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        window.location.href = data.redirect;
      } else {
        err.textContent = data.message;
        err.classList.add('visible');
        btn.textContent = 'Login as Student';
        btn.classList.remove('loading');
      }
    })
    .catch(() => {
      err.textContent = 'Server error. Please try again.';
      err.classList.add('visible');
      btn.textContent = 'Login as Student';
      btn.classList.remove('loading');
    });

  //  OFFICE 
  } else {
    const office   = document.getElementById('officeSelect').value;
    const password = document.getElementById('officePassword').value.trim();

    if (!office || !password) {
      err.textContent = 'Please select your office and enter the password.';
      err.classList.add('visible');
      return;
    }

    const btn = document.getElementById('officeBtn');
    btn.textContent = 'Logging in…';
    btn.classList.add('loading');

    fetch('login.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ role: 'office', office, password }),
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        window.location.href = data.redirect;
      } else {
        err.textContent = data.message;
        err.classList.add('visible');
        btn.textContent = 'Login as Office';
        btn.classList.remove('loading');
      }
    })
    .catch(() => {
      err.textContent = 'Server error. Please try again.';
      err.classList.add('visible');
      btn.textContent = 'Login as Office';
      btn.classList.remove('loading');
    });
  }
}

function switchTab(tab) {
  currentTab = tab;
  const toggle     = document.getElementById('tabToggle');
  const studentBtn = document.getElementById('tabStudent');
  const officeBtn  = document.getElementById('tabOffice');
  const sForm      = document.getElementById('studentForm');
  const oForm      = document.getElementById('officeForm');
  const err        = document.getElementById('errorMsg');
  err.classList.remove('visible');
  if (tab === 'student') {
    toggle.classList.remove('office');
    studentBtn.classList.add('active');
    officeBtn.classList.remove('active');
    sForm.style.display = 'block';
    oForm.style.display = 'none';
  } else {
    toggle.classList.add('office');
    officeBtn.classList.add('active');
    studentBtn.classList.remove('active');
    oForm.style.display = 'block';
    sForm.style.display = 'none';
  }
}

function togglePassword() {
  const input = document.getElementById('officePassword');
  const icon  = document.getElementById('eyeIcon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
  } else {
    input.type = 'password';
    icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  }
}