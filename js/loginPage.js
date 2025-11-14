/* Made by 240115551 // Will Giles -> working updated version as of 14/11/2025*/

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

        // Skip validation if the input isnt visible
        if (!$(input).is(':visible')) {
            return true;
        }

        if (
            $(input).attr('type') === 'email' ||
            $(input).attr('name') === 'email' ||
            $(input).attr('name') === 'reg_email'
        ) {
            const emailRegex = /^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{1,5}|[0-9]{1,3})(\]?)$/; // check email format

            if (!$(input).val().trim().match(emailRegex)) { //if matches above
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
        var thisAlert = $(input).parent();        //error alert
        $(thisAlert).addClass('alert-validate');
    }
    function hideValidate(input) {
        var thisAlert = $(input).parent();
        $(thisAlert).removeClass('alert-validate');
    }





    // Validate all visible inputs
    function validateAllInputs() {
        const form = document.querySelector(".login100-form");
        const isRegisterMode = form.classList.contains("register-mode");
        let check = true;

        if (isRegisterMode) {
            

            const nameInput = $('input[name="fullname"]:visible');
            const emailInput = $('input[name="reg_email"]:visible');
            const passInput = $('input[name="reg_pass"]:visible');
            const confirmInput = $('input[name="confirm_pass"]:visible');



            if (!validate(nameInput[0])) {
                showValidate(nameInput[0]);
                check = false;
            }
            if (!validate(emailInput[0])) {              //REGISTER FEILDS
                showValidate(emailInput[0]);
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
            
            if (passInput.val().trim() !== confirmInput.val().trim()) {  //matches passwod?
                showValidate(confirmInput[0]);
                check = false;
            }
        } else {
            
            const emailInput = $('input[name="email"]:visible');     
            const passInput = $('input[name="pass"]:visible'); 


            if (!validate(emailInput[0])) {
                showValidate(emailInput[0]);             //LOGIN FIELDS
                check = false;      
            }
            if (!validate(passInput[0])) {
                showValidate(passInput[0]);
                check = false;
            }
        }

        return check;
    }

    const container = document.querySelector('.container');
    if (container) {
        container.addEventListener('animationend', () => {
            container.classList.remove('active');

            //reset icons
            const ok = container.querySelector('.ok');
            const cross = container.querySelector('.cross');
        });

    }

    // login  --  register pages

    document.addEventListener("DOMContentLoaded", function () {


        const toggleLink = document.getElementById("toggleForm");
        const form = document.querySelector(".login100-form");
        const title = form.querySelector(".login-title");
        const textButton = document.querySelector(".text");

        toggleLink.addEventListener("click", function (e) {
            e.preventDefault();

            form.classList.toggle("register-mode");

            if (form.classList.contains("register-mode")) {
                title.textContent = "Register Account";
                textButton.textContent = "REGISTER";
                toggleLink.innerHTML = 'Already have an account? <i class="fa fa-long-arrow-left m-l-5" aria-hidden="true"></i>';
            } else {
                title.textContent = "Member Login";
                textButton.textContent = "LOGIN";
                toggleLink.innerHTML = 'Create your Account <i class="fa fa-long-arrow-right m-l-5" aria-hidden="true"></i>';
            }
        });
    });

    // valid?
    $(document).ready(function () {
        const container = document.querySelector('.container');
        const ok = container.querySelector('.ok');
        const cross = container.querySelector('.cross');

 
        ok.style.display = 'none';
        ok.style.opacity = '0';               //OFF
        cross.style.display = 'none';
        cross.style.opacity = '0';

        
        const validUser = {
            email: '240115551@aston.ac.uk',   //example user while database not linked
            pass: '12345'
        };


        function showResult(isSuccess) {

            container.classList.add('active');

            if (isSuccess) {
                ok.style.display = 'block';
                ok.style.opacity = '1';
            } else {
                cross.style.display = 'block';
                cross.style.opacity = '1';
            }
        }

        //reset anim
        container.addEventListener('animationend', () => {
            container.classList.remove('active');

            ok.style.display = 'none';
            ok.style.opacity = '0';
            cross.style.display = 'none';
            cross.style.opacity = '0';
        });

        // login/register pressed
        container.addEventListener('click', (e) => {
            e.preventDefault();

            const form = document.querySelector(".login100-form");
            const isRegisterMode = form.classList.contains("register-mode");  //both that need pressing 

            const isValid = validateAllInputs();

            if (!isValid) {
                
                showResult(false);
                console.log('Validation failed!');  // STILL HAPPENS AFTR ANIMATION GOES, MAY CHANGE
                return;
            }

            let success = false;

            if (isRegisterMode) {
                success = true; 
            } else {
                const email = $('input[name="email"]').val().trim();
                const pass = $('input[name="pass"]').val().trim();
                success = (email === validUser.email && pass === validUser.pass);
            }
            showResult(success);

        });
    });


})(jQuery);
