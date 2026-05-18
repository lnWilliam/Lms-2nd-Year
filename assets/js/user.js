document.addEventListener('DOMContentLoaded', function () {
    const usernameInput = document.getElementById('username');
    const usernameStatus = document.getElementById('usernameStatus');
    const emailInput = document.getElementById('email');
    const emailStatus = document.getElementById('emailStatus');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_pass');
    const confirmError = document.querySelector('.confirm_error');
    const registrationForm = document.getElementById('registrationForm');
    const messageBox = document.querySelector('.message');
    const container = document.querySelector('.container');

    let usernameTimeout;
    let emailTimeout;
    let passwordTimeout;

    if (messageBox && container) {
        container.addEventListener('click', function () {
            messageBox.style.display = 'none';
        });
    }

    if (usernameInput && usernameStatus) {
        usernameInput.addEventListener('input', function () {
            const username = this.value;
            usernameInput.setCustomValidity('');
            clearTimeout(usernameTimeout);

            if (username.length === 0) {
                usernameStatus.textContent = 'Username is required';
                usernameStatus.className = 'username-status unavailable';
                return;
            }

            if (username.length < 3) {
                usernameStatus.textContent = 'Username must be at least 3 characters';
                usernameStatus.className = 'username-status unavailable';
                return;
            }

            if (!/^[a-zA-Z]/.test(username)) {
                usernameStatus.textContent = 'Username must start with a letter';
                usernameStatus.className = 'username-status unavailable';
                return;
            }

            if (!/^[a-zA-Z][a-zA-Z0-9_.]*$/.test(username)) {
                usernameStatus.textContent = 'Username can only contain letters, numbers, underscores and dots';
                usernameInput.setCustomValidity('Username can only contain letters, numbers, underscores and dots');
                usernameStatus.className = 'username-status unavailable';
                return;
            }

            usernameStatus.textContent = 'Checking availability...';
            usernameStatus.className = 'username-status checking';

            usernameTimeout = setTimeout(function () {
                checkUsernameAvailability(username);
            }, 500);
        });
    }

    function checkUsernameAvailability(username) {
        fetch('../../src/APIs/UserAPI.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'check-username',
                username: username
            })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data.valid) {
                    usernameStatus.textContent = data.errors.join(', ');
                    usernameStatus.className = 'username-status unavailable';
                } else if (data.available) {
                    usernameStatus.textContent = '✓ Username is available!';
                    usernameStatus.className = 'username-status available';
                } else {
                    usernameStatus.textContent = '✗ Username is already taken';
                    usernameStatus.className = 'username-status unavailable';
                }
            })
            .catch(function (error) {
                console.error('Error:', error);
                usernameStatus.textContent = 'Error checking username. Please try again.';
                usernameStatus.className = 'username-status unavailable';
            });
    }

    if (emailInput && emailStatus) {
        emailInput.addEventListener('input', function () {
            const email = this.value.trim();
            emailInput.setCustomValidity('');
            clearTimeout(emailTimeout);

            if (email.length === 0) {
                emailStatus.textContent = 'Email is required';
                emailStatus.className = 'email-status unavailable';
                return;
            }

            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

            if (!emailRegex.test(email)) {
                emailStatus.textContent = 'Invalid Email Format';
                emailInput.setCustomValidity('Please enter a valid email address');
                emailStatus.className = 'email-status unavailable';
                return;
            }

            emailStatus.textContent = 'Checking availability...';
            emailStatus.className = 'email-status checking';

            emailTimeout = setTimeout(function () {
                checkEmailAvailability(email);
            }, 500);
        });
    }

    function checkEmailAvailability(email) {
        fetch('../../src/APIs/UserAPI.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'check-email',
                email: email
            })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data.valid) {
                    emailStatus.textContent = data.errors.join(', ');
                    emailStatus.className = 'email-status unavailable';
                } else if (data.available) {
                    emailStatus.textContent = '✓ Email is available!';
                    emailStatus.className = 'email-status available';
                } else {
                    emailStatus.textContent = '✗ Email is already taken';
                    emailStatus.className = 'email-status unavailable';
                }
            })
            .catch(function (error) {
                console.error('Error:', error);
                emailStatus.textContent = 'Error checking email. Please try again.';
                emailStatus.className = 'email-status unavailable';
            });
    }

    function checkPasswordMatch() {
        if (!passwordInput || !confirmInput || !confirmError) {
            return true;
        }

        const password = passwordInput.value;
        const confirm = confirmInput.value;

        if (confirm.length === 0) {
            confirmInput.setCustomValidity('');
            confirmError.textContent = '';
            return true;
        }

        if (password !== confirm) {
            confirmInput.setCustomValidity('Passwords do not match');
            confirmError.textContent = 'Passwords do not match';
            return false;
        }

        confirmInput.setCustomValidity('');
        confirmError.textContent = '';
        return true;
    }

    if (passwordInput && confirmInput) {
        passwordInput.addEventListener('input', function () {
            clearTimeout(passwordTimeout);
            passwordTimeout = setTimeout(checkPasswordMatch, 300);
        });

        confirmInput.addEventListener('input', function () {
            clearTimeout(passwordTimeout);
            passwordTimeout = setTimeout(checkPasswordMatch, 300);
        });
    }

    document.querySelectorAll('.toggle-password').forEach(function (button) {
        button.addEventListener('click', function () {
            const targetId = this.dataset.target;
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');

            if (!input || !icon) {
                return;
            }

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    if (registrationForm) {
        registrationForm.addEventListener('submit', function (e) {
            if (!checkPasswordMatch()) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    }
});
