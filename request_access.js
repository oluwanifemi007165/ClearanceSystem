document.getElementById('requestForm').addEventListener('submit', function(e) {
  e.preventDefault();

  const err = document.getElementById('errMsg');
  err.style.display = 'none';

  const payload = {
    full_name:     document.getElementById('fullName').value.trim(),
    matric_number: document.getElementById('matricNumber').value.trim().toUpperCase(),
    department:    document.getElementById('department').value.trim(),
    level:         document.getElementById('level').value.trim(),
    email:         document.getElementById('email').value.trim(),
    phone:         document.getElementById('phone').value.trim(),
    reason:        document.getElementById('reason').value.trim(),
  };

  if (!payload.full_name || !payload.matric_number || !payload.department || !payload.level || !payload.email) {
    err.textContent = 'Please fill in all required fields.';
    err.style.display = 'block';
    return;
  }

  const btn = document.getElementById('submitBtn');
  const orig = btn.textContent;
  btn.textContent = 'Submitting…';
  btn.classList.add('loading');

  fetch('submit_registration_request.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(payload)
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      document.getElementById('formState').style.display = 'none';
      document.getElementById('successState').style.display = 'block';
    } else {
      err.textContent = data.message;
      err.style.display = 'block';
      btn.textContent = orig;
      btn.classList.remove('loading');
    }
  })
  .catch(() => {
    err.textContent = 'Server error. Please try again.';
    err.style.display = 'block';
    btn.textContent = orig;
    btn.classList.remove('loading');
  });
});