(function () {
    function selectRoom(card, cards) {
        cards.forEach(function (item) {
            item.classList.remove("active");
            item.setAttribute("aria-pressed", "false");
        });

        card.classList.add("active");
        card.setAttribute("aria-pressed", "true");
    }

    function revealOnScroll(items) {
        if (!("IntersectionObserver" in window) || window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
            items.forEach(function (item) {
                item.classList.add("visible");
            });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add("visible");
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });

        items.forEach(function (item) {
            observer.observe(item);
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        var cards = Array.prototype.slice.call(document.querySelectorAll(".bh-room-card"));
        var animatedItems = Array.prototype.slice.call(document.querySelectorAll(".anim"));

        cards.forEach(function (card) {
            card.addEventListener("click", function () {
                selectRoom(card, cards);
            });
        });

        revealOnScroll(animatedItems);
    });
})();
