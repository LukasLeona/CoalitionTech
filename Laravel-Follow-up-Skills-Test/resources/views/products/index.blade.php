{{-- GitHub Repository: https://github.com/LukasLeona/CoalitionTech --}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Product Inventory</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<nav class="navbar bg-white border-bottom">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="{{ url('/') }}">Product Inventory</a>
    </div>
</nav>

<main
    id="inventoryApp"
    class="container py-4 py-lg-5"
    data-products-url="{{ url('/products') }}"
>
    <div class="mb-4">
        <h1 class="h3 mb-1">Inventory</h1>
        <p class="text-secondary mb-0">Add products and track their stock value.</p>
    </div>

    <div id="loadError" class="alert alert-danger d-none" role="alert">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span id="loadErrorMessage">Products could not be loaded.</span>
            <button id="retryButton" class="btn btn-sm btn-outline-danger" type="button">Retry</button>
        </div>
    </div>

    <section class="card inventory-card mb-4" aria-labelledby="addProductTitle">
        <div class="card-body p-4">
            <h2 id="addProductTitle" class="h5 mb-3">Add product</h2>

            <form id="productForm" novalidate>
                @csrf
                <div class="row g-3 align-items-start">
                    <div class="col-lg-5">
                        <label class="form-label" for="productName">Product name</label>
                        <input
                            id="productName"
                            class="form-control"
                            name="product_name"
                            type="text"
                            maxlength="255"
                            autocomplete="off"
                            aria-describedby="productNameError"
                            required
                        >
                        <div id="productNameError" class="invalid-feedback">Enter a product name.</div>
                    </div>

                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label" for="quantityInStock">Quantity in stock</label>
                        <input
                            id="quantityInStock"
                            class="form-control"
                            name="quantity_in_stock"
                            type="number"
                            min="0"
                            step="1"
                            inputmode="numeric"
                            aria-describedby="quantityInStockError"
                            required
                        >
                        <div id="quantityInStockError" class="invalid-feedback">Enter a whole number of 0 or more.</div>
                    </div>

                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label" for="pricePerItem">Price per item</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">$</span>
                            <input
                                id="pricePerItem"
                                class="form-control"
                                name="price_per_item"
                                type="number"
                                min="0"
                                step="0.01"
                                inputmode="decimal"
                                aria-describedby="pricePerItemError"
                                required
                            >
                            <div id="pricePerItemError" class="invalid-feedback">Enter a price of 0 or more.</div>
                        </div>
                    </div>

                    <div class="col-lg-3 d-grid form-button-column">
                        <button id="submitButton" class="btn btn-primary" type="submit">Add product</button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <section class="card inventory-card" aria-labelledby="productsTitle">
        <div class="card-header bg-white d-flex align-items-center justify-content-between gap-3 px-4 py-3">
            <h2 id="productsTitle" class="h5 mb-0">Products</h2>
            <span id="productCount" class="text-secondary small">0 products</span>
        </div>

        <div class="table-responsive">
            <table class="table inventory-table align-middle mb-0">
                <caption class="visually-hidden">Products ordered by submission date.</caption>
                <thead>
                    <tr>
                        <th scope="col">Product name</th>
                        <th scope="col" class="text-end">Quantity in stock</th>
                        <th scope="col" class="text-end">Price per item</th>
                        <th scope="col">Datetime submitted</th>
                        <th scope="col" class="text-end">Total value</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                    <tr>
                        <td class="text-center text-secondary py-5" colspan="6">Loading products...</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th scope="row" colspan="4">Grand total</th>
                        <td id="grandTotalCell" class="text-end fw-semibold">$0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>
</main>

<div id="feedbackAlert" class="feedback-alert alert d-none" role="status" aria-live="polite"></div>

<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editProductForm" novalidate>
                @csrf
                <input id="editProductId" type="hidden">

                <div class="modal-header">
                    <h2 id="editProductTitle" class="modal-title fs-5">Edit product</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="editProductName">Product name</label>
                        <input id="editProductName" class="form-control" name="product_name" type="text" maxlength="255" aria-describedby="editProductNameError" required>
                        <div id="editProductNameError" class="invalid-feedback">Enter a product name.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="editQuantityInStock">Quantity in stock</label>
                            <input id="editQuantityInStock" class="form-control" name="quantity_in_stock" type="number" min="0" step="1" inputmode="numeric" aria-describedby="editQuantityInStockError" required>
                            <div id="editQuantityInStockError" class="invalid-feedback">Enter a whole number of 0 or more.</div>
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label" for="editPricePerItem">Price per item</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">$</span>
                                <input id="editPricePerItem" class="form-control" name="price_per_item" type="number" min="0" step="0.01" inputmode="decimal" aria-describedby="editPricePerItemError" required>
                                <div id="editPricePerItemError" class="invalid-feedback">Enter a price of 0 or more.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button id="editSubmitButton" type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
