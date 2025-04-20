// auth.js - Handle authentication (login & registration)
console.log("auth.js loaded");

document.addEventListener('DOMContentLoaded', function() {
    // Registration form handler
    const registerForm = document.getElementById('registerForm');
    
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Reset error messages
            const errorElements = document.querySelectorAll('.error-message');
            errorElements.forEach(element => {
                element.textContent = '';
            });
            
            // Get form data
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Validate input
            let isValid = true;
            
            // Username validation
            if (username === '') {
                document.getElementById('username-error').textContent = 'Username is required';
                isValid = false;
            } else if (username.length < 3) {
                document.getElementById('username-error').textContent = 'Username must be at least 3 characters';
                isValid = false;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email === '') {
                document.getElementById('email-error').textContent = 'Email is required';
                isValid = false;
            } else if (!emailRegex.test(email)) {
                document.getElementById('email-error').textContent = 'Please enter a valid email address';
                isValid = false;
            }
            
            // Password validation
            if (password === '') {
                document.getElementById('password-error').textContent = 'Password is required';
                isValid = false;
            } else if (password.length < 8) {
                document.getElementById('password-error').textContent = 'Password must be at least 8 characters';
                isValid = false;
            }
            
            // Confirm password validation
            if (confirmPassword === '') {
                document.getElementById('confirm-password-error').textContent = 'Please confirm your password';
                isValid = false;
            } else if (confirmPassword !== password) {
                document.getElementById('confirm-password-error').textContent = 'Passwords do not match';
                isValid = false;
            }
            
            // If validation passes, submit the form
            if (isValid) {
                // Create form data object
                const formData = new FormData();
                formData.append('username', username);
                formData.append('email', email);
                formData.append('password', password);
                formData.append('action', 'register');
                
                // Send AJAX request
                fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Registration successful
                        window.location.href = 'dashboard.php';
                    } else {
                        // Registration failed
                        if (data.errors) {
                            // Display specific errors
                            if (data.errors.username) {
                                document.getElementById('username-error').textContent = data.errors.username;
                            }
                            if (data.errors.email) {
                                document.getElementById('email-error').textContent = data.errors.email;
                            }
                            if (data.errors.password) {
                                document.getElementById('password-error').textContent = data.errors.password;
                            }
                        } else {
                            // Display general error
                            alert('Registration failed. Please try again.');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again later.');
                });
            }
        });
    } else {
        console.log('Register form not found!');
    }
    
    // Login form handler
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Reset error messages
            const errorElements = document.querySelectorAll('.error-message');
            errorElements.forEach(element => {
                element.textContent = '';
            });
            
            // Get form data
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            // Validate input
            let isValid = true;
            
            // Email validation
            if (email === '') {
                document.getElementById('email-error').textContent = 'Email is required';
                isValid = false;
            }
            
            // Password validation
            if (password === '') {
                document.getElementById('password-error').textContent = 'Password is required';
                isValid = false;
            }
            
            // If validation passes, submit the form
            if (isValid) {
                // Create form data object
                const formData = new FormData();
                formData.append('email', email);
                formData.append('password', password);
                formData.append('action', 'login');
                
                // Send AJAX request
                fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Login successful
                        window.location.href = 'dashboard.php';
                    } else {
                        // Login failed
                        document.getElementById('login-error').textContent = data.message || 'Invalid email or password';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again later.');
                });
            }
        });
    } else {
        console.log('Login form not found!');
    }
});
