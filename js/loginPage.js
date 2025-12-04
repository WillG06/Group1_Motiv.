/* Made by 240115551 // Will Giles -> working updated version as of 03/12/2025 */

(function ($) {
    "use strict";

    var input = $('.validate-input .input100');

    $('.validate-form').on('submit', function (e) {
        e.preventDefault();
        return false;
    });

    $('.validate-form .input100:visible').each(function () {
        $(this).focus(function () {
            hideValidate(this);
        });
    });

    function validate(input) {
        if (!$(input).is(':visible')) {
            return true;
        }

        if (
            $(input).attr('type') === 'email' ||
            $(input).attr('name') === 'email' ||
            $(input).attr('name') === 'reg_email'
        ) {
            const emailRegex = /^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{1,5}|[0-9]{1,3})(\]?)$/;

            if (!$(input).val().trim().match(emailRegex)) {
                return false;
            }

        } else if ($(input).attr('id') === 'regDriving') {

            const licenceRegex = /^[A-Z9]{5}\d{6}[A-Z]{2}\d{2}$/i;

            if (!$(input).val().trim().match(licenceRegex)) {
                return false;
            }

        } else {

            if ($(input).val().trim() === '') {
                return false;
            }
        }

        return true;
    }

    function showValidate(input) {
        var thisAlert = $(input).parent();
        $(thisAlert).addClass('alert-validate');
    }
    
    function hideValidate(input) {
        var thisAlert = $(input).parent();
        $(thisAlert).removeClass('alert-validate');
    }

    function validateAllInputs() {
        const form = document.querySelector(".login100-form");
        const isRegisterMode = form.classList.contains("register-mode");
        let check = true;

        if (isRegisterMode) {
            const nameInput = $('#regFullname');
            const emailInput = $('#regEmail');
            const drivingInput = $('#regDriving');          
            const passInput = $('#regPassword');
            const confirmInput = $('#confirmPassword');

            if (!validate(nameInput[0])) {
                showValidate(nameInput[0]);
                check = false;
            }

            if (!validate(emailInput[0])) {
                showValidate(emailInput[0]);
                check = false;
            }

            if (!validate(drivingInput[0])) {
                showValidate(drivingInput[0]);
                check = false;
            }

            if (!validate(passInput[0])) {
                showValidate(passInput[0]);
                check = false;
            }
            if (!validate(confirmInput[0])) {
                showValidate(confirmInput[0]);
                check = false;
            }

            if (passInput.val().trim() !== "" &&
                confirmInput.val().trim() !== "" &&
                passInput.val().trim() !== confirmInput.val().trim()) {
                showMismatch(confirmInput[0]);
                check = false;
            }

        } else {
            const emailInput = $('#loginEmail');
            const passInput = $('#loginPassword');

            if (!validate(emailInput[0])) {
                showValidate(emailInput[0]);
                check = false;
            }
            if (!validate(passInput[0])) {
                showValidate(passInput[0]);
                check = false;
            }
        }

        return check;
    }

    function showMismatch(input) {
        var thisAlert = $(input).parent();
        $(thisAlert).addClass('alert-mismatch');
    }

    const container = document.querySelector('.container');
    if (container) {
        container.addEventListener('animationend', () => {
            container.classList.remove('active');
        });
    }

    // Toggle between login and register
    document.addEventListener("DOMContentLoaded", function () {
        const toggleLink = document.getElementById("toggleForm");
        const form = document.querySelector(".login100-form");
        const title = form.querySelector(".login-title");
        const textButton = document.querySelector(".text");
        const formActionInput = document.getElementById("formAction");
        const loginTypeInput = document.getElementById("loginType");
        const loginFields = document.querySelector('.login-fields');
        const registerFields = document.querySelector('.register-fields');
        const emailInput = document.getElementById('loginEmail');

        if (toggleLink && form && title && textButton) {
            toggleLink.addEventListener("click", function (e) {
                e.preventDefault();

                form.classList.toggle("register-mode");

                if (form.classList.contains("register-mode")) {
                    // SHOW REGISTER FIELDS
                    if (loginFields) loginFields.style.display = 'none';
                    if (registerFields) registerFields.style.display = 'block';
                    
                    title.textContent = "Create Account";
                    textButton.textContent = "REGISTER";
                    toggleLink.innerHTML = 'Already have an account? <i class="fa fa-long-arrow-left m-l-5" aria-hidden="true"></i>';
                    if (formActionInput) formActionInput.value = "register";
                } else {
                    // SHOW LOGIN FIELDS
                    if (loginFields) loginFields.style.display = 'block';
                    if (registerFields) registerFields.style.display = 'none';
                    
                    // Reset to customer login when going back to login
                    if (loginTypeInput) loginTypeInput.value = 'customer';
                    title.textContent = "Member Login";
                    textButton.textContent = "LOGIN";
                    if (emailInput) emailInput.placeholder = 'Email / Member ID';
                    toggleLink.innerHTML = 'Create your Account <i class="fa fa-long-arrow-right m-l-5" aria-hidden="true"></i>';
                    if (formActionInput) formActionInput.value = "login";
                }
            });
        }

        const adminToggle = document.createElement('a');
        adminToggle.href = '#';
        adminToggle.className = 'txt2 admin-toggle';
        adminToggle.style.display = 'block';
        adminToggle.style.marginTop = '10px';
        adminToggle.textContent = 'Login as Admin';
        
        const forgotSection = document.querySelector('.text-center.p-t-12');
        if (forgotSection) {
            forgotSection.appendChild(adminToggle);
        }

        adminToggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Only allow toggle when NOT in register mode
            if (!form.classList.contains("register-mode")) {
                if (loginTypeInput && loginTypeInput.value === 'customer') {
                    loginTypeInput.value = 'admin';
                    this.textContent = 'Login as Customer';
                    if (emailInput) emailInput.placeholder = 'Email / Member ID';
                    title.textContent = "Admin Login";
                } else if (loginTypeInput) {
                    loginTypeInput.value = 'customer';
                    this.textContent = 'Login as Admin';
                    if (emailInput) emailInput.placeholder = 'Email / Member ID';
                    title.textContent = "Member Login";
                }
            }
        });
    });

    // AJAX authentication
    $(document).ready(function () {
        const container = document.querySelector('.container');
        if (!container) return;

        const ok = container.querySelector('.ok');
        const cross = container.querySelector('.cross');

        if (ok && cross) {
            ok.style.display = 'none';
            ok.style.opacity = '0';
            cross.style.display = 'none';
            cross.style.opacity = '0';
        }

        function showResult(isSuccess) {
            container.classList.add('active');

            if (isSuccess && ok) {
                ok.style.display = 'block';
                ok.style.opacity = '1';
            } else if (!isSuccess && cross) {
                cross.style.display = 'block';
                cross.style.opacity = '1';
            }
        }

        container.addEventListener('animationend', () => {
            container.classList.remove('active');

            if (ok && cross) {
                ok.style.display = 'none';
                ok.style.opacity = '0';
                cross.style.display = 'none';
                cross.style.opacity = '0';
            }
        });

        // Remove validation alerts on input
        document.querySelectorAll('.input100').forEach(input => {
            input.addEventListener('input', function() {
                this.closest('.input-wrap').classList.remove('alert-validate');
                this.closest('.input-wrap').classList.remove('alert-mismatch');
            });

            input.addEventListener('blur', function() {
                if ($(this).is(':visible')) {
                    validate(this);
                }
            });
        });

        // Handle form submission
        container.addEventListener('click', async (e) => {
            e.preventDefault();

            const form = document.querySelector(".login100-form");
            const isRegisterMode = form.classList.contains("register-mode");

            const isValid = validateAllInputs();

            if (!isValid) {
                showResult(false);
                console.log('Validation failed!');
                return;
            }

            // Prepare form data
            const formData = new FormData();
            
            if (isRegisterMode) {
                const fullname = $('#regFullname').val().trim();
                const email = $('#regEmail').val().trim();
                const password = $('#regPassword').val().trim();
                const confirmPassword = $('#confirmPassword').val().trim();

                // Check password match
                if (password !== confirmPassword) {
                    showResult(false);
                    setTimeout(() => {
                        alert('Passwords do not match');
                    }, 1000);
                    return;
                }

                formData.append('action', 'register');
                formData.append('fullname', fullname);
                formData.append('email', email);
                formData.append('password', password);
                formData.append('confirm_password', confirmPassword);
        
                const driving = $('#regDriving').val().trim();
                formData.append('driving_licence', driving);

                
            } else {
                const email = $('#loginEmail').val().trim();
                const password = $('#loginPassword').val().trim();
                const loginType = $('#loginType').val() || 'customer';

                formData.append('action', 'login');
                formData.append('email', email);
                formData.append('password', password);
                formData.append('loginType', loginType);
            }

            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    showResult(true);
                    
                    // Redirect after successful login/registration
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 6000);
                } else {
                    showResult(false);
                    
                    // Show error message
                    setTimeout(() => {
                        alert(result.message);
                    }, 6000);
                }
            } catch (error) {
                console.error('Error:', error);
                showResult(false);
                
                setTimeout(() => {
                    alert('An error occurred. Please try again.');
                }, 1000);
            }
        });
    });

})(jQuery);
