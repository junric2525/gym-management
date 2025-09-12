// Array to store submitted emails
const usedEmails = [];

// Elements
const emailInput = document.getElementById('emailInput');
const passwordInput = document.getElementById('passwordInput');
const signinBtn = document.getElementById('signinBtn');
const emailsList = document.getElementById('emailsList');

// Function to update datalist for suggestions
function updateEmailSuggestions() {
  emailsList.innerHTML = '';
  usedEmails.forEach(email => {
    const option = document.createElement('option');
    option.value = email;
    emailsList.appendChild(option);
  });
}

// Sign-in button click
signinBtn.addEventListener('click', (e) => {
  e.preventDefault();

  const email = emailInput.value.trim();
  const password = passwordInput.value;

  if (!email) {
    alert('Please enter your email!');
    return;
  }

  if (!password) {
    alert('Please enter your password!');
    return;
  }

  // Save email if not already saved
  if (!usedEmails.includes(email)) {
    usedEmails.push(email);
    updateEmailSuggestions();
  }

  // ✅ Simulate login success
  // alert('Logged in successfully!');
  // Redirect to another HTML page
  window.location.href = 'http://127.0.0.1:5500/User/User.html'; // <-- replace with your target page
});

// Optional: live suggestions while typing (HTML datalist handles it)



function toggleMenu() {
  const mobileMenu = document.getElementById('mobileMenu'); // the dropdown menu
  const menuIcon = document.getElementById('menuIcon');     // the icon inside the button

  // Toggle visibility
  mobileMenu.classList.toggle('show');

  // Optional: toggle icon between bars and X
  if (mobileMenu.classList.contains('show')) {
    menuIcon.classList.remove('fa-bars');
    menuIcon.classList.add('fa-xmark');
  } else {
    menuIcon.classList.remove('fa-xmark');
    menuIcon.classList.add('fa-bars');
  }
}

