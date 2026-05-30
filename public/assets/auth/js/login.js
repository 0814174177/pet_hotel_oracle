document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("loginForm");
    const submitBtn = document.getElementById("submitBtn");
    const generalError = document.getElementById("general-error");

    // 1. Quản lý trạng thái dữ liệu và lỗi (State Management)
    let formState = { email: "", password: "" };
    let formErrors = {};

    // 2. Định nghĩa quy tắc kiểm tra (Validation Rules)
    const validateField = (fieldName, value) => {
        let error = "";
        switch (fieldName) {
            case "email":
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!value.trim()) {
                    error = "Vui lòng nhập email";
                } else if (!emailRegex.test(value)) {
                    error = "Định dạng email không hợp lệ";
                }
                break;
            case "password":
                if (!value) {
                    error = "Vui lòng nhập mật khẩu";
                }
                break;
        }
        return error;
    };

    // 3. Hàm cập nhật hiển thị lỗi lên giao diện (Render UI)
    const renderError = (fieldName) => {
        const errorElement = document.getElementById(`error-${fieldName}`);
        const inputElement = document.getElementById(fieldName);

        if (formErrors[fieldName]) {
            errorElement.innerText = formErrors[fieldName];
            errorElement.style.display = "block";
            inputElement.classList.add("input-error");
        } else {
            errorElement.innerText = "";
            errorElement.style.display = "none";
            inputElement.classList.remove("input-error");
        }
    };

    // 4. Đăng ký sự kiện giám sát nhập liệu (Event Listeners)
    const inputs = form.querySelectorAll(
        'input[type="email"], input[type="password"]',
    );

    inputs.forEach((input) => {
        // Xử lý khi rời khỏi ô nhập liệu
        input.addEventListener("blur", (e) => {
            const fieldName = e.target.name;
            const value = e.target.value;
            formState[fieldName] = value;

            const error = validateField(fieldName, value);
            if (error) {
                formErrors[fieldName] = error;
            } else {
                delete formErrors[fieldName];
            }
            renderError(fieldName);
        });

        // Xử lý khi đang gõ ký tự
        input.addEventListener("input", (e) => {
            const fieldName = e.target.name;
            const value = e.target.value;
            formState[fieldName] = value;
            generalError.style.display = "none";

            if (formErrors[fieldName]) {
                const error = validateField(fieldName, value);
                if (error) {
                    formErrors[fieldName] = error;
                } else {
                    delete formErrors[fieldName];
                }
                renderError(fieldName);
            }
        });
    });

    // 5. Xử lý sự kiện gửi Form (Submit & Fetch API)
    form.addEventListener("submit", async function (e) {
        e.preventDefault();
        generalError.style.display = "none";

        let isValid = true;
        inputs.forEach((input) => {
            const fieldName = input.name;
            const value = input.value;
            formState[fieldName] = value;

            const error = validateField(fieldName, value);
            if (error) {
                formErrors[fieldName] = error;
                isValid = false;
            } else {
                delete formErrors[fieldName];
            }
            renderError(fieldName);
        });

        if (!isValid) return;

        submitBtn.disabled = true;

        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: "POST",
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
            });

            // Chặn và xử lý lỗi hệ thống 500
            if (response.status === 500) {
                window.location.href = "/500";
                return;
            }

            const data = await response.json();

            if (response.ok) {
                // Đăng nhập thành công -> Điều hướng theo yêu cầu hệ thống
                window.location.href = data.redirect_url || "/";
            } else {
                // Xử lý lỗi trả về từ Laravel Validation hoặc Logic kiểm tra mật khẩu (422)
                if (response.status === 422) {
                    if (data.errors) {
                        for (const [key, messages] of Object.entries(
                            data.errors,
                        )) {
                            formErrors[key] = messages[0];
                            renderError(key);
                        }
                    } else if (data.message) {
                        generalError.innerText = data.message;
                        generalError.style.display = "block";
                    }
                }
            }
        } catch (error) {
            console.error("Lỗi kết nối mạng:", error);
            generalError.innerText =
                "Không thể kết nối đến máy chủ. Vui lòng kiểm tra mạng.";
            generalError.style.display = "block";
        } finally {
            submitBtn.disabled = false;
        }
    });

    // 6. Xử lý ẩn/hiện mật khẩu
    const togglePassword = document.querySelector(".toggle-password");
    const passwordInput = document.querySelector("#password");
    const passwordIcon = document.querySelector(".toggle-password i");

    if (togglePassword) {
        togglePassword.addEventListener("click", function () {
            const isPassword = passwordInput.type === "password";
            passwordInput.type = isPassword ? "text" : "password";
            passwordIcon.classList.toggle("fa-eye");
            passwordIcon.classList.toggle("fa-eye-slash");
        });
    }
});
