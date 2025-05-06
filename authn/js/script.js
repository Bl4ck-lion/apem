// Theme Toggle
document.addEventListener("DOMContentLoaded", () => {
  const html = document.documentElement;
  const savedTheme = localStorage.getItem("theme") || "light";
  html.setAttribute("data-theme", savedTheme);

  const toggleBtn = document.getElementById("toggleTheme");
  if (toggleBtn) {
    toggleBtn.addEventListener("click", () => {
      const current = html.getAttribute("data-theme");
      const newTheme = current === "dark" ? "light" : "dark";
      html.setAttribute("data-theme", newTheme);
      localStorage.setItem("theme", newTheme);
    });
  }
});

// Login
const loginForm = document.getElementById("loginForm");
if (loginForm) {
  loginForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const email = loginForm["loginEmail"].value;
    const password = loginForm["loginPassword"].value;
    firebase.auth().signInWithEmailAndPassword(email, password)
      .then((userCred) => {
        const user = userCred.user;
        if (user.emailVerified) {
          window.location.href = "dashboard.html";
        } else {
          alert("Please verify your email before logging in.");
        }
      })
      .catch((err) => alert(err.message));
  });
}

// Signup
const signupForm = document.getElementById("signupForm");
if (signupForm) {
  signupForm.addEventListener("submit", (e) => {
    e.preventDefault();

    const name = signupForm["signupName"].value;
    const email = signupForm["signupEmail"].value;
    const password = signupForm["signupPassword"].value;

    firebase.auth().createUserWithEmailAndPassword(email, password)
      .then((userCred) => {
        const user = userCred.user;
        return user.updateProfile({
          displayName: name,
        }).then(() => {
          user.sendEmailVerification();
          alert(`Akun berhasil dibuat! Email verifikasi dikirim ke ${email}`);
          signupForm.reset();
          window.location.href = "login.html";
        });
      })
      .catch((err) => alert(err.message));
  });
}


// Forgot Password
const forgotForm = document.getElementById("forgotForm");
if (forgotForm) {
  forgotForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const email = forgotForm["forgotEmail"].value;
    firebase.auth().sendPasswordResetEmail(email)
      .then(() => alert("Password reset email sent."))
      .catch((err) => alert(err.message));
  });
}

// Dashboard
const userEmailSpan = document.getElementById("userEmail");
if (userEmailSpan) {
  firebase.auth().onAuthStateChanged((user) => {
    if (user && user.emailVerified) {
      userEmailSpan.textContent = user.email;
    } else {
      window.location.href = "login.html";
    }
  });
}

document.getElementById("signOutBtn").onclick = () => {
  firebase.auth().signOut().then(() => {
    // Optional: bersihkan localStorage jika perlu
    localStorage.removeItem("myTryouts");
    window.location.href = "login.html";
  }).catch((error) => {
    console.error("Sign out error:", error);
    alert("Gagal logout. Silakan coba lagi.");
  });
};


// Password 
function togglePassword(inputId, toggleIcon) {
  const input = document.getElementById(inputId);
  if (input.type === "password") {
    input.type = "text";
    toggleIcon.textContent = "🔒";
  } else {
    input.type = "password";
    toggleIcon.textContent = "👁️";
  }
}

