(function () {
    "use strict";

    const root = document.getElementById("managerEmployeePage");

    if (!root) {
        return;
    }

    let employees = readEmployees().filter(isWorkingEmployee);

    const selectors = {
        statTotal: "[data-stat-total]",
        statPositions: "[data-stat-positions]",
        statAvgSalary: "[data-stat-avg-salary]",
        searchInput: "[data-search-input]",
        filterPosition: "[data-filter-position]",
        tableBody: "[data-employee-table-body]",
        tableCount: "[data-table-count]",
        emptyState: "[data-empty-state]",
        openCreateModal: "[data-open-create-modal]",
        closeCreateModal: "[data-close-create-modal]",
        createOverlay: "[data-create-overlay]",
        createForm: "[data-create-form]",
        createErrors: "[data-create-errors]",
        editAction: "[data-edit-action]",
        resignAction: "[data-resign-action]",
        closeEditModal: "[data-close-edit-modal]",
        editOverlay: "[data-edit-overlay]",
        editForm: "[data-edit-form]",
        editErrors: "[data-edit-errors]",
        editEmployeeId: "[data-edit-employee-id]",
        closeResignModal: "[data-close-resign-modal]",
        resignOverlay: "[data-resign-overlay]",
        resignForm: "[data-resign-form]",
        resignEmployeeName: "[data-resign-employee-name]",
        toast: "[data-toast]",
    };

    function $(selector) {
        return root.querySelector(selector);
    }

    function readEmployees() {
        const dataNode = root.querySelector("#managerEmployeeData");

        if (!dataNode) {
            return [];
        }

        try {
            const parsed = JSON.parse(dataNode.textContent || "[]");

            return Array.isArray(parsed) ? parsed.map(normalizeEmployee).filter((employee) => employee.name) : [];
        } catch (error) {
            console.warn("Khong doc duoc du lieu nhan vien tu server.", error);
            return [];
        }
    }

    function normalizeEmployee(employee) {
        return {
            id: Number(employee.id) || 0,
            code: String(employee.code || ""),
            name: String(employee.name || ""),
            phone: employee.phone || "",
            email: employee.email || "",
            branch: {
                id: employee.branch?.id === null || employee.branch?.id === undefined ? null : Number(employee.branch.id),
                name: employee.branch?.name || "",
            },
            position: String(employee.position || "").toUpperCase(),
            positionLabel: employee.positionLabel || positionLabel(employee.position),
            salary: Number(employee.salary) || 0,
            hireDate: employee.hireDate || "",
            birthday: employee.birthday || "",
            experience: employee.experience || "",
            notes: employee.notes || "",
            status: Number(employee.status) === 1 ? 1 : 0,
            statusLabel: employee.statusLabel || "Đang làm",
        };
    }

    function isWorkingEmployee(employee) {
        return Number(employee.status) === 1;
    }

    function positionLabel(position) {
        const normalized = String(position || "").toUpperCase();
        const labels = {
            RECEPTIONIST: "Lễ tân",
            GROOMER: "Groomer",
        };

        return labels[normalized] || "Khác";
    }

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function initials(name) {
        const parts = String(name).trim().split(/\s+/).filter(Boolean);

        if (parts.length >= 2) {
            return `${parts[parts.length - 2][0]}${parts[parts.length - 1][0]}`.toUpperCase();
        }

        return String(name).slice(0, 2).toUpperCase();
    }

    function number(value) {
        return new Intl.NumberFormat("vi-VN").format(Number(value) || 0);
    }

    function formatSalary(value) {
        const salary = Number(value) || 0;

        if (!salary) {
            return "0đ";
        }

        return `${number(salary)}đ`;
    }

    function formatCompactSalary(value) {
        const salary = Number(value) || 0;

        if (!salary) {
            return "0";
        }

        if (salary >= 1000000) {
            return `${(salary / 1000000).toFixed(1).replace(".0", "")}M`;
        }

        return number(salary);
    }

    function formatDate(date) {
        if (!date) {
            return "—";
        }

        const [year, month, day] = String(date).split("-");

        return day && month && year ? `${day}/${month}/${year}` : date;
    }

    function setText(selector, text) {
        const node = $(selector);

        if (node) {
            node.textContent = text;
        }
    }

    function updateStats() {
        const positions = new Set(employees.map((employee) => employee.position).filter(Boolean));
        const salaryList = employees
            .map((employee) => employee.salary)
            .filter((salary) => salary > 0);
        const averageSalary = salaryList.length
            ? salaryList.reduce((sum, salary) => sum + salary, 0) / salaryList.length
            : 0;

        setText(selectors.statTotal, employees.length);
        setText(selectors.statPositions, positions.size);
        setText(selectors.statAvgSalary, formatCompactSalary(averageSalary));
    }

    function renderRow(employee, index) {
        const avatarClass = `manager-employee-avatar--${index % 5}`;

        return `
            <tr>
                <td>
                    <div class="manager-employee-person-cell">
                        <span class="manager-employee-avatar ${avatarClass}">${escapeHtml(initials(employee.name))}</span>
                        <div>
                            <div class="manager-employee-name">${escapeHtml(employee.name)}</div>
                            <div class="manager-employee-code">${escapeHtml(employee.code)}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="manager-employee-contact-main">${escapeHtml(employee.phone || "—")}</div>
                    <div class="manager-employee-muted">${escapeHtml(employee.email || "—")}</div>
                </td>
                <td>
                    <span class="manager-employee-position-badge">${escapeHtml(employee.positionLabel)}</span>
                </td>
                <td>
                    <div class="manager-employee-salary">${formatSalary(employee.salary)}</div>
                    <div class="manager-employee-salary-sub">VND / tháng</div>
                </td>
                <td class="manager-employee-muted">${formatDate(employee.hireDate)}</td>
                <td>
                    <span class="manager-employee-experience">${escapeHtml(employee.experience || "—")}</span>
                </td>
                <td>
                    <span class="manager-employee-status-badge manager-employee-status-badge--working">${escapeHtml(employee.statusLabel)}</span>
                </td>
                <td class="manager-employee-actions-col">
                    <div class="manager-employee-actions">
                        <button type="button" class="manager-employee-action-btn" data-edit-action data-employee-id="${employee.id}" title="Sửa">✎</button>
                        <button type="button" class="manager-employee-action-btn manager-employee-action-btn--resign" data-resign-action data-employee-id="${employee.id}" title="Cho nghỉ việc">−</button>
                    </div>
                </td>
            </tr>
        `;
    }

    function renderTable(list) {
        const tableBody = $(selectors.tableBody);
        const tableCount = $(selectors.tableCount);
        const emptyState = $(selectors.emptyState);

        if (!tableBody || !tableCount || !emptyState) {
            return;
        }

        tableCount.textContent = `${list.length} người`;

        if (!list.length) {
            tableBody.innerHTML = "";
            emptyState.classList.remove("manager-employee-hidden");
            return;
        }

        emptyState.classList.add("manager-employee-hidden");
        tableBody.innerHTML = list.map(renderRow).join("");
    }

    function applyFilters() {
        const query = ($(selectors.searchInput)?.value || "").trim().toLowerCase();
        const position = $(selectors.filterPosition)?.value || "";

        const filtered = employees.filter((employee) => {
            const matchesQuery = !query ||
                employee.name.toLowerCase().includes(query) ||
                employee.code.toLowerCase().includes(query) ||
                employee.phone.toLowerCase().includes(query) ||
                employee.email.toLowerCase().includes(query) ||
                employee.positionLabel.toLowerCase().includes(query);
            const matchesPosition = !position || employee.position === position;

            return matchesQuery && matchesPosition;
        });

        renderTable(filtered);
    }

    function showToast(message) {
        const toast = $(selectors.toast);

        if (!toast) {
            return;
        }

        toast.textContent = message;
        toast.classList.remove("manager-employee-hidden");
        clearTimeout(showToast.timer);
        showToast.timer = setTimeout(() => {
            toast.classList.add("manager-employee-hidden");
        }, 2600);
    }

    function clearErrors(errorBox) {
        if (!errorBox) {
            return;
        }

        errorBox.innerHTML = "";
        errorBox.classList.add("manager-employee-hidden");
    }

    function fieldLabel(field) {
        const labels = {
            full_name: "Họ tên",
            email: "Email",
            password: "Mật khẩu",
            password_confirmation: "Xác nhận mật khẩu",
            phone: "Số điện thoại",
            position: "Vị trí",
            salary: "Lương",
            hire_date: "Ngày vào làm",
            birthday: "Ngày sinh",
            experience: "Kinh nghiệm",
            notes: "Ghi chú",
        };

        return labels[field] || field;
    }

    function showErrors(errorBox, payload) {
        if (!errorBox) {
            return;
        }

        const messages = [];

        if (payload?.errors && typeof payload.errors === "object") {
            Object.entries(payload.errors).forEach(([field, errors]) => {
                (Array.isArray(errors) ? errors : [errors]).forEach((message) => {
                    messages.push(`${fieldLabel(field)}: ${message}`);
                });
            });
        }

        if (!messages.length && payload?.message) {
            messages.push(payload.message);
        }

        errorBox.innerHTML = messages.map((message) => `<div>${escapeHtml(message)}</div>`).join("");
        errorBox.classList.toggle("manager-employee-hidden", !messages.length);
    }

    function setFormLoading(form, loading, loadingText) {
        const submitButton = form?.querySelector('[type="submit"]');

        if (!submitButton) {
            return;
        }

        if (!submitButton.dataset.originalText) {
            submitButton.dataset.originalText = submitButton.textContent;
        }

        submitButton.disabled = loading;
        submitButton.textContent = loading ? loadingText : submitButton.dataset.originalText;
    }

    async function submitForm(form) {
        const response = await fetch(form.action, {
            method: "POST",
            body: new FormData(form),
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        });
        const contentType = response.headers.get("content-type") || "";
        const payload = contentType.includes("application/json")
            ? await response.json()
            : { success: false, message: await response.text() };

        if (!response.ok) {
            const error = new Error(payload.message || "Yêu cầu không thành công.");
            error.payload = payload;
            error.status = response.status;
            throw error;
        }

        return payload;
    }

    function upsertEmployee(employee) {
        const normalized = normalizeEmployee(employee);

        if (!isWorkingEmployee(normalized)) {
            employees = employees.filter((item) => item.id !== normalized.id);
            return;
        }

        const index = employees.findIndex((item) => item.id === normalized.id);

        if (index >= 0) {
            employees[index] = normalized;
            return;
        }

        employees.unshift(normalized);
    }

    function openCreateModal() {
        const overlay = $(selectors.createOverlay);

        if (!overlay) {
            return;
        }

        clearErrors($(selectors.createErrors));
        overlay.classList.add("show");
        overlay.setAttribute("aria-hidden", "false");
    }

    function closeCreateModal() {
        const overlay = $(selectors.createOverlay);

        if (!overlay) {
            return;
        }

        overlay.classList.remove("show");
        overlay.setAttribute("aria-hidden", "true");
    }

    function buildEmployeeUrl(template, employeeId) {
        return String(template || "").replace("__EMPLOYEE_ID__", encodeURIComponent(employeeId));
    }

    function findEmployee(employeeId) {
        return employees.find((employee) => employee.id === employeeId);
    }

    function setField(form, field, value) {
        const input = form.querySelector(`[data-edit-field="${field}"]`);

        if (input) {
            input.value = value ?? "";
        }
    }

    function openEditModal(employee, preserveFormValues = false) {
        const overlay = $(selectors.editOverlay);
        const form = $(selectors.editForm);

        if (!overlay || !form || !employee) {
            return;
        }

        clearErrors($(selectors.editErrors));
        form.action = buildEmployeeUrl(root.dataset.updateUrlTemplate, employee.id);
        const employeeIdInput = $(selectors.editEmployeeId);

        if (employeeIdInput) {
            employeeIdInput.value = employee.id;
        }

        if (!preserveFormValues) {
            setField(form, "name", employee.name);
            setField(form, "phone", employee.phone);
            setField(form, "position", employee.position);
            setField(form, "salary", employee.salary || "");
            setField(form, "hireDate", employee.hireDate);
            setField(form, "birthday", employee.birthday);
            setField(form, "experience", employee.experience);
            setField(form, "notes", employee.notes);
        }

        overlay.classList.add("show");
        overlay.setAttribute("aria-hidden", "false");
    }

    function closeEditModal() {
        const overlay = $(selectors.editOverlay);

        if (!overlay) {
            return;
        }

        overlay.classList.remove("show");
        overlay.setAttribute("aria-hidden", "true");
    }

    function openResignModal(employee) {
        const overlay = $(selectors.resignOverlay);
        const form = $(selectors.resignForm);
        const nameNode = $(selectors.resignEmployeeName);

        if (!overlay || !form || !employee) {
            return;
        }

        form.action = buildEmployeeUrl(root.dataset.resignUrlTemplate, employee.id);

        if (nameNode) {
            nameNode.textContent = employee.name;
        }

        overlay.classList.add("show");
        overlay.setAttribute("aria-hidden", "false");
    }

    function closeResignModal() {
        const overlay = $(selectors.resignOverlay);

        if (!overlay) {
            return;
        }

        overlay.classList.remove("show");
        overlay.setAttribute("aria-hidden", "true");
    }

    function handleEmployeeAction(event) {
        const editButton = event.target.closest(selectors.editAction);
        const resignButton = event.target.closest(selectors.resignAction);
        const button = editButton || resignButton;

        if (!button || !root.contains(button)) {
            return;
        }

        const employee = findEmployee(Number(button.dataset.employeeId) || 0);

        if (editButton) {
            openEditModal(employee);
            return;
        }

        openResignModal(employee);
    }

    async function handleCreateSubmit(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const errorBox = $(selectors.createErrors);

        clearErrors(errorBox);
        setFormLoading(form, true, "Đang tạo...");

        try {
            const payload = await submitForm(form);

            if (payload.employee) {
                upsertEmployee(payload.employee);
            }

            updateStats();
            applyFilters();
            form.reset();
            closeCreateModal();
            showToast(payload.message || "Thêm nhân viên thành công.");
        } catch (error) {
            showErrors(errorBox, error.payload || { message: "Không thể thêm nhân viên." });
        } finally {
            setFormLoading(form, false);
        }
    }

    async function handleEditSubmit(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const errorBox = $(selectors.editErrors);

        clearErrors(errorBox);
        setFormLoading(form, true, "Đang lưu...");

        try {
            const payload = await submitForm(form);

            if (payload.employee) {
                upsertEmployee(payload.employee);
            }

            updateStats();
            applyFilters();
            closeEditModal();
            showToast(payload.message || "Cập nhật thông tin nhân viên thành công.");
        } catch (error) {
            showErrors(errorBox, error.payload || { message: "Không thể cập nhật nhân viên." });
        } finally {
            setFormLoading(form, false);
        }
    }

    async function handleResignSubmit(event) {
        event.preventDefault();

        const form = event.currentTarget;

        setFormLoading(form, true, "Đang xử lý...");

        try {
            const payload = await submitForm(form);
            const resignedId = Number(payload.employee_id) || Number((form.action.match(/employees\/(\d+)\/resign/) || [])[1]) || 0;

            employees = employees.filter((employee) => employee.id !== resignedId);
            updateStats();
            applyFilters();
            closeResignModal();
            showToast(payload.message || "Đã cho nhân viên nghỉ việc.");
        } catch (error) {
            showToast(error.payload?.message || "Không thể cho nhân viên nghỉ việc.");
        } finally {
            setFormLoading(form, false);
        }
    }

    function bindEvents() {
        $(selectors.searchInput)?.addEventListener("input", applyFilters);
        $(selectors.filterPosition)?.addEventListener("change", applyFilters);
        $(selectors.openCreateModal)?.addEventListener("click", openCreateModal);
        $(selectors.createForm)?.addEventListener("submit", handleCreateSubmit);
        $(selectors.editForm)?.addEventListener("submit", handleEditSubmit);
        $(selectors.resignForm)?.addEventListener("submit", handleResignSubmit);
        root.addEventListener("click", handleEmployeeAction);

        root.querySelectorAll(selectors.closeCreateModal).forEach((button) => {
            button.addEventListener("click", closeCreateModal);
        });

        root.querySelectorAll(selectors.closeEditModal).forEach((button) => {
            button.addEventListener("click", closeEditModal);
        });

        root.querySelectorAll(selectors.closeResignModal).forEach((button) => {
            button.addEventListener("click", closeResignModal);
        });

        $(selectors.createOverlay)?.addEventListener("click", (event) => {
            if (event.target === $(selectors.createOverlay)) {
                closeCreateModal();
            }
        });

        $(selectors.editOverlay)?.addEventListener("click", (event) => {
            if (event.target === $(selectors.editOverlay)) {
                closeEditModal();
            }
        });

        $(selectors.resignOverlay)?.addEventListener("click", (event) => {
            if (event.target === $(selectors.resignOverlay)) {
                closeResignModal();
            }
        });
    }

    bindEvents();

    if (root.dataset.createHasErrors === "1") {
        openCreateModal();
    }

    if (root.dataset.editHasErrors === "1") {
        openEditModal(findEmployee(Number(root.dataset.oldEmployeeId) || 0), true);
    }

    updateStats();
    applyFilters();
})();
