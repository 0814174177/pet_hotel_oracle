(function () {
    document.addEventListener("DOMContentLoaded", function () {
        const input = document.getElementById("pet_image");
        const preview = document.getElementById("pet-edit-img-preview");
        const wrapper = document.getElementById("pet-edit-avatar-wrapper");

        if (!input || !preview || !wrapper) {
            return;
        }

        input.addEventListener("change", function () {
            const file = input.files && input.files[0];

            if (!file) {
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                alert("Ảnh quá lớn, vui lòng chọn ảnh dưới 2MB");
                input.value = "";
                return;
            }

            const reader = new FileReader();

            reader.onload = function (event) {
                preview.src = event.target.result;
                wrapper.classList.remove("is-empty");
                wrapper.classList.add("has-image");
            };

            reader.readAsDataURL(file);
        });
    });
})();
