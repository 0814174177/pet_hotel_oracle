(function () {
    function previewAvatar(input) {
        const image = document.getElementById("avatar-img");
        const placeholder = document.getElementById("avatar-placeholder");
        const file = input.files && input.files[0];

        if (!file || !image) {
            return;
        }

        const reader = new FileReader();

        reader.onload = function (event) {
            image.src = event.target.result;
            image.hidden = false;

            if (placeholder) {
                placeholder.hidden = true;
            }
        };

        reader.readAsDataURL(file);
    }

    document.addEventListener("DOMContentLoaded", function () {
        const form = document.getElementById("profile-form");
        const avatarInput = document.getElementById("avatar");
        const toggleButton = document.getElementById("toggle-password-btn");
        const passwordSection = document.getElementById("password-section");
        const passwordInputs = document.querySelectorAll(".pwd-input");
        const passwordError = document.getElementById("password-error");

        if (avatarInput) {
            avatarInput.addEventListener("change", function () {
                previewAvatar(this);
            });
        }

        if (!form || !toggleButton || !passwordSection || !passwordError) {
            return;
        }

        let isPasswordExpanded = false;

        toggleButton.addEventListener("click", function () {
            isPasswordExpanded = !isPasswordExpanded;
            passwordSection.classList.toggle("expanded", isPasswordExpanded);

            toggleButton.innerHTML = isPasswordExpanded
                ? '<i class="fa-solid fa-chevron-up"></i> Hủy đổi'
                : '<i class="fa-solid fa-chevron-down"></i> Thay đổi';

            if (!isPasswordExpanded) {
                passwordError.hidden = true;

                passwordInputs.forEach(function (input) {
                    input.value = "";
                    input.classList.remove("input-error");
                });
            }
        });

        form.addEventListener("submit", function (event) {
            if (!isPasswordExpanded) {
                return;
            }

            let emptyCount = 0;

            passwordInputs.forEach(function (input) {
                input.classList.remove("input-error");

                if (input.value.trim() === "") {
                    emptyCount += 1;
                    input.classList.add("input-error");
                }
            });

            if (emptyCount === 0) {
                passwordError.hidden = true;
                return;
            }

            event.preventDefault();
            passwordError.hidden = false;
            passwordError.innerHTML = emptyCount === passwordInputs.length
                ? '<i class="fa-solid fa-circle-exclamation"></i> Bạn đang mở khung đổi mật khẩu nhưng để trống. Vui lòng nhập thông tin hoặc ấn "Hủy đổi".'
                : '<i class="fa-solid fa-circle-exclamation"></i> Vui lòng điền đầy đủ thông tin vào các trường được tô đỏ.';
        });

        passwordInputs.forEach(function (input) {
            input.addEventListener("input", function () {
                input.classList.remove("input-error");
                passwordError.hidden = true;
            });
        });
    });
})();
