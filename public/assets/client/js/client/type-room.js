(function () {
    function page() {
        return document.querySelector('.rd-page');
    }

    window.rdSwitchImage = function (src, trigger) {
        var mainImage = document.getElementById('rdMainImage');

        if (mainImage && src) {
            mainImage.src = src;
        }

        document.querySelectorAll('.rd-gallery-thumb').forEach(function (thumb) {
            thumb.classList.remove('rd-gallery-thumb--active');
        });

        if (trigger) {
            trigger.classList.add('rd-gallery-thumb--active');
        }
    };

    window.rdBookNow = function () {
        var target = page()?.dataset.rdBookingUrl || '/booking';
        window.location.href = target;
    };
})();
