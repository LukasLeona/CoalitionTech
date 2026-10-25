(() => {
    'use strict';

    const app = document.getElementById('inventoryApp');

    if (!app) {
        return;
    }

    const productsUrl = app.dataset.productsUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const currency = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    });

    const elements = {
        createForm: document.getElementById('productForm'),
        createButton: document.getElementById('submitButton'),
        tableBody: document.getElementById('productsTableBody'),
        grandTotal: document.getElementById('grandTotalCell'),
        productCount: document.getElementById('productCount'),
        loadError: document.getElementById('loadError'),
        loadErrorMessage: document.getElementById('loadErrorMessage'),
        retryButton: document.getElementById('retryButton'),
        feedback: document.getElementById('feedbackAlert'),
        editModalElement: document.getElementById('editProductModal'),
        editForm: document.getElementById('editProductForm'),
        editButton: document.getElementById('editSubmitButton'),
        editId: document.getElementById('editProductId'),
    };

    const createFields = {
        product_name: field('productName', 'productNameError'),
        quantity_in_stock: field('quantityInStock', 'quantityInStockError'),
        price_per_item: field('pricePerItem', 'pricePerItemError'),
    };

    const editFields = {
        product_name: field('editProductName', 'editProductNameError'),
        quantity_in_stock: field('editQuantityInStock', 'editQuantityInStockError'),
        price_per_item: field('editPricePerItem', 'editPricePerItemError'),
    };

    const state = {
        products: [],
        feedbackTimer: null,
    };

    const editModal = window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(elements.editModalElement)
        : null;

    class ValidationError extends Error {
        constructor(message, errors) {
            super(message);
            this.errors = errors ?? {};
        }
    }

    function field(inputId, errorId) {
        return {
            input: document.getElementById(inputId),
            error: document.getElementById(errorId),
        };
    }

    function productPayload(fields) {
        return {
            product_name: fields.product_name.input.value.trim(),
            quantity_in_stock: fields.quantity_in_stock.input.value,
            price_per_item: fields.price_per_item.input.value,
        };
    }

    async function request(url, options = {}) {
        const headers = new Headers(options.headers ?? {});
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
        const payload = await response.json().catch(() => ({}));

        if (response.status === 422) {
            throw new ValidationError(payload.message || 'Please check the form.', payload.errors);
        }

        if (!response.ok) {
            throw new Error(payload.message || 'The request could not be completed.');
        }

        return payload;
    }

    function setButtonLoading(button, loading, idleText, loadingText) {
        button.disabled = loading;
        button.textContent = loading ? loadingText : idleText;
    }

    function clearErrors(form, fields) {
        form.classList.remove('was-validated');

        Object.values(fields).forEach(({ input, error }) => {
            input.classList.remove('is-invalid');
            input.removeAttribute('aria-invalid');
            error.textContent = error.dataset.defaultMessage || error.textContent;
        });
    }

    function validate(form) {
        form.classList.add('was-validated');

        if (form.checkValidity()) {
            return true;
        }

        form.querySelector(':invalid')?.focus();
        return false;
    }

    function applyErrors(form, fields, errors) {
        clearErrors(form, fields);

        Object.entries(errors ?? {}).forEach(([name, messages]) => {
            const current = fields[name];

            if (!current) {
                return;
            }

            current.input.classList.add('is-invalid');
            current.input.setAttribute('aria-invalid', 'true');
            current.error.textContent = Array.isArray(messages) ? messages[0] : String(messages);
        });

        form.querySelector('.is-invalid')?.focus();
    }

    function showFeedback(message, type = 'success') {
        window.clearTimeout(state.feedbackTimer);
        elements.feedback.textContent = message;
        elements.feedback.className = `feedback-alert alert alert-${type}`;
        state.feedbackTimer = window.setTimeout(() => {
            elements.feedback.classList.add('d-none');
        }, 3500);
    }

    function normalizeProduct(product) {
        const quantity = Number(product.quantity_in_stock) || 0;
        const price = Number(product.price_per_item) || 0;

        return {
            id: product.id,
            product_name: String(product.product_name || ''),
            quantity_in_stock: quantity,
            price_per_item: price,
            total_value: Number(product.total_value ?? quantity * price) || 0,
            submitted_at_display: String(product.submitted_at_display || ''),
        };
    }

    function cell(text, className = '') {
        const element = document.createElement('td');
        element.className = className;
        element.textContent = text;
        return element;
    }

    function productRow(product) {
        const row = document.createElement('tr');
        const nameCell = cell(product.product_name);
        nameCell.classList.add('product-name');

        const editButton = document.createElement('button');
        editButton.type = 'button';
        editButton.className = 'btn btn-sm btn-outline-secondary edit-button';
        editButton.dataset.action = 'edit';
        editButton.dataset.productId = product.id;
        editButton.textContent = 'Edit';

        const actionCell = document.createElement('td');
        actionCell.className = 'text-end';
        actionCell.appendChild(editButton);

        row.append(
            nameCell,
            cell(String(product.quantity_in_stock), 'text-end'),
            cell(currency.format(product.price_per_item), 'text-end money'),
            cell(product.submitted_at_display || '—'),
            cell(currency.format(product.total_value), 'text-end money'),
            actionCell,
        );

        return row;
    }

    function render(payload) {
        const products = Array.isArray(payload.data)
            ? payload.data.map(normalizeProduct)
            : [];
        const suppliedTotal = Number(payload.meta?.grand_total);
        const total = Number.isFinite(suppliedTotal)
            ? suppliedTotal
            : products.reduce((sum, product) => sum + product.total_value, 0);

        state.products = products;
        elements.tableBody.replaceChildren();
        elements.productCount.textContent = `${products.length} ${products.length === 1 ? 'product' : 'products'}`;
        elements.grandTotal.textContent = currency.format(total);
        elements.loadError.classList.add('d-none');

        if (!products.length) {
            const row = document.createElement('tr');
            const empty = cell('No products have been submitted.', 'text-center text-secondary py-5');
            empty.colSpan = 6;
            row.appendChild(empty);
            elements.tableBody.appendChild(row);
            return;
        }

        const fragment = document.createDocumentFragment();
        products.forEach((product) => fragment.appendChild(productRow(product)));
        elements.tableBody.appendChild(fragment);
    }

    async function loadProducts() {
        elements.retryButton.disabled = true;

        try {
            render(await request(productsUrl));
        } catch (error) {
            elements.loadErrorMessage.textContent = error.message || 'Products could not be loaded.';
            elements.loadError.classList.remove('d-none');
        } finally {
            elements.retryButton.disabled = false;
        }
    }

    async function createProduct(event) {
        event.preventDefault();

        if (!validate(elements.createForm)) {
            return;
        }

        setButtonLoading(elements.createButton, true, 'Add product', 'Saving...');

        try {
            const payload = await request(productsUrl, {
                method: 'POST',
                body: JSON.stringify(productPayload(createFields)),
            });
            render(payload);
            elements.createForm.reset();
            clearErrors(elements.createForm, createFields);
            showFeedback(payload.message || 'Product added.');
        } catch (error) {
            if (error instanceof ValidationError) {
                applyErrors(elements.createForm, createFields, error.errors);
            }
            showFeedback(error.message || 'Product could not be added.', 'danger');
        } finally {
            setButtonLoading(elements.createButton, false, 'Add product', 'Saving...');
        }
    }

    function openEdit(productId) {
        const product = state.products.find((item) => String(item.id) === String(productId));

        if (!product || !editModal) {
            showFeedback('Product could not be opened.', 'danger');
            return;
        }

        clearErrors(elements.editForm, editFields);
        elements.editId.value = product.id;
        editFields.product_name.input.value = product.product_name;
        editFields.quantity_in_stock.input.value = product.quantity_in_stock;
        editFields.price_per_item.input.value = product.price_per_item.toFixed(2);
        editModal.show();
    }

    async function updateProduct(event) {
        event.preventDefault();

        if (!validate(elements.editForm)) {
            return;
        }

        setButtonLoading(elements.editButton, true, 'Save changes', 'Saving...');

        try {
            const payload = await request(`${productsUrl}/${encodeURIComponent(elements.editId.value)}`, {
                method: 'PATCH',
                body: JSON.stringify(productPayload(editFields)),
            });
            render(payload);
            editModal.hide();
            showFeedback(payload.message || 'Product updated.');
        } catch (error) {
            if (error instanceof ValidationError) {
                applyErrors(elements.editForm, editFields, error.errors);
            }
            showFeedback(error.message || 'Product could not be updated.', 'danger');
        } finally {
            setButtonLoading(elements.editButton, false, 'Save changes', 'Saving...');
        }
    }

    [...Object.values(createFields), ...Object.values(editFields)].forEach(({ input, error }) => {
        error.dataset.defaultMessage = error.textContent;
        input.addEventListener('input', () => {
            input.classList.remove('is-invalid');
            input.removeAttribute('aria-invalid');
            error.textContent = error.dataset.defaultMessage;
        });
    });

    elements.createForm.addEventListener('submit', createProduct);
    elements.editForm.addEventListener('submit', updateProduct);
    elements.retryButton.addEventListener('click', loadProducts);
    elements.tableBody.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action="edit"]');

        if (button) {
            openEdit(button.dataset.productId);
        }
    });

    loadProducts();
})();
