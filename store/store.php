<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();

// Store is public — anyone can browse products without logging in.
// Login is only required at checkout time (handled in JS + checkout.php).
$is_logged_in = isset($_SESSION['id']);

$current_page = 'store.php';
require_once __DIR__ . '/../includes/db.php';

try {
    $cat_stmt = $pdo->query("SELECT * FROM store_categories ORDER BY id ASC");
    $categories = $cat_stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
}

// Pull the logged-in user's account name/email so the checkout form
// can auto-fill them (still editable, in case they want to ship to
// someone else or fix a typo on the name).
$current_user_fullname = '';
$current_user_email = '';
if ($is_logged_in) {
    try {
        $u_stmt = $pdo->prepare("SELECT fullname, email FROM users WHERE id = ?");
        $u_stmt->execute([$_SESSION['id']]);
        $u_row = $u_stmt->fetch();
        $current_user_fullname = $u_row['fullname'] ?? '';
        $current_user_email    = $u_row['email'] ?? '';
    } catch (Exception $e) {
        // fail silently — form just won't be pre-filled
    }
}
$name_parts = array_filter(explode(' ', trim($current_user_fullname)), fn($p) => $p !== '');
$name_parts = array_values($name_parts);
$current_user_first_name = $name_parts[0] ?? '';
$current_user_last_name  = count($name_parts) > 1 ? implode(' ', array_slice($name_parts, 1)) : '';

// Pre-fill the phone number from the user's most recent order, if any —
// there's no phone field on the account itself, so we fall back to the
// last number they actually used at checkout. Still editable.
$current_user_last_phone = '';
if ($is_logged_in) {
    try {
        $phone_stmt = $pdo->prepare(
            "SELECT contact_number FROM orders
             WHERE user_id = ? AND contact_number IS NOT NULL AND contact_number != ''
             ORDER BY order_date DESC LIMIT 1"
        );
        $phone_stmt->execute([$_SESSION['id']]);
        $current_user_last_phone = $phone_stmt->fetchColumn() ?: '';
    } catch (Exception $e) {
        // fail silently — field just won't be pre-filled
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SafeKidsSpace - Educational Academy Shop</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700&family=Orbitron:wght@500;700;900&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <link rel="stylesheet" href="../assets/layout.css">
</head>
<body>

  <?php include __DIR__ . '/../includes/navbar.php'; ?>
  <div class="container">
    <main class="main-content" id="content">

      <!-- ===== HERO BANNER ===== -->
      <section class="store-hero-banner">
        <div class="store-hero-inner">
          <div class="store-pill"><i class="fa-solid fa-basket-shopping"></i> Astro Kids Store</div>
          <h1 class="store-main-title">Shop Cool Learning Kits &amp; Toys</h1>
          <p class="store-subtitle">Browse STEM robotics kits, puzzle sets, telescopes, and rocket models — perfect gifts for curious young minds.</p>
          <a href="#products-container" class="store-hero-btn">
            <i class="fa-solid fa-basket-shopping"></i> Shop Now
          </a>
        </div>
      </section>

      <!-- ===== CATEGORY PILLS (one line, scrollable) ===== -->
      <div class="store-cats-bar" id="cats-bar">
        <button class="cat-pill active" data-category="all">
          <i class="fa-solid fa-book-bookmark"></i> All Resources
        </button>
        <?php foreach ($categories as $cat): ?>
          <button class="cat-pill" data-category="<?php echo htmlspecialchars($cat['slug']); ?>">
            <i class="<?php echo htmlspecialchars($cat['icon_class']); ?>"></i>
            <?php echo htmlspecialchars($cat['name']); ?>
          </button>
        <?php endforeach; ?>
      </div>

      <!-- ===== PRODUCTS GRID ===== -->
      <div id="store-loader">
        <div class="spinner-border"></div>
        <p style="color:#64748b;font-size:0.8rem;margin-top:10px;font-family:monospace;">Syncing inventory...</p>
      </div>
      <div class="products-grid-matrix" id="products-container"></div>

    </main>
  </div>

  <?php include __DIR__ . '/../includes/footer.php'; ?>

  <!-- ===== FLOATING CART FAB ===== -->
  <button id="cart-fab" aria-label="Open Cart">
    <i class="fa-solid fa-basket-shopping"></i>
    <span id="cart-fab-badge">0</span>
  </button>

  <!-- ===== CART DRAWER ===== -->
  <div id="cart-drawer-overlay"></div>
  <div id="cart-drawer">
    <div class="cart-drawer-head">
      <h3><i class="fa-solid fa-basket-shopping"></i> Learning Kit Bag</h3>
      <button class="cart-close-btn" id="cart-close-btn"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="cart-drawer-items" id="cart-drawer-items">
      <div class="cart-empty-state" id="cart-empty-state">
        <i class="fa-solid fa-box-open"></i>
        <p>Your bag is empty.<br>Add some learning materials!</p>
      </div>
    </div>
    <div class="cart-drawer-foot">
      <div class="cart-subtotal-row">
        <span>Items</span>
        <span id="drawer-qty">0 items</span>
      </div>
      <div class="cart-total-row">
        <span>Total</span>
        <span id="drawer-total">$0.00</span>
      </div>
      <button class="checkout-btn-main" id="checkout-btn" disabled>
        <i class="fa-solid fa-lock me-2"></i> PROCEED TO CHECKOUT
      </button>
    </div>
  </div>

  <!-- ===== PRODUCT DETAIL MODAL ===== -->
  <div id="product-modal-overlay" class="popup-overlay" style="display:none;">
    <div class="popup-card product-detail-card">
      <button class="pd-close-btn" id="pd-close-btn" type="button" aria-label="Close">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <div class="pd-visual-frame">
        <img id="pd-image" src="" alt="" class="pd-img">
      </div>

      <div class="pd-body">
        <span class="product-badge-tag pd-badge-inline" id="pd-badge" style="display:none;"></span>
        <h2 class="pd-title" id="pd-title"></h2>
        <div class="pd-price" id="pd-price"></div>
        <p class="pd-desc" id="pd-desc"></p>

        <div class="pd-qty-row">
          <span class="pd-qty-label">Quantity</span>
          <div class="pd-qty-stepper">
            <button type="button" class="pd-qty-btn" id="pd-qty-dec">−</button>
            <span class="pd-qty-value" id="pd-qty-value">1</span>
            <button type="button" class="pd-qty-btn" id="pd-qty-inc">+</button>
          </div>
        </div>

        <p class="pd-stock-note" id="pd-stock-note"></p>

        <button class="popup-continue-btn pd-add-btn" id="pd-add-to-cart-btn">
          <i class="fa-solid fa-cart-plus me-2"></i> Add to Cart
        </button>
      </div>
    </div>
  </div>

  <!-- ===== CHECKOUT MODAL ===== -->
  <div id="checkout-modal-overlay" class="popup-overlay" style="display:none;">
    <div class="popup-card checkout-card">
      <div class="popup-header checkout-modal-header">
        <button class="checkout-back-btn" id="checkout-back-btn" type="button" aria-label="Back to cart">
          <i class="fa-solid fa-arrow-left"></i>
        </button>
        <h2><i class="fa-solid fa-truck-fast" style="margin-right:8px;"></i>Checkout</h2>
        <p>Fill in your delivery &amp; payment details</p>
      </div>

      <form id="checkout-form" novalidate>
        <div class="checkout-body">

          <!-- Delivery -->
          <div class="checkout-section">
            <h4>Delivery</h4>

            <div class="ck-field-row">
              <div class="ck-field">
                <label for="ck-first-name">First name</label>
                <input type="text" id="ck-first-name" name="first_name" placeholder="Ayesha" autocomplete="given-name" value="<?= htmlspecialchars($current_user_first_name) ?>">
                <span class="ck-error" id="err-first-name"></span>
              </div>
              <div class="ck-field">
                <label for="ck-last-name">Last name</label>
                <input type="text" id="ck-last-name" name="last_name" placeholder="Khan" autocomplete="family-name" value="<?= htmlspecialchars($current_user_last_name) ?>">
                <span class="ck-error" id="err-last-name"></span>
              </div>
            </div>

            <div class="ck-field">
              <label for="ck-email">Email <span class="ck-email-note">(from your account)</span></label>
              <input type="email" id="ck-email" class="ck-email-readonly" value="<?= htmlspecialchars($current_user_email) ?>" readonly disabled>
            </div>

            <div class="ck-field">
              <label for="ck-billing-address">Address</label>
              <input type="text" id="ck-billing-address" name="address" placeholder="House #, Street, Area" autocomplete="street-address">
              <span class="ck-error" id="err-address"></span>
            </div>

            <div class="ck-field">
              <label for="ck-city">City</label>
              <input type="text" id="ck-city" name="city" placeholder="Karachi" autocomplete="address-level2">
              <span class="ck-error" id="err-city"></span>
            </div>

            <div class="ck-field">
              <label for="ck-contact-number">Phone</label>
              <input type="tel" id="ck-contact-number" name="contact_number" placeholder="03001234567" autocomplete="tel" inputmode="numeric" maxlength="11" value="<?= htmlspecialchars($current_user_last_phone) ?>">
              <span class="ck-error" id="err-contact-number"></span>
            </div>
          </div>

          <!-- Shipping method -->
          <div class="checkout-section">
            <h4>Shipping method</h4>
            <div class="ck-shipping-row">
              <span>Standard</span>
              <span id="ck-shipping-price">RS 250</span>
            </div>
          </div>

          <!-- Payment method -->
          <div class="checkout-section">
            <h4>Payment</h4>
            <p class="ck-pay-subtitle">All transactions are secure and encrypted.</p>

            <div class="ck-pay-list">
              <label class="ck-pay-row">
                <input type="radio" name="payment_method" value="jazzcash">
                <span class="ck-radio-dot"></span>
                <span class="ck-pay-row-label">JazzCash</span>
                <span class="ck-pay-badge ck-badge-jazzcash">JazzCash</span>
              </label>

              <label class="ck-pay-row">
                <input type="radio" name="payment_method" value="easypaisa">
                <span class="ck-radio-dot"></span>
                <span class="ck-pay-row-label">EasyPaisa</span>
                <span class="ck-pay-badge ck-badge-easypaisa">EasyPaisa</span>
              </label>

              <label class="ck-pay-row">
                <input type="radio" name="payment_method" value="cod">
                <span class="ck-radio-dot"></span>
                <span class="ck-pay-row-label">Cash on Delivery (COD)</span>
              </label>
            </div>
            <span class="ck-error" id="err-payment-method"></span>

            <div class="ck-field ck-tid-field" id="ck-tid-wrap" style="display:none;">
              <label for="ck-payment-reference">Transaction ID (TID)</label>
              <input type="text" id="ck-payment-reference" name="payment_reference" placeholder="Enter TID from your payment app">
              <span class="ck-error" id="err-payment-reference"></span>
            </div>
          </div>

          <!-- Order summary -->
          <div class="checkout-section">
            <h4>Order Summary</h4>
            <div class="ck-summary-row">
              <span>Subtotal</span>
              <span id="ck-subtotal">RS 0</span>
            </div>
            <div class="ck-summary-row">
              <span>Delivery Charges</span>
              <span id="ck-delivery">RS 0</span>
            </div>
            <div class="ck-summary-row ck-summary-total">
              <span>Total</span>
              <span id="ck-grand-total">RS 0</span>
            </div>
            <p class="ck-delivery-hint" id="ck-delivery-hint">Free delivery on orders RS 5000 and above — otherwise a flat RS 250 delivery charge applies.</p>
          </div>

        </div>

        <div class="popup-footer">
          <span class="ck-submit-error" id="ck-submit-error"></span>
          <button type="submit" class="popup-continue-btn" id="ck-place-order-btn">
            <i class="fa-solid fa-lock me-2"></i> Pay now
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ===== JAVASCRIPT ===== -->
  <script>
  document.addEventListener("DOMContentLoaded", () => {

    // Passed from PHP session — tells the frontend whether checkout is allowed.
    const IS_LOGGED_IN = <?php echo $is_logged_in ? 'true' : 'false'; ?>;

    // ---- State ----
    const cart = {}; // { productId: { title, price, image, qty } }
    const productsById = {}; // { productId: fullProductRecord } — used by the product detail modal
    let pdCurrentProductId = null;
    let pdCurrentQty = 1;

    // ---- DOM refs ----
    const productsContainer = document.getElementById("products-container");
    const loader            = document.getElementById("store-loader");
    const catPills          = document.querySelectorAll(".cat-pill");
    const cartFab           = document.getElementById("cart-fab");
    const cartBadge         = document.getElementById("cart-fab-badge");
    const cartDrawer        = document.getElementById("cart-drawer");
    const cartOverlay       = document.getElementById("cart-drawer-overlay");
    const cartCloseBtn      = document.getElementById("cart-close-btn");
    const cartItemsEl       = document.getElementById("cart-drawer-items");
    const cartEmptyEl       = document.getElementById("cart-empty-state");
    const drawerQtyEl       = document.getElementById("drawer-qty");
    const drawerTotalEl     = document.getElementById("drawer-total");
    const checkoutBtn       = document.getElementById("checkout-btn");

    // ---- Drawer open / close ----
    function openCart()  { cartDrawer.classList.add("open"); cartOverlay.classList.add("open"); }
    function closeCart() { cartDrawer.classList.remove("open"); cartOverlay.classList.remove("open"); }
    cartFab.addEventListener("click", openCart);
    cartCloseBtn.addEventListener("click", closeCart);
    cartOverlay.addEventListener("click", closeCart);

    // ---- Escape key ----
    document.addEventListener("keydown", e => {
      if (e.key === "Escape") {
        closeCart();
        if (productModalOverlay.style.display === "flex") closeProductModal();
      }
    });

    // ---- Escape HTML ----
    function esc(str) {
      return String(str).replace(/[&<>'"]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":"&#39;",'"':'&quot;'}[m]));
    }

    // ---- Cart render ----
    function renderCart() {
      const ids = Object.keys(cart);
      const totalQty  = ids.reduce((s, id) => s + cart[id].qty, 0);
      const totalCost = ids.reduce((s, id) => s + cart[id].price * cart[id].qty, 0);

      // FAB badge
      cartBadge.textContent = totalQty;
      if (totalQty > 0) cartBadge.classList.add("show");
      else              cartBadge.classList.remove("show");

      // Drawer footer
      drawerQtyEl.textContent   = totalQty + " item" + (totalQty !== 1 ? "s" : "");
      drawerTotalEl.textContent = "RS " + totalCost.toFixed(0);
      checkoutBtn.disabled      = totalQty === 0;

      // Items list
      cartItemsEl.innerHTML = "";

      if (ids.length === 0) {
        cartItemsEl.appendChild(cartEmptyEl);
        return;
      }

      ids.forEach(id => {
        const item = cart[id];
        const el = document.createElement("div");
        el.className = "cart-line-item";
        el.innerHTML = `
          <div class="cart-item-thumb">
            <img src="${esc(item.image)}" alt="${esc(item.title)}">
          </div>
          <div class="cart-item-info">
            <div class="cart-item-name">${esc(item.title)}</div>
            <div class="cart-item-price">RS ${item.price.toFixed(0)}</div>
          </div>
          <div class="cart-item-qty">
            <button class="qty-btn" data-action="dec" data-id="${esc(id)}">−</button>
            <span class="qty-display">${item.qty}</span>
            <button class="qty-btn" data-action="inc" data-id="${esc(id)}">+</button>
          </div>
        `;
        cartItemsEl.appendChild(el);
      });

      // Qty button events
      cartItemsEl.querySelectorAll(".qty-btn").forEach(btn => {
        btn.addEventListener("click", () => {
          const pid    = btn.dataset.id;
          const action = btn.dataset.action;
          if (action === "inc") {
            cart[pid].qty++;
          } else {
            cart[pid].qty--;
            if (cart[pid].qty <= 0) delete cart[pid];
          }
          renderCart();
        });
      });
    }

    // ---- Add to cart (shared helper — used by both the quick button and the modal) ----
    function addToCart(id, title, price, image, qty = 1) {
      if (cart[id]) {
        cart[id].qty += qty;
      } else {
        cart[id] = { title, price, image, qty };
      }
      renderCart();
    }

    // ---- Add to cart (quick button on the card) + open detail modal (card click) ----
    function bindAddToCart() {
      document.querySelectorAll(".add-to-cart-btn").forEach(btn => {
        btn.addEventListener("click", function(e) {
          e.stopPropagation(); // don't also trigger the card's open-modal click
          const id    = this.dataset.id;
          const price = parseFloat(this.dataset.price);
          const title = this.dataset.title;
          const image = this.dataset.image;

          addToCart(id, title, price, image, 1);

          // Visual feedback
          this.classList.add("added");
          this.innerHTML = `<i class="fa-solid fa-check"></i> Added`;
          setTimeout(() => {
            this.classList.remove("added");
            this.innerHTML = `<i class="fa-solid fa-cart-plus"></i> Add to Cart`;
          }, 900);
        });
      });

      document.querySelectorAll(".product-item-card").forEach(card => {
        card.addEventListener("click", function() {
          if (this.classList.contains("out-of-stock")) return;
          openProductModal(this.dataset.id);
        });
      });
    }

    // ================= PRODUCT DETAIL MODAL =================
    const productModalOverlay = document.getElementById("product-modal-overlay");
    const pdCloseBtn    = document.getElementById("pd-close-btn");
    const pdImage       = document.getElementById("pd-image");
    const pdBadge       = document.getElementById("pd-badge");
    const pdTitle       = document.getElementById("pd-title");
    const pdPrice       = document.getElementById("pd-price");
    const pdDesc        = document.getElementById("pd-desc");
    const pdQtyValueEl  = document.getElementById("pd-qty-value");
    const pdQtyDecBtn   = document.getElementById("pd-qty-dec");
    const pdQtyIncBtn   = document.getElementById("pd-qty-inc");
    const pdStockNote   = document.getElementById("pd-stock-note");
    const pdAddBtn      = document.getElementById("pd-add-to-cart-btn");

    function openProductModal(id) {
      const p = productsById[id];
      if (!p) return;

      pdCurrentProductId = id;
      pdCurrentQty = 1;

      const stock = parseInt(p.stock ?? 0, 10);

      pdImage.src = "../" + p.image_path;
      pdImage.alt = p.title;
      pdTitle.textContent = p.title;
      pdPrice.textContent = "RS " + parseFloat(p.price).toFixed(0);
      pdDesc.textContent  = p.description;

      if (p.badge_tag) {
        pdBadge.textContent = p.badge_tag;
        pdBadge.style.display = "inline-flex";
      } else {
        pdBadge.style.display = "none";
      }

      if (stock <= 0) {
        pdStockNote.textContent = "Out of stock";
        pdStockNote.classList.add("pd-out-of-stock");
        pdAddBtn.disabled = true;
        pdQtyIncBtn.disabled = true;
        pdQtyDecBtn.disabled = true;
      } else {
        pdStockNote.textContent = stock <= 5 ? `Only ${stock} left in stock` : "";
        pdStockNote.classList.remove("pd-out-of-stock");
        pdAddBtn.disabled = false;
        pdQtyIncBtn.disabled = false;
        pdQtyDecBtn.disabled = false;
      }

      pdQtyValueEl.textContent = pdCurrentQty;
      pdAddBtn.innerHTML = `<i class="fa-solid fa-cart-plus me-2"></i> Add to Cart`;

      productModalOverlay.style.display = "flex";
    }

    function closeProductModal() {
      productModalOverlay.style.display = "none";
      pdCurrentProductId = null;
    }

    pdCloseBtn.addEventListener("click", closeProductModal);
    productModalOverlay.addEventListener("click", (e) => {
      if (e.target === productModalOverlay) closeProductModal();
    });

    pdQtyDecBtn.addEventListener("click", () => {
      if (pdCurrentQty > 1) {
        pdCurrentQty--;
        pdQtyValueEl.textContent = pdCurrentQty;
      }
    });

    pdQtyIncBtn.addEventListener("click", () => {
      const p = productsById[pdCurrentProductId];
      const stock = p ? parseInt(p.stock ?? 0, 10) : 99;
      if (pdCurrentQty < stock) {
        pdCurrentQty++;
        pdQtyValueEl.textContent = pdCurrentQty;
      }
    });

    pdAddBtn.addEventListener("click", () => {
      const p = productsById[pdCurrentProductId];
      if (!p) return;

      addToCart(p.id, p.title, parseFloat(p.price), "../" + p.image_path, pdCurrentQty);

      pdAddBtn.innerHTML = `<i class="fa-solid fa-check me-2"></i> Added`;
      setTimeout(() => {
        closeProductModal();
      }, 700);
    });

    // ---- Fetch products ----
    async function fetchProducts(category = "all") {
      productsContainer.style.opacity = "0.15";
      loader.style.display = "block";
      productsContainer.innerHTML = "";

      try {
        const res = await fetch(`get_products.php?category=${encodeURIComponent(category)}`);
        if (!res.ok) throw new Error("Request failed");
        const products = await res.json();

        if (products.length === 0) {
          productsContainer.innerHTML = `
            <div style="grid-column:1/-1;text-align:center;padding:60px 0;color:#475569;">
              <i class="fa-solid fa-box-open" style="font-size:2.5rem;margin-bottom:14px;display:block;"></i>
              <p style="font-size:0.9rem;">No products in this category yet.</p>
            </div>`;
          return;
        }

        products.forEach(p => {
          productsById[p.id] = p; // cache full record for the product detail modal

          const badge = p.badge_tag
            ? `<span class="product-badge-tag">${esc(p.badge_tag)}</span>` : "";
          const stock = parseInt(p.stock ?? 0, 10);
          const isOutOfStock = stock <= 0;
          const card = document.createElement("div");
          card.className = "product-item-card" + (isOutOfStock ? " out-of-stock" : "");
          card.dataset.id = p.id;
          card.innerHTML = `
            ${badge}
            ${isOutOfStock ? `<span class="out-of-stock-badge">Out of Stock</span>` : ""}
            <div class="product-visual-frame">
              <img src="../${esc(p.image_path)}" alt="${esc(p.title)}" class="product-img">
            </div>
            <div class="product-details-pane">
              <h3 class="product-title">${esc(p.title)}</h3>
              <div class="product-action-row">
                <div class="product-price-tag">RS ${parseFloat(p.price).toFixed(0)}</div>
                <button class="add-to-cart-btn"
                  data-id="${esc(p.id)}"
                  data-price="${esc(p.price)}"
                  data-title="${esc(p.title)}"
                  data-image="../${esc(p.image_path)}"
                  ${isOutOfStock ? "disabled" : ""}>
                  <i class="fa-solid ${isOutOfStock ? "fa-ban" : "fa-cart-plus"}"></i> ${isOutOfStock ? "Out of Stock" : "Add to Cart"}
                </button>
              </div>
            </div>
          `;
          productsContainer.appendChild(card);
        });

        bindAddToCart();

      } catch (err) {
        console.error(err);
        productsContainer.innerHTML = `
          <p style="grid-column:1/-1;text-align:center;color:#f87171;padding:40px 0;font-family:monospace;">
            Error loading products. Please refresh.
          </p>`;
      } finally {
        loader.style.display = "none";
        productsContainer.style.opacity = "1";
      }
    }

    // ---- Category pill clicks ----
    catPills.forEach(pill => {
      pill.addEventListener("click", () => {
        catPills.forEach(p => p.classList.remove("active"));
        pill.classList.add("active");
        fetchProducts(pill.dataset.category);
      });
    });

 // Locate this inside your <script> tag in store.php
    const checkoutModalOverlay = document.getElementById("checkout-modal-overlay");
    const checkoutBackBtn      = document.getElementById("checkout-back-btn");
    const checkoutForm         = document.getElementById("checkout-form");
    const placeOrderBtn        = document.getElementById("ck-place-order-btn");
    const ckTidWrap            = document.getElementById("ck-tid-wrap");
    const ckSubtotalEl         = document.getElementById("ck-subtotal");
    const ckDeliveryEl         = document.getElementById("ck-delivery");
    const ckGrandTotalEl       = document.getElementById("ck-grand-total");
    const ckShippingPriceEl    = document.getElementById("ck-shipping-price");
    const ckSubmitErrorEl      = document.getElementById("ck-submit-error");

    const FREE_DELIVERY_THRESHOLD = 5000;
    const FLAT_DELIVERY_CHARGE    = 250;

    function currentCartTotals() {
      const ids = Object.keys(cart);
      const subtotal = ids.reduce((s, id) => s + cart[id].price * cart[id].qty, 0);
      const delivery = subtotal >= FREE_DELIVERY_THRESHOLD ? 0 : (ids.length ? FLAT_DELIVERY_CHARGE : 0);
      return { subtotal, delivery, total: subtotal + delivery };
    }

    function renderCheckoutSummary() {
      const { subtotal, delivery, total } = currentCartTotals();
      ckSubtotalEl.textContent      = "RS " + subtotal.toFixed(0);
      ckDeliveryEl.textContent      = delivery === 0 ? "FREE" : "RS " + delivery.toFixed(0);
      ckGrandTotalEl.textContent    = "RS " + total.toFixed(0);
      ckShippingPriceEl.textContent = delivery === 0 ? "FREE" : "RS " + delivery.toFixed(0);
    }

    function clearFieldErrors() {
      checkoutForm.querySelectorAll(".ck-error").forEach(el => el.textContent = "");
      ckSubmitErrorEl.textContent = "";
      checkoutForm.querySelectorAll(".ck-field.has-error").forEach(el => el.classList.remove("has-error"));
    }

    function showFieldError(fieldName, message) {
      const errEl = document.getElementById("err-" + fieldName.replace(/_/g, "-"));
      if (errEl) errEl.textContent = message;
      const inputEl = checkoutForm.querySelector(`[name="${fieldName}"]`);
      if (inputEl) {
        const wrap = inputEl.closest(".ck-field");
        if (wrap) wrap.classList.add("has-error");
      }
    }

    // Show/hide TID field based on payment method
    checkoutForm.querySelectorAll('input[name="payment_method"]').forEach(radio => {
      radio.addEventListener("change", () => {
        const needsTid = radio.value === "jazzcash" || radio.value === "easypaisa";
        ckTidWrap.style.display = needsTid ? "block" : "none";
      });
    });

    // ---- Phone number: digits only + live validation ----
    const ckPhoneInput = document.getElementById("ck-contact-number");
    const ckPhoneError = document.getElementById("err-contact-number");
    const phoneRegex    = /^(03[0-9]{9}|\+92 ?3[0-9]{9})$/;

    ckPhoneInput.addEventListener("input", () => {
      // Strip anything that isn't a digit (keeps typing frictionless)
      const cleaned = ckPhoneInput.value.replace(/[^0-9]/g, "").slice(0, 11);
      ckPhoneInput.value = cleaned;

      if (cleaned.length === 0) {
        ckPhoneError.textContent = "";
        ckPhoneInput.closest(".ck-field").classList.remove("has-error");
      } else if (!phoneRegex.test(cleaned)) {
        ckPhoneError.textContent = cleaned.length < 11
          ? "Number must be 11 digits, e.g. 03001234567."
          : "Enter a valid number starting with 03, e.g. 03001234567.";
        ckPhoneInput.closest(".ck-field").classList.add("has-error");
      } else {
        ckPhoneError.textContent = "";
        ckPhoneInput.closest(".ck-field").classList.remove("has-error");
      }
    });

    // Block non-digit keystrokes outright (extra safety on top of the input filter)
    ckPhoneInput.addEventListener("keypress", (e) => {
      if (!/[0-9]/.test(e.key)) e.preventDefault();
    });

    // ---- Client-side validation ----
    function validateCheckoutForm() {
      clearFieldErrors();
      let valid = true;

      const firstName = document.getElementById("ck-first-name").value.trim();
      const lastName  = document.getElementById("ck-last-name").value.trim();
      const phone     = document.getElementById("ck-contact-number").value.trim();
      const address   = document.getElementById("ck-billing-address").value.trim();
      const city      = document.getElementById("ck-city").value.trim();
      const method    = checkoutForm.querySelector('input[name="payment_method"]:checked');
      const tid       = document.getElementById("ck-payment-reference").value.trim();

      if (firstName.length < 2) {
        showFieldError("first_name", "Please enter your first name.");
        valid = false;
      }

      if (lastName.length < 1) {
        showFieldError("last_name", "Please enter your last name.");
        valid = false;
      }

      const phoneRegex = /^(03[0-9]{9}|\+92 ?3[0-9]{9})$/;
      if (!phoneRegex.test(phone)) {
        showFieldError("contact_number", "Enter a valid number, e.g. 03001234567.");
        valid = false;
      }

      if (address.length < 6) {
        showFieldError("address", "Please enter a complete delivery address.");
        valid = false;
      }

      if (city.length < 2) {
        showFieldError("city", "Please enter your city.");
        valid = false;
      }

      if (!method) {
        document.getElementById("err-payment-method").textContent = "Please select a payment method.";
        valid = false;
      } else if ((method.value === "jazzcash" || method.value === "easypaisa") && tid.length < 4) {
        showFieldError("payment_reference", "Please enter your transaction ID (TID).");
        valid = false;
      }

      if (Object.keys(cart).length === 0) {
        ckSubmitErrorEl.textContent = "Your cart is empty.";
        valid = false;
      }

      return valid;
    }

    // ---- Open / close checkout modal ----
    function openCheckoutModal() {
      renderCheckoutSummary();
      closeCart();
      checkoutModalOverlay.style.display = "flex";
    }
    function closeCheckoutModal() {
      checkoutModalOverlay.style.display = "none";
    }

    checkoutBtn.addEventListener("click", () => {
      if (checkoutBtn.disabled) return;

      // Guest with items in the cart -> send to login/register first.
      // We keep their cart items in localStorage so it isn't lost.
      if (!IS_LOGGED_IN) {
        try {
          localStorage.setItem('sks_pending_cart', JSON.stringify(cart));
        } catch (e) { /* ignore storage errors */ }
        window.location.href = "../account/login.php?redirect=store";
        return;
      }

      openCheckoutModal();
    });

    checkoutBackBtn.addEventListener("click", () => {
      closeCheckoutModal();
      openCart();
    });

    // ---- Submit checkout form ----
    checkoutForm.addEventListener("submit", (e) => {
      e.preventDefault();
      if (!validateCheckoutForm()) return;

      const method = checkoutForm.querySelector('input[name="payment_method"]:checked').value;

      placeOrderBtn.disabled = true;
      placeOrderBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-2"></i> Placing Order...`;

      const payload = new URLSearchParams({
        cart_data: JSON.stringify(cart),
        first_name: document.getElementById("ck-first-name").value.trim(),
        last_name: document.getElementById("ck-last-name").value.trim(),
        contact_number: document.getElementById("ck-contact-number").value.trim(),
        address: document.getElementById("ck-billing-address").value.trim(),
        city: document.getElementById("ck-city").value.trim(),
        payment_method: method,
        payment_reference: document.getElementById("ck-payment-reference").value.trim()
      });

      fetch('checkout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: payload
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {

          const itemsHtml = data.items.map(item => `
            <div class="popup-item-row">
              <div class="popup-item-img">
                <img src="${esc(item.image)}" alt="${esc(item.title)}" onerror="this.src='../images/banner.png'">
              </div>
              <div class="popup-item-info">
                <div class="popup-item-title">${esc(item.title)}</div>
                <div class="popup-item-meta">Qty: <strong>${item.qty}</strong> &nbsp;×&nbsp; RS ${parseFloat(item.price).toFixed(0)}</div>
              </div>
              <div class="popup-item-subtotal">RS ${parseFloat(item.subtotal).toFixed(0)}</div>
            </div>
          `).join('');

          const methodLabels = { cod: "Cash on Delivery", jazzcash: "JazzCash", easypaisa: "EasyPaisa" };
          const statusLabels = { confirmed: ["Confirmed ✓", "#22c55e"], pending_payment: ["Pending Verification", "#facc15"] };
          const statusInfo = statusLabels[data.order_status] || ["Confirmed ✓", "#22c55e"];

          document.getElementById('popup-order-id').textContent    = '#' + data.order_id;
          document.getElementById('popup-user-name').textContent  = data.user_name;
          document.getElementById('popup-order-date').textContent = data.order_date;
          document.getElementById('popup-items-list').innerHTML   = itemsHtml;
          document.getElementById('popup-subtotal').textContent    = 'RS ' + parseFloat(data.subtotal).toFixed(0);
          document.getElementById('popup-delivery').textContent    = parseFloat(data.delivery_charge) === 0 ? 'FREE' : 'RS ' + parseFloat(data.delivery_charge).toFixed(0);
          document.getElementById('popup-grand-total').textContent = 'RS ' + parseFloat(data.total).toFixed(0);
          document.getElementById('popup-billing-name').textContent    = data.billing_name;
          document.getElementById('popup-billing-address').textContent = data.billing_address;
          document.getElementById('popup-contact-number').textContent  = data.contact_number;
          document.getElementById('popup-payment-method').textContent  = methodLabels[data.payment_method] || data.payment_method;
          document.getElementById('popup-status-value').textContent    = statusInfo[0];
          document.getElementById('popup-status-value').style.color    = statusInfo[1];

          closeCheckoutModal();
          document.getElementById('order-success-popup').style.display = 'flex';

          Object.keys(cart).forEach(k => delete cart[k]);
          renderCart();
          checkoutForm.reset();
          ckTidWrap.style.display = "none";

        } else {
          if (data.errors) {
            Object.keys(data.errors).forEach(field => showFieldError(field, data.errors[field]));
          } else {
            ckSubmitErrorEl.textContent = data.message || 'Order failed. Please try again.';
          }
        }
      })
      .catch(() => {
        ckSubmitErrorEl.textContent = 'Connection error. Please try again.';
      })
      .finally(() => {
        placeOrderBtn.disabled = false;
        placeOrderBtn.innerHTML = `<i class="fa-solid fa-bag-shopping me-2"></i> PLACE ORDER`;
      });
    });

    // ---- Boot ----
    // If the user was sent to login mid-checkout, restore their cart now.
    if (IS_LOGGED_IN) {
      try {
        const saved = localStorage.getItem('sks_pending_cart');
        if (saved) {
          Object.assign(cart, JSON.parse(saved));
          localStorage.removeItem('sks_pending_cart');
        }
      } catch (e) { /* ignore corrupt storage */ }
    }
    fetchProducts("all");
    renderCart();
  });
  </script>
<div id="order-success-popup" class="popup-overlay" style="display:none;">
  <div class="popup-card">
    <div class="popup-header">
      <div class="popup-check-icon"><i class="fa-solid fa-check"></i></div>
      <h2>Order Confirmed!</h2>
      <p>Thank you, <strong id="popup-user-name">Customer</strong>! Your learning materials are on their way.</p>
    </div>
    <div class="popup-meta">
      <div class="popup-meta-cell">
        <div class="label">Order ID</div>
        <div class="value" id="popup-order-id">#0</div>
      </div>
      <div class="popup-meta-cell">
        <div class="label">Date</div>
        <div class="value" id="popup-order-date">—</div>
      </div>
      <div class="popup-meta-cell">
        <div class="label">Status</div>
        <div class="value" id="popup-status-value" style="color:#22c55e;">Confirmed ✓</div>
      </div>
    </div>

    <div class="popup-billing-section">
      <h4>Delivery &amp; Payment</h4>
      <div class="popup-billing-row"><span>Name</span><span id="popup-billing-name">—</span></div>
      <div class="popup-billing-row"><span>Contact</span><span id="popup-contact-number">—</span></div>
      <div class="popup-billing-row"><span>Address</span><span id="popup-billing-address">—</span></div>
      <div class="popup-billing-row"><span>Payment Method</span><span id="popup-payment-method">—</span></div>
    </div>

    <div class="popup-items-section">
      <h4>Order Summary</h4>
      <div id="popup-items-list"></div>
    </div>
    <div class="popup-footer">
      <div class="popup-total-row" style="background:none;border:none;padding:0 16px;margin-bottom:6px;">
        <span style="font-size:0.8rem;color:#94a3b8;font-weight:600;">Subtotal</span>
        <span id="popup-subtotal" style="font-size:0.9rem;color:#e2e8f0;font-weight:700;">RS 0</span>
      </div>
      <div class="popup-total-row" style="background:none;border:none;padding:0 16px;margin-bottom:12px;">
        <span style="font-size:0.8rem;color:#94a3b8;font-weight:600;">Delivery Charges</span>
        <span id="popup-delivery" style="font-size:0.9rem;color:#e2e8f0;font-weight:700;">RS 0</span>
      </div>
      <div class="popup-total-row">
        <span>TOTAL</span>
        <span id="popup-grand-total">RS 0</span>
      </div>
      <button class="popup-continue-btn"
        onclick="document.getElementById('order-success-popup').style.display='none'; window.location.reload();">
        <i class="fa-solid fa-bag-shopping me-2"></i> CONTINUE SHOPPING
      </button>
    </div>
  </div>
</div>

</body>
</html>