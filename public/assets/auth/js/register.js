document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("registerForm");
    const submitBtn = document.getElementById("submitBtn");
    const serverFieldMap = {
        full_name: "name",
    };

    if (!form || !submitBtn) {
        return;
    }

    let formState = {
        name: "",
        phone: "",
        email: "",
        password: "",
        password_confirmation: "",
    };

    let formErrors = {};

    const validateField = (fieldName, value) => {
        let error = "";

        switch (fieldName) {
            case "name":
                if (!value.trim()) {
                    error = "Họ và tên không được để trống";
                }
                break;
            case "phone": {
                const phoneRegex = /(84|0[3|5|7|8|9])+([0-9]{8})\b/;
                if (!value.trim()) {
                    error = "Số điện thoại không được để trống";
                } else if (!phoneRegex.test(value)) {
                    error = "Số điện thoại không hợp lệ";
                }
                break;
            }
            case "email": {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!value.trim()) {
                    error = "Email không được để trống";
                } else if (!emailRegex.test(value)) {
                    error = "Email không hợp lệ";
                }
                break;
            }
            case "password":
                if (!value) {
                    error = "Mật khẩu không được để trống";
                } else if (value.length < 8) {
                    error = "Mật khẩu phải từ 8 ký tự";
                }
                break;
            case "password_confirmation":
                if (!value) {
                    error = "Vui lòng xác nhận mật khẩu";
                } else if (value !== formState.password) {
                    error = "Mật khẩu không khớp";
                }
                break;
        }

        return error;
    };

    const renderError = (fieldName) => {
        const uiFieldName = serverFieldMap[fieldName] || fieldName;
        const errorElement = document.getElementById("error-" + uiFieldName);
        const inputElement = document.getElementById(uiFieldName);

        if (!errorElement || !inputElement) {
            return;
        }

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

    const inputs = form.querySelectorAll('input:not([type="hidden"])');

    inputs.forEach((input) => {
        input.addEventListener("blur", (event) => {
            const fieldName = event.target.name;
            const value = event.target.value;

            formState[fieldName] = value;
            const error = validateField(fieldName, value);

            if (error) {
                formErrors[fieldName] = error;
            } else {
                delete formErrors[fieldName];
            }

            renderError(fieldName);
        });

        input.addEventListener("input", (event) => {
            const fieldName = event.target.name;
            const value = event.target.value;
            formState[fieldName] = value;

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

    form.addEventListener("submit", async function (event) {
        event.preventDefault();

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

        if (!isValid) {
            return;
        }

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

            // --- ĐIỂM CHẶN 1: KIỂM TRA LỖI SERVER SẬP (500) ---
            if (response.status === 500) {
                // Điều hướng thẳng về trang báo lỗi do bạn thiết kế
                window.location.href = "/500";
                return; // Dừng toàn bộ code phía dưới
            }

            // --- ĐIỂM CHẶN 2: PHÂN TÍCH DỮ LIỆU ---
            const data = await response.json();

            if (response.ok) {
                // Thành công (Status 200-299)
                console.log("Đăng ký thành công!");
                window.location.href = data.redirect_url || "/login";
            } else {
                // Thất bại do người dùng nhập sai (Status 422 Validation)
                if (response.status === 422 && data.errors) {
                    for (const [key, messages] of Object.entries(data.errors)) {
                        formErrors[key] = messages[0];
                        renderError(key);
                    }
                } else {
                    // Các lỗi khác (401, 403, 404...)
                    alert(data.message || "Có lỗi xảy ra, vui lòng thử lại.");
                }
            }
        } catch (error) {
            // --- ĐIỂM CHẶN 3: LỖI MẠNG / KHÔNG THỂ GỌI API ---
            console.error("Lỗi kết nối:", error);
            window.location.href = "/500"; // Có thể điều hướng về trang 500 nếu rớt mạng
        } finally {
            submitBtn.disabled = false;
        }
    });

    const toggleButtons = document.querySelectorAll(".toggle-password");
    toggleButtons.forEach((button) => {
        button.addEventListener("click", function () {
            const targetId = this.getAttribute("data-target");
            const input = document.getElementById(targetId);
            const icon = this.querySelector("i");

            if (!input || !icon) {
                return;
            }

            const isPassword = input.type === "password";
            input.type = isPassword ? "text" : "password";
            icon.classList.toggle("fa-eye");
            icon.classList.toggle("fa-eye-slash");
        });
    });
});
