{{-- GitHub Repository: https://github.com/LukasLeona/CoalitionTech --}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#102a2e">
    <title>Inventory Studio</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>

<div
    id="inventoryApp"
    class="app-shell"
    data-products-url="{{ url('/products') }}"
>
    <aside class="app-sidebar" aria-label="Primary navigation">
        <div class="sidebar-inner">
            <a class="brand" href="{{ url('/') }}" aria-label="Inventory Studio home">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" role="img">
                        <path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"></path>
                        <path d="m4.4 7.7 7.6 4.2 7.6-4.2M12 12v8.6"></path>
                    </svg>
                </span>
                <span>
                    <strong>Inventory</strong>
                    <small>Studio</small>
                </span>
            </a>

            <nav class="sidebar-nav" aria-label="Workspace">
                <span class="nav-label">Workspace</span>
                <a class="sidebar-link active" href="#inventory" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="3" width="7" height="7" rx="2"></rect>
                        <rect x="14" y="3" width="7" height="7" rx="2"></rect>
                        <rect x="3" y="14" width="7" height="7" rx="2"></rect>
                        <rect x="14" y="14" width="7" height="7" rx="2"></rect>
                    </svg>
                    Inventory
                    <span class="active-indicator" aria-hidden="true"></span>
                </a>
                <span class="sidebar-link sidebar-link-muted" aria-disabled="true">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 19V9m5 10V5m6 14v-7m5 7V3"></path>
                    </svg>
                    Insights
                </span>
                <span class="sidebar-link sidebar-link-muted" aria-disabled="true">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 4h16v16H4zM4 9h16M9 4v16"></path>
                    </svg>
                    Reports
                </span>
            </nav>

            <div class="sidebar-card">
                <span class="sidebar-card-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 3v3m0 12v3M3 12h3m12 0h3M5.6 5.6l2.1 2.1m8.6 8.6 2.1 2.1m0-12.8-2.1 2.1m-8.6 8.6-2.1 2.1"></path>
                        <circle cx="12" cy="12" r="4"></circle>
                    </svg>
                </span>
                <div>
                    <strong>Live workspace</strong>
                    <span>Changes sync instantly.</span>
                </div>
            </div>

            <div class="sidebar-footer">
                <span class="status-dot" aria-hidden="true"></span>
                <span><strong>System online</strong><small>Ready for updates</small></span>
            </div>
        </div>
    </aside>

    <main id="main-content" class="app-main" tabindex="-1">
        <header class="page-header">
            <div>
                <div class="eyebrow"><span></span> Stock control</div>
                <h1>Product inventory</h1>
                <p>Track stock, pricing, and portfolio value from one focused workspace.</p>
            </div>
            <div class="header-actions">
                <div class="sync-status" title="Data is loaded directly from the server">
                    <span class="sync-pulse" aria-hidden="true"></span>
                    <span><strong>Live data</strong><small id="lastUpdated">Connecting…</small></span>
                </div>
                <a class="btn btn-accent btn-header" href="#add-product">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
                    Add product
                </a>
            </div>
        </header>

        <section class="metrics-grid" aria-label="Inventory summary">
            <article class="metric-card metric-primary">
                <div class="metric-topline">
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M4 6.5 12 3l8 3.5v11L12 21l-8-3.5v-11Z"></path><path d="m4 7 8 4 8-4M12 11v10"></path></svg>
                    </span>
                    <span class="metric-kicker">Portfolio value</span>
                </div>
                <strong id="grandTotalMetric" class="metric-value">$0.00</strong>
                <span class="metric-caption">Across all inventory</span>
                <span class="metric-glow" aria-hidden="true"></span>
            </article>

            <article class="metric-card">
                <div class="metric-topline">
                    <span class="metric-icon metric-icon-coral" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M4 7h16v12H4zM7 4h10v3M8 11h8M8 15h5"></path></svg>
                    </span>
                    <span class="metric-kicker">Products</span>
                </div>
                <strong id="recordCountMetric" class="metric-value">0</strong>
                <span class="metric-caption">Active records</span>
            </article>

            <article class="metric-card">
                <div class="metric-topline">
                    <span class="metric-icon metric-icon-sky" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5v-7Z"></path><path d="m4 9 8 4 8-4"></path></svg>
                    </span>
                    <span class="metric-kicker">Units in stock</span>
                </div>
                <strong id="totalUnitsMetric" class="metric-value">0</strong>
                <span class="metric-caption">Ready to fulfill</span>
            </article>
        </section>

        <div id="loadError" class="alert app-alert d-none" role="alert">
            <div>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5m0 3h.01M10.3 4.5 2.8 18a2 2 0 0 0 1.8 3h14.8a2 2 0 0 0 1.8-3L13.7 4.5a2 2 0 0 0-3.4 0Z"></path></svg>
                <span><strong>Inventory unavailable.</strong> <span id="loadErrorMessage">Please try again.</span></span>
            </div>
            <button id="retryButton" type="button" class="btn btn-sm btn-light">Retry</button>
        </div>

        <div class="content-grid">
            <section id="add-product" class="surface form-surface" aria-labelledby="addProductTitle">
                <div class="surface-header">
                    <span class="surface-number" aria-hidden="true">01</span>
                    <div>
                        <span class="section-kicker">New entry</span>
                        <h2 id="addProductTitle">Add a product</h2>
                        <p>Create a new inventory record.</p>
                    </div>
                </div>

                <form id="productForm" novalidate>
                    @csrf
                    <div class="mb-4">
                        <label class="form-label" for="productName">Product name</label>
                        <div class="input-shell">
                            <span class="input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"></path><path d="m4.4 7.7 7.6 4.2 7.6-4.2M12 12v8.6"></path></svg>
                            </span>
                            <input
                                id="productName"
                                class="form-control"
                                name="product_name"
                                type="text"
                                maxlength="255"
                                autocomplete="off"
                                placeholder="e.g. Studio headphones"
                                aria-describedby="productNameError"
                                required
                            >
                        </div>
                        <div id="productNameError" class="invalid-feedback" role="alert"></div>
                    </div>

                    <div class="form-row-split">
                        <div>
                            <label class="form-label" for="quantityInStock">Quantity in stock</label>
                            <div class="input-shell">
                                <span class="input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M5 8h14v12H5zM8 4h8v4M9 12h6"></path></svg>
                                </span>
                                <input
                                    id="quantityInStock"
                                    class="form-control"
                                    name="quantity_in_stock"
                                    type="number"
                                    min="0"
                                    step="1"
                                    inputmode="numeric"
                                    placeholder="0"
                                    aria-describedby="quantityInStockError"
                                    required
                                >
                            </div>
                            <div id="quantityInStockError" class="invalid-feedback" role="alert"></div>
                        </div>

                        <div>
                            <label class="form-label" for="pricePerItem">Price per item</label>
                            <div class="input-shell input-shell-prefix">
                                <span class="input-prefix" aria-hidden="true">$</span>
                                <input
                                    id="pricePerItem"
                                    class="form-control"
                                    name="price_per_item"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    inputmode="decimal"
                                    placeholder="0.00"
                                    aria-describedby="pricePerItemError"
                                    required
                                >
                            </div>
                            <div id="pricePerItemError" class="invalid-feedback" role="alert"></div>
                        </div>
                    </div>

                    <div class="value-preview" aria-live="polite">
                        <span>
                            <small>Estimated line value</small>
                            <strong id="lineValuePreview">$0.00</strong>
                        </span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-5-5 5 5-5 5"></path></svg>
                    </div>

                    <button id="submitButton" class="btn btn-accent btn-submit" type="submit">
                        <span class="button-idle">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
                            Add to inventory
                        </span>
                        <span class="button-loading" aria-hidden="true">
                            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                            Saving product…
                        </span>
                    </button>
                </form>
            </section>

            <section id="inventory" class="surface table-surface" aria-labelledby="inventoryTitle">
                <div class="table-toolbar">
                    <div>
                        <span class="section-kicker">Current stock</span>
                        <div class="title-row">
                            <h2 id="inventoryTitle">Inventory ledger</h2>
                            <span id="recordCountBadge" class="count-badge">0 items</span>
                        </div>
                        <p>Newest submissions appear first.</p>
                    </div>
                    <div class="table-status" aria-hidden="true">
                        <span></span> Live
                    </div>
                </div>

                <div id="tableRegion" class="inventory-table-wrap" aria-live="polite" aria-busy="true">
                    <table class="table inventory-table align-middle mb-0">
                        <caption class="visually-hidden">Products ordered by submission date, with quantity, unit price, submission time, and total value.</caption>
                        <thead>
                            <tr>
                                <th scope="col">Product name</th>
                                <th scope="col" class="text-end">Quantity in stock</th>
                                <th scope="col" class="text-end">Price per item</th>
                                <th scope="col">Datetime submitted</th>
                                <th scope="col" class="text-end">Total value</th>
                                <th scope="col" class="action-heading"><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <tr class="skeleton-row" aria-hidden="true">
                                <td><span class="skeleton skeleton-product"></span></td>
                                <td><span class="skeleton skeleton-short"></span></td>
                                <td><span class="skeleton skeleton-short"></span></td>
                                <td><span class="skeleton skeleton-date"></span></td>
                                <td><span class="skeleton skeleton-short"></span></td>
                                <td><span class="skeleton skeleton-action"></span></td>
                            </tr>
                            <tr class="skeleton-row" aria-hidden="true">
                                <td><span class="skeleton skeleton-product"></span></td>
                                <td><span class="skeleton skeleton-short"></span></td>
                                <td><span class="skeleton skeleton-short"></span></td>
                                <td><span class="skeleton skeleton-date"></span></td>
                                <td><span class="skeleton skeleton-short"></span></td>
                                <td><span class="skeleton skeleton-action"></span></td>
                            </tr>
                            <tr class="skeleton-row" aria-hidden="true">
                                <td><span class="skeleton skeleton-product"></span></td>
                                <td><span class="skeleton skeleton-short"></span></td>
                                <td><span class="skeleton skeleton-short"></span></td>
                                <td><span class="skeleton skeleton-date"></span></td>
                                <td><span class="skeleton skeleton-short"></span></td>
                                <td><span class="skeleton skeleton-action"></span></td>
                            </tr>
                        </tbody>
                        <tfoot id="inventoryTableFoot" class="d-none">
                            <tr>
                                <th scope="row" colspan="4">Grand total</th>
                                <td id="grandTotalCell" class="text-end">$0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div id="emptyState" class="empty-state d-none">
                    <span class="empty-illustration" aria-hidden="true">
                        <svg viewBox="0 0 80 80">
                            <path d="M17 27 40 15l23 12v27L40 66 17 54V27Z"></path>
                            <path d="m17 28 23 12 23-12M40 40v26"></path>
                            <path d="M31 23.5 54 35"></path>
                        </svg>
                    </span>
                    <h3>Your inventory is ready</h3>
                    <p>Add the first product to start tracking its value.</p>
                    <a href="#add-product" class="btn btn-sm btn-soft">Create first product</a>
                </div>
            </section>
        </div>

        <footer class="page-footer">
            <span>Inventory Studio</span>
            <span aria-hidden="true">•</span>
            <span>Built for clear, confident stock decisions.</span>
        </footer>
    </main>
</div>

<div class="toast-viewport" aria-live="polite" aria-atomic="true">
    <div id="appToast" class="app-toast" role="status" aria-hidden="true">
        <span class="toast-icon" aria-hidden="true">
            <svg class="toast-success-icon" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"></path></svg>
            <svg class="toast-error-icon" viewBox="0 0 24 24"><path d="M7 7l10 10M17 7 7 17"></path></svg>
        </span>
        <span>
            <strong id="toastTitle">Saved</strong>
            <small id="toastMessage">Your changes are live.</small>
        </span>
        <button id="toastClose" type="button" aria-label="Dismiss notification">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 7l10 10M17 7 7 17"></path></svg>
        </button>
    </div>
</div>
<div id="appLiveRegion" class="visually-hidden" aria-live="polite" aria-atomic="true"></div>

<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content app-modal">
            <div class="modal-header">
                <div>
                    <span class="section-kicker">Update record</span>
                    <h2 class="modal-title" id="editProductTitle">Edit product</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editProductForm" novalidate>
                @csrf
                <input id="editProductId" type="hidden">
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label" for="editProductName">Product name</label>
                        <input id="editProductName" class="form-control" name="product_name" type="text" maxlength="255" aria-describedby="editProductNameError" required>
                        <div id="editProductNameError" class="invalid-feedback" role="alert"></div>
                    </div>
                    <div class="form-row-split">
                        <div>
                            <label class="form-label" for="editQuantityInStock">Quantity in stock</label>
                            <input id="editQuantityInStock" class="form-control" name="quantity_in_stock" type="number" min="0" step="1" inputmode="numeric" aria-describedby="editQuantityInStockError" required>
                            <div id="editQuantityInStockError" class="invalid-feedback" role="alert"></div>
                        </div>
                        <div>
                            <label class="form-label" for="editPricePerItem">Price per item</label>
                            <div class="input-shell input-shell-prefix">
                                <span class="input-prefix" aria-hidden="true">$</span>
                                <input id="editPricePerItem" class="form-control" name="price_per_item" type="number" min="0" step="0.01" inputmode="decimal" aria-describedby="editPricePerItemError" required>
                            </div>
                            <div id="editPricePerItemError" class="invalid-feedback" role="alert"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-quiet" data-bs-dismiss="modal">Cancel</button>
                    <button id="editSubmitButton" type="submit" class="btn btn-accent">
                        <span class="button-idle">Save changes</span>
                        <span class="button-loading" aria-hidden="true">
                            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                            Saving…
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
