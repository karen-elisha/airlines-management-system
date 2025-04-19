// Show/hide password function
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    passwordField.type = passwordField.type === "password" ? "text" : "password";
  }
  
  // Handle login form submissions (just an example)
  document.getElementById('userLoginForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    alert('User login successful!');
  });
  
  document.getElementById('adminLoginForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    alert('Admin login successful!');
  });
  