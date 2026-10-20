(() => {
    'use strict';

    const app = document.getElementById('inventoryApp');

    if (!app) {
        return;
    }

    const productsUrl = String(app.dataset.productsUrl || '/products').replace(/\/$/, '');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const elements = {
        productForm: document.getElementById('productForm'),
        submitButton: document.getElementById('submitButton'),
        productName: document.getElementById('productName'),
        quantityInStock: document.getElementById('quantityInStock'),
        pricePerItem: document.getElementById('pricePerItem'),
        lineValuePreview: document.getElementById('lineValuePreview'),
        tableRegion: document.getElementById('tableRegion'),
        tableBody: document.getElementById('productsTableBody'),
        tableFoot: document.getElementById('inventoryTableFoot'),
        grandTotalCell: document.getElementById('grandTotalCell'),
        emptyState: document.getElementById('emptyState'),
        loadError: document.getElementById('loadError'),
        loadErrorMessage: document.getElementById('loadErrorMessage'),
        retryButton: document.getElementById('retryButton'),
        grandTotalMetric: document.getElementById('grandTotalMetric'),
        recordCountMetric: document.getElementById('recordCountMetric'),
        totalUnitsMetric: document.getElementById('totalUnitsMetric'),
        recordCountBadge: document.getElementById('recordCountBadge'),
        lastUpdated: document.getElementById('lastUpdated'),
        toast: document.getElementById('appToast'),
        toastTitle: document.getElementById('toastTitle'),
        toastMessage: document.getElementById('toastMessage'),
        toastClose: document.getElementById('toastClose'),
        liveRegion: document.getElementById('appLiveRegion'),
        editModalElement: document.getElementById('editProductModal'),
        editForm: document.getElementById('editProductForm'),
        editSubmitButton: document.getElementById('editSubmitButton'),
        editProductId: document.getElementById('editProductId'),
        editProductName: document.getElementById('editProductName'),
        editQuantityInStock: document.getElementById('editQuantityInStock'),
        editPricePerItem: document.getElementById('editPricePerItem'),
    };

    const createFields = {
        product_name: {
            input: elements.productName,
            error: document.getElementById('productNameError'),
            fallback: 'Enter a product name.',
        },
        quantity_in_stock: {
            input: elements.quantityInStock,
            error: document.getElementById('quantityInStockError'),
            fallback: 'Enter a whole number of 0 or more.',
        },
        price_per_item: {
            input: elements.pricePerItem,
            error: document.getElementById('pricePerItemError'),
            fallback: 'Enter a price of 0 or more.',
        },
    };

    const editFields = {
        product_name: {
            input: elements.editProductName,
            error: document.getElementById('editProductNameError'),
            fallback: 'Enter a product name.',
        },
        quantity_in_stock: {
            input: elements.editQuantityInStock,
            error: document.getElementById('editQuantityInStockError'),
            fallback: 'Enter a whole number of 0 or more.',
        },
        price_per_item: {
            input: elements.editPricePerItem,
            error: document.getElementById('editPricePerItemError'),
            fallback: 'Enter a price of 0 or more.',
        },
    };

    const currencyFormatter = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const integerFormatter = new Intl.NumberFormat('en-US', {
        maximumFractionDigits: 0,
    });

    const timeFormatter = new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit',
    });

    const state = {
        products: [],
        loading: false,
        creating: false,
        editing: false,
        toastTimer: null,
    };

    const editModal = window.bootstrap?.Modal
        ? new window.bootstrap.Modal(elements.editModalElement)
        : null;

    class ValidationError extends Error {
        constructor(message, errors) {
            super(message);
            this.name = 'ValidationError';
            this.errors = errors || {};
        }
    }

    function toNumber(value) {
        const parsed = Number.parseFloat(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function formatCurrency(value) {
        return currencyFormatter.format(toNumber(value));
    }

    function formatInteger(value) {
        return integerFormatter.format(Math.max(0, Math.trunc(toNumber(value))));
    }

    function makeElement(tagName, className, text) {
        const element = document.createElement(tagName);

        if (className) {
            element.className = className;
        }

        if (text !== undefined && text !== null) {
            element.textContent = String(text);
        }

        return element;
    }

    function makeIcon(pathData) {
        const namespace = 'http://www.w3.org/2000/svg';
        const svg = document.createElementNS(namespace, 'svg');
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('aria-hidden', 'true');

        pathData.forEach((definition) => {
            const path = document.createElementNS(namespace, 'path');
            path.setAttribute('d', definition);
            svg.appendChild(path);
        });

        return svg;
    }

    function initialsFor(name) {
        const words = String(name || '')
            .trim()
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2);

        if (!words.length) {
            return 'PR';
        }

        return words.map((word) => word.charAt(0)).join('').toUpperCase();
    }

    function fallbackDate(isoValue) {
        if (!isoValue) {
            return 'Not available';
        }

        const date = new Date(isoValue);

        if (Number.isNaN(date.getTime())) {
            return String(isoValue);
        }

        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        }).format(date);
    }

    function normalizeProduct(product) {
        return {
            id: product?.id,
            product_name: String(product?.product_name ?? ''),
            quantity_in_stock: Math.max(0, Math.trunc(toNumber(product?.quantity_in_stock))),
            price_per_item: Math.max(0, toNumber(product?.price_per_item)),
            total_value: Math.max(
                0,
                toNumber(product?.total_value ?? toNumber(product?.quantity_in_stock) * toNumber(product?.price_per_item)),
            ),
            submitted_at_iso: product?.submitted_at_iso ? String(product.submitted_at_iso) : '',
            submitted_at_display: product?.submitted_at_display
                ? String(product.submitted_at_display)
                : fallbackDate(product?.submitted_at_iso),
        };
    }

    function normalizeSnapshot(payload) {
        const products = Array.isArray(payload?.data)
            ? payload.data.map(normalizeProduct)
            : [];
        const calculated = products.reduce(
            (summary, product) => {
                summary.grandTotal += product.total_value;
                summary.totalUnits += product.quantity_in_stock;
                return summary;
            },
            { grandTotal: 0, totalUnits: 0 },
        );

        return {
            data: products,
            meta: {
                grand_total: toNumber(payload?.meta?.grand_total ?? calculated.grandTotal),
                record_count: Math.max(0, Math.trunc(toNumber(payload?.meta?.record_count ?? products.length))),
                total_units: Math.max(0, Math.trunc(toNumber(payload?.meta?.total_units ?? calculated.totalUnits))),
            },
            message: payload?.message ? String(payload.message) : '',
        };
    }

    async function request(url, options = {}) {
        const headers = new Headers(options.headers || {});
        headers.set('Accept', 'application/json');
        headers.set('X-Requested-With', 'XMLHttpRequest');

        if (csrfToken) {
            headers.set('X-CSRF-TOKEN', csrfToken);
        }

        if (options.body !== undefined) {
            headers.set('Content-Type', 'application/json');
        }

        const response = await fetch(url, {
            ...options,
            headers,
            credentials: 'same-origin',
        });

        const responseText = await response.text();
        let payload = {};

        if (responseText) {
            try {
                payload = JSON.parse(responseText);
            } catch (error) {
                payload = {};
            }
        }

        if (response.status === 422) {
            throw new ValidationError(
                String(payload.message || 'Please review the highlighted fields.'),
                payload.errors,
            );
        }

        if (!response.ok) {
            const fallback = response.status === 419
                ? 'Your session expired. Refresh the page and try again.'
                : 'Something went wrong while contacting the server.';
            throw new Error(String(payload.message || fallback));
        }

        return payload;
    }

    function announce(message) {
        elements.liveRegion.textContent = '';
        window.setTimeout(() => {
            elements.liveRegion.textContent = message;
        }, 30);
    }

    function hideToast() {
        window.clearTimeout(state.toastTimer);
        elements.toast.classList.remove('is-visible');
        elements.toast.setAttribute('aria-hidden', 'true');
    }

    function showToast(title, message, type = 'success') {
        window.clearTimeout(state.toastTimer);
        elements.toastTitle.textContent = title;
        elements.toastMessage.textContent = message;
        elements.toast.classList.toggle('is-error', type === 'error');
        elements.toast.classList.add('is-visible');
        elements.toast.setAttribute('aria-hidden', 'false');
        announce(`${title}. ${message}`);

        state.toastTimer = window.setTimeout(hideToast, 5000);
    }

    function setButtonLoading(button, isLoading) {
        button.disabled = isLoading;
        button.classList.toggle('is-loading', isLoading);
        button.setAttribute('aria-busy', String(isLoading));
    }

    function clearValidation(form, fields) {
        form.classList.remove('was-validated');

        Object.values(fields).forEach(({ input, error }) => {
            input.classList.remove('is-invalid');
            input.removeAttribute('aria-invalid');
            error.textContent = '';
        });
    }

    function validateForm(form, fields) {
        clearValidation(form, fields);

        if (form.checkValidity()) {
            return true;
        }

        form.classList.add('was-validated');
        let firstInvalid = null;

        Object.values(fields).forEach((field) => {
            if (!field.input.validity.valid) {
                field.error.textContent = field.fallback;
                firstInvalid ||= field.input;
            }
        });

        firstInvalid?.focus();
        announce('Please review the highlighted fields.');
        return false;
    }

    function applyValidationErrors(form, fields, errors) {
        clearValidation(form, fields);
        let firstInvalid = null;

        Object.entries(errors || {}).forEach(([fieldName, messages]) => {
            const field = fields[fieldName];

            if (!field) {
                return;
            }

            const message = Array.isArray(messages) ? messages[0] : messages;
            field.input.classList.add('is-invalid');
            field.input.setAttribute('aria-invalid', 'true');
            field.error.textContent = String(message || field.fallback);
            firstInvalid ||= field.input;
        });

        firstInvalid?.focus();
    }

    function formPayload(fields) {
        return {
            product_name: fields.product_name.input.value.trim(),
            quantity_in_stock: fields.quantity_in_stock.input.value,
            price_per_item: fields.price_per_item.input.value,
        };
    }

    function showSkeleton() {
        elements.loadError.classList.add('d-none');
        elements.emptyState.classList.add('d-none');
        elements.tableRegion.classList.remove('d-none');
        elements.tableRegion.setAttribute('aria-busy', 'true');
        elements.tableFoot.classList.add('d-none');
        elements.tableBody.replaceChildren();

        for (let rowIndex = 0; rowIndex < 3; rowIndex += 1) {
            const row = makeElement('tr', 'skeleton-row');
            row.setAttribute('aria-hidden', 'true');
            const classes = ['skeleton-product', 'skeleton-short', 'skeleton-short', 'skeleton-date', 'skeleton-short', 'skeleton-action'];

            classes.forEach((className) => {
                const cell = makeElement('td');
                cell.appendChild(makeElement('span', `skeleton ${className}`));
                row.appendChild(cell);
            });

            elements.tableBody.appendChild(row);
        }
    }

    function makeProductRow(product) {
        const row = makeElement('tr');
        row.dataset.productId = String(product.id ?? '');

        const nameCell = makeElement('td');
        nameCell.dataset.label = 'Product name';
        const identity = makeElement('div', 'product-identity');
        const avatar = makeElement('span', 'product-avatar', initialsFor(product.product_name));
        avatar.setAttribute('aria-hidden', 'true');
        const copy = makeElement('span', 'product-copy');
        const name = makeElement('span', 'product-name', product.product_name || 'Unnamed product');
        name.title = product.product_name || 'Unnamed product';
        const reference = makeElement('small', '', product.id === undefined || product.id === null ? 'Inventory item' : `Item #${product.id}`);
        copy.append(name, reference);
        identity.append(avatar, copy);
        nameCell.appendChild(identity);

        const quantityCell = makeElement('td', 'text-end');
        quantityCell.dataset.label = 'Quantity';
        const stock = makeElement('span', 'stock-pill', formatInteger(product.quantity_in_stock));
        if (product.quantity_in_stock === 0) {
            stock.classList.add('out-of-stock');
            stock.title = 'Out of stock';
        } else {
            stock.title = `${formatInteger(product.quantity_in_stock)} units in stock`;
        }
        quantityCell.appendChild(stock);

        const priceCell = makeElement('td', 'text-end');
        priceCell.dataset.label = 'Price per item';
        priceCell.appendChild(makeElement('span', 'money', formatCurrency(product.price_per_item)));

        const submittedCell = makeElement('td');
        submittedCell.dataset.label = 'Submitted';
        const submitted = makeElement('span', 'submitted-time');
        const time = makeElement('time', '', product.submitted_at_display || fallbackDate(product.submitted_at_iso));
        if (product.submitted_at_iso) {
            time.dateTime = product.submitted_at_iso;
        }
        submitted.appendChild(time);
        submittedCell.appendChild(submitted);

        const totalCell = makeElement('td', 'text-end');
        totalCell.dataset.label = 'Total value';
        totalCell.appendChild(makeElement('span', 'money total', formatCurrency(product.total_value)));

        const actionCell = makeElement('td', 'text-end');
        actionCell.dataset.label = 'Actions';
        const editButton = makeElement('button', 'btn edit-button');
        editButton.type = 'button';
        editButton.dataset.action = 'edit';
        editButton.dataset.productId = String(product.id ?? '');
        editButton.setAttribute('aria-label', `Edit ${product.product_name || 'product'}`);
        editButton.title = 'Edit product';
        editButton.appendChild(makeIcon([
            'M13.5 6.5 17.5 10.5',
            'm4 20 4.5-1 10-10a2.8 2.8 0 0 0-4-4l-10 10L4 20Z',
        ]));
        actionCell.appendChild(editButton);

        row.append(nameCell, quantityCell, priceCell, submittedCell, totalCell, actionCell);
        return row;
    }

    function updateMetrics(meta) {
        elements.grandTotalMetric.textContent = formatCurrency(meta.grand_total);
        elements.recordCountMetric.textContent = formatInteger(meta.record_count);
        elements.totalUnitsMetric.textContent = formatInteger(meta.total_units);
        elements.grandTotalCell.textContent = formatCurrency(meta.grand_total);
        elements.recordCountBadge.textContent = `${formatInteger(meta.record_count)} ${meta.record_count === 1 ? 'item' : 'items'}`;
    }

    function markUpdated() {
        elements.lastUpdated.textContent = `Updated ${timeFormatter.format(new Date())}`;
    }

    function renderSnapshot(payload) {
        const snapshot = normalizeSnapshot(payload);
        state.products = snapshot.data;
        elements.tableBody.replaceChildren();
        updateMetrics(snapshot.meta);
        elements.tableRegion.setAttribute('aria-busy', 'false');
        elements.loadError.classList.add('d-none');

        if (!snapshot.data.length) {
            elements.tableRegion.classList.add('d-none');
            elements.tableFoot.classList.add('d-none');
            elements.emptyState.classList.remove('d-none');
        } else {
            const fragment = document.createDocumentFragment();
            snapshot.data.forEach((product) => fragment.appendChild(makeProductRow(product)));
            elements.tableBody.appendChild(fragment);
            elements.tableRegion.classList.remove('d-none');
            elements.tableFoot.classList.remove('d-none');
            elements.emptyState.classList.add('d-none');
        }

        markUpdated();
        return snapshot;
    }

    function displayLoadError(error) {
        elements.tableRegion.setAttribute('aria-busy', 'false');
        elements.tableRegion.classList.add('d-none');
        elements.emptyState.classList.add('d-none');
        elements.tableFoot.classList.add('d-none');
        elements.loadErrorMessage.textContent = error.message || 'Please try again.';
        elements.loadError.classList.remove('d-none');
        elements.lastUpdated.textContent = 'Connection issue';
        announce(`Inventory unavailable. ${error.message || 'Please try again.'}`);
    }

    async function loadProducts() {
        if (state.loading) {
            return;
        }

        state.loading = true;
        elements.retryButton.disabled = true;
        showSkeleton();

        try {
            const payload = await request(productsUrl);
            renderSnapshot(payload);
        } catch (error) {
            displayLoadError(error);
        } finally {
            state.loading = false;
            elements.retryButton.disabled = false;
        }
    }

    function updateLinePreview() {
        const quantity = Math.max(0, toNumber(elements.quantityInStock.value));
        const price = Math.max(0, toNumber(elements.pricePerItem.value));
        elements.lineValuePreview.textContent = formatCurrency(quantity * price);
    }

    async function createProduct(event) {
        event.preventDefault();

        if (state.creating || !validateForm(elements.productForm, createFields)) {
            return;
        }

        state.creating = true;
        setButtonLoading(elements.submitButton, true);

        try {
            const payload = await request(productsUrl, {
                method: 'POST',
                body: JSON.stringify(formPayload(createFields)),
            });
            const snapshot = renderSnapshot(payload);
            elements.productForm.reset();
            clearValidation(elements.productForm, createFields);
            updateLinePreview();
            showToast('Product added', snapshot.message || 'The inventory ledger has been updated.');
            elements.productName.focus();
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(elements.productForm, createFields, error.errors);
                showToast('Check the form', error.message, 'error');
            } else {
                showToast('Product not saved', error.message || 'Please try again.', 'error');
            }
        } finally {
            state.creating = false;
            setButtonLoading(elements.submitButton, false);
        }
    }

    function openEditModal(productId) {
        const product = state.products.find((item) => String(item.id) === String(productId));

        if (!product) {
            showToast('Product unavailable', 'Refresh the inventory and try again.', 'error');
            return;
        }

        clearValidation(elements.editForm, editFields);
        elements.editProductId.value = String(product.id ?? '');
        elements.editProductName.value = product.product_name;
        elements.editQuantityInStock.value = String(product.quantity_in_stock);
        elements.editPricePerItem.value = product.price_per_item.toFixed(2);

        if (editModal) {
            editModal.show();
        } else {
            showToast('Editor unavailable', 'Reload the page to restore the editor.', 'error');
        }
    }

    async function updateProduct(event) {
        event.preventDefault();

        if (state.editing || !validateForm(elements.editForm, editFields)) {
            return;
        }

        const productId = elements.editProductId.value;

        if (!productId) {
            showToast('Product unavailable', 'Close the editor and select the product again.', 'error');
            return;
        }

        state.editing = true;
        setButtonLoading(elements.editSubmitButton, true);

        try {
            const payload = await request(`${productsUrl}/${encodeURIComponent(productId)}`, {
                method: 'PATCH',
                body: JSON.stringify(formPayload(editFields)),
            });
            const snapshot = renderSnapshot(payload);
            editModal?.hide();
            showToast('Changes saved', snapshot.message || 'The product record is up to date.');
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(elements.editForm, editFields, error.errors);
                showToast('Check the form', error.message, 'error');
            } else {
                showToast('Changes not saved', error.message || 'Please try again.', 'error');
            }
        } finally {
            state.editing = false;
            setButtonLoading(elements.editSubmitButton, false);
        }
    }

    elements.productForm.addEventListener('submit', createProduct);
    elements.editForm.addEventListener('submit', updateProduct);
    elements.retryButton.addEventListener('click', loadProducts);
    elements.toastClose.addEventListener('click', hideToast);

    [elements.quantityInStock, elements.pricePerItem].forEach((input) => {
        input.addEventListener('input', updateLinePreview);
    });

    elements.tableBody.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action="edit"]');

        if (button) {
            openEditModal(button.dataset.productId);
        }
    });

    [...Object.values(createFields), ...Object.values(editFields)].forEach(({ input, error }) => {
        input.addEventListener('input', () => {
            if (input.classList.contains('is-invalid')) {
                input.classList.remove('is-invalid');
                input.removeAttribute('aria-invalid');
                error.textContent = '';
            }
        });
    });

    elements.editModalElement.addEventListener('shown.bs.modal', () => {
        elements.editProductName.focus();
    });

    elements.editModalElement.addEventListener('hidden.bs.modal', () => {
        elements.editForm.reset();
        clearValidation(elements.editForm, editFields);
        elements.editProductId.value = '';
    });

    loadProducts();
})();
