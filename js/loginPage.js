/* Made by 240115551 // Will Giles -> 31/10/25 (not done at all i give up) */

(function ($) {
    "use strict";


    var input = $('.validate-input .input100');

    $('.validate-form').on('submit', function (e) {
        e.preventDefault();
        var check = true;

        for (var i = 0; i < input.length; i++) {
            if (validate(input[i]) == false) {
                showValidate(input[i]);
                check = false;
            }
        }

        if (check) {
            const container = document.querySelector('.container');
            container.classList.add('active'); // start fingerprint animation
        }

        return check;
    });

    $('.validate-form .input100').each(function () {
        $(this).focus(function () {
            hideValidate(this);
        });
    });

    // Validation rules
    function validate(input) {
        if ($(input).attr('type') == 'email' || $(input).attr('name') == 'email') {
            if (
                $(input)
                    .val()
                    .trim()
                    .match(/^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{1,5}|[0-9]{1,3})(\]?)$/) == null
            ) {
                return false;
            }
        } else {
            if ($(input).val().trim() == '') {
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


    const container = document.querySelector('.container');
    if (container) {
        container.addEventListener('animationend', () => {
            container.classList.remove('active');
        });
    }

    // login / register pages
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

    // LOGIN
    $(document).ready(function () {

        const container = document.querySelector('.container');
        const ok = container.querySelector('.ok');
        const cross = container.querySelector('.cross');

        ok.style.display = 'none';
        cross.style.display = 'none';

        //  TEST for valid user
        const validUser = {
            email: '240115551@aston.ac.uk',
            pass: '12345'
        };

        // when fingerprint container is clicked
        container.addEventListener('click', (e) => {
            e.preventDefault();
            const email = $('input[name="email"]').val().trim();
            const pass = $('input[name="pass"]').val().trim();


            const success = (email === validUser.email && pass === validUser.pass);
            // show tick or corss
            ok.style.display = success ? 'block' : 'none';
            cross.style.display = success ? 'none' : 'block';

        });
    });

})(jQuery);
