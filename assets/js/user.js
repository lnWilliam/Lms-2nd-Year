document.addEventListener('DOMContentLoaded', function() {
            const usernameInput = document.getElementById('username');
            const usernameStatus = document.getElementById('usernameStatus');
            const firstName = document.getElementById('firstName');
            const lastName = document.getElementById('lastName');
            const emailInput = document.getElementById('email');
            const emailStatus = document.getElementById('emailStatus');
           
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirm_pass');
            let timeoutId;
            const message = document.querySelector('.message') == null ? null : "nice";
            document.querySelector(".container").addEventListener("click", () => {
                if(message !== null){
                document.querySelector('.message').style.display = 'none';}
            });
           
           
            // Username availability check
            usernameInput.addEventListener('input', function() {
                const username = this.value;
                usernameInput.setCustomValidity("");
                // Clear previous timeout
                if (timeoutId) {
                    clearTimeout(timeoutId);
                }

                // Client-side validation
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
                    usernameInput.setCustomValidity("Username can only contain letters, numbers, underscores and dots and 1 @");
                    usernameStatus.className = 'username-status unavailable';
                    
                    return;
                }

                // Show checking status
                usernameStatus.textContent = 'Checking availability...';
                usernameStatus.className = 'username-status checking';
                
                    
                // Set timeout to avoid too many requests
                timeoutId = setTimeout(() => {
                    checkUsernameAvailability(username);
                }, 500);
            });

            function checkUsernameAvailability(username) {
                fetch('../../src/APIs/UserAPI.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                    action : 'check-username',         
                    username: username })
                })
                .then(response => response.json())
                .then(data => {
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
                .catch(error => {
                    console.error('Error:', error);
                    usernameStatus.textContent = 'Error checking username. Please try again.';
                    usernameStatus.className = 'username-status unavailable';
                });
            }
            // Email Availability Check
            emailInput.addEventListener('input', function() {
                const email = this.value;
                emailInput.setCustomValidity("");
                if (timeoutId) {
                    clearTimeout(timeoutId);
                }
                //from google regex
                const emailRegex = /^[a-zA-Z0-9._]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                
                if(!emailRegex.test(email)){
                        emailStatus.textContent = 'Invalid Email Format';
                        emailInput.setCustomValidity("Email can only contain letters, numbers, underscores and dots and 1 @");
                        emailStatus.className = 'email-status unavailable';
                        
                        return;
                }

                emailStatus.textContent = 'Checking availability...';
                emailStatus.className = 'email-status checking';
                

                // Set timeout to avoid too many requests
                timeoutId = setTimeout(() => {
                    checkEmailAvailability(email);
                }, 500);
                
            });
            function checkEmailAvailability(email) {
                fetch('../../src/APIs/UserAPI.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action : 'check-email',
                        email: email })
                })
                .then(response => response.json())
                .then(data => {
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
                .catch(error => {
                    console.error('Error:', error);
                    emailStatus.textContent = 'Error checking email. Please try again.';
                    emailStatus.className = 'email-status unavailable';
                });
            }
            confirmInput.addEventListener('input',()=>{
                const confirm_error = document.querySelector('.confirm_error');
                if (timeoutId) {
                    clearTimeout(timeoutId);
                }
                timeoutId = setTimeout(() => {
                    if(!checkPasswordMatch()){
                        confirm_error.textContent = 'Passwords do not match';
                    }
                    else{
                        confirm_error.textContent = ''
                    }
                }, 500);
            });
            // Password match validation
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirm = confirmInput.value;
                
                if (confirm.length > 0) {
                    if (password !== confirm) {
                        confirmInput.setCustomValidity('Passwords do not match');
                        return false;
                    } else {
                        confirmInput.setCustomValidity('');
                        return true;
                    }
                }
            }

            passwordInput.addEventListener('change', checkPasswordMatch);
            confirmInput.addEventListener('keyup', checkPasswordMatch);

            // Form submission validation
            document.getElementById('registrationForm').addEventListener('submit', function(e) {
                
                if (passwordInput.value !== confirmInput.value) {
                    e.preventDefault();
                    alert('Passwords do not match!');

                }
            });
            });
  