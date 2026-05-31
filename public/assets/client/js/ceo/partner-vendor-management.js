(function ($) {
    const root = document.getElementById("ceoVendorPage");

    if (!root || !window.DashboardEngine) {
        return;
    }

    function money(value) {
        return `${new Intl.NumberFormat("vi-VN").format(Number(value) || 0)} d`;
    }

    function renderVendors(data) {
        const tbody = root.querySelector(".partner-table tbody");
        const products = Array.isArray(data.products) ? data.products : [];

        if (!tbody || !products.length) {
            return;
        }

        tbody.innerHTML = products.map((product) => `
            <tr>
                <td>
                    <div class="partner-vendor-name">${product.product_name || ""}</div>
                    <div class="partner-vendor-category">${product.unit || "Vat tu"}</div>
                </td>
                <td><span class="vendor-tier vendor-tier--silver">Catalog</span></td>
                <td><div class="partner-money">${money(product.item_price)}</div></td>
                <td><div class="partner-score"><span>*</span> API</div></td>
                <td><button type="button" class="partner-detail-btn">Chi tiet</button></td>
            </tr>
        `).join("");
    }

    $(document).ready(function () {
        DashboardEngine.run([
            {
                url: root.dataset.vendorsUrl,
                onSuccess: renderVendors,
            },
        ]);
    });
})(window.jQuery);
