document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("pet-form");

    if (!form) {
        return;
    }

    let formErrors = {};

    const validateField = (fieldName, value) => {
        let error = "";

        switch (fieldName) {
            case "pet_name":
                if (!value.trim()) {
                    error = "Tên không được để trống";
                }
                break;
            case "species":
                if (!value) {
                    error = "Vui lòng chọn loài";
                }
                break;
            case "weight_kg":
                if (value && parseFloat(value) < 0.1) {
                    error = "Cân nặng phải lớn hơn hoặc bằng 0.1";
                }
                break;
        }

        return error;
    };

    const renderError = (fieldName) => {
        const errorElement = document.getElementById(`error-${fieldName}`);
        const inputElement = document.getElementById(fieldName);

        if (!errorElement || !inputElement) {
            return;
        }

        if (formErrors[fieldName]) {
            errorElement.innerText = formErrors[fieldName];
            errorElement.style.display = "block";
            inputElement.classList.add("input-error");
            return;
        }

        errorElement.innerText = "";
        errorElement.style.display = "none";
        inputElement.classList.remove("input-error");
    };

    const inputs = form.querySelectorAll(
        'input:not([type="hidden"]):not([type="file"]), select, textarea',
    );

    inputs.forEach((input) => {
        input.addEventListener("blur", (e) => {
            const fieldName = e.target.name;
            const error = validateField(fieldName, e.target.value);

            if (error) {
                formErrors[fieldName] = error;
            } else {
                delete formErrors[fieldName];
            }

            renderError(fieldName);
        });

        const triggerEvent = input.tagName === "SELECT" ? "change" : "input";

        input.addEventListener(triggerEvent, (e) => {
            const fieldName = e.target.name;

            if (!formErrors[fieldName]) {
                return;
            }

            const error = validateField(fieldName, e.target.value);

            if (error) {
                formErrors[fieldName] = error;
            } else {
                delete formErrors[fieldName];
            }

            renderError(fieldName);
        });
    });

    form.addEventListener("submit", function (e) {
        let isValid = true;

        inputs.forEach((input) => {
            const fieldName = input.name;
            const error = validateField(fieldName, input.value);

            if (error) {
                formErrors[fieldName] = error;
                isValid = false;
            } else {
                delete formErrors[fieldName];
            }

            renderError(fieldName);
        });

        if (!isValid) {
            e.preventDefault();
        }
    });

    const inputImage = document.getElementById("pet_image");
    const imagePreview = document.getElementById("pet-img");
    const container = document.getElementById("pet-avatar-container");

    if (inputImage && imagePreview && container) {
        inputImage.addEventListener("change", function () {
            const file = inputImage.files && inputImage.files[0];

            delete formErrors.pet_image;
            renderError("pet_image");

            if (!file) {
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                formErrors.pet_image = "Ảnh quá lớn, vui lòng chọn ảnh dưới 2MB";
                renderError("pet_image");
                inputImage.value = "";
                return;
            }

            const reader = new FileReader();

            reader.onload = function (event) {
                imagePreview.src = event.target.result;
                container.classList.add("has-image");
            };

            reader.readAsDataURL(file);
        });
    }
});
