var sun = document.getElementById("sun-icon")
var moon = document.getElementById("moon-icon")
var body = document.getElementsByTagName("body")[0]
var introText = document.querySelector(".main-content")

function darkMode() {
    sun.style.display="none"
    moon.style.display="block"
    body.classList.toggle("main-content-dark")
    introText.style.color="#eeeff1"
}

function lightMode() {
    sun.style.display="block"
    moon.style.display="none"
    body.classList.toggle("main-content-dark")
    introText.style.color="#1e1e1e"
}

//Products//
let products = [
    {
        id: 1,
        name: "Bluetooth Headphones",
        price: 349.99,
        category: "electronics",
        image: "img/headphone2.jpg",
        description: "High quality wireless headphones with noise cancellation.",
        rating: 4.5
    },

    {
        id: 2,
        name: "Smart Watch",
        price: 3499.99,
        category: "electronics",
        image: "img/smartwatch.jpg",
        description: "Track your fitness and stay connected.",
        rating: 4.1
    },

    {
        id: 3,
        name: "Cotton T-Shirt",
        price: 119.99,
        category: "clothing",
        image: "img/tshirt.jpg",
        description: "Fitting T-Shirts available in multiple colors.",
        rating: 4.6
    },

    {
        id: 4,
        name: "Couch",
        price: 7999.99,
        category: "furniture",
        image: "img/couch.jpg",
        description: "Relax in your living room with this comfortable couch.",
        rating: 4.6
    },

    {
        id: 5,
        name: "Yoga Mat",
        price: 199.99,
        category: "sports",
        image: "img/yogamat.jpg",
        description: "Non-slip yoga mat for your routine exercises.",
        rating: 4.4
    },

    {
        id: 6,
        name: "Laptop",
        price: 4399.99,
        category: "electronics",
        image: "img/laptop.jpg",
        description: "Durable and reliable laptop for your daily tasks.",
        rating: 4.7
    },

    {
        id: 7,
        name: "Adidas Predator FG/MG",
        price: 399.99,
        category: "sports",
        image: "img/adidas predator.jpg",
        description: "Adidas Predator Club FG/MG Junior Football Boots White JH8868.",
        rating: 4.9
    },

    {
        id: 8,
        name: "Boston Celtics Kit NBA",
        price: 249.99,
        category: "sports, clothing",
        image: "img/boston.jpg",
        description: "NBA Jayson Tatum Boston Celtics Jersey Nike City Edition.",
        rating: 4.9
    },

    {
        id: 9,
        name: "Cargo Jeans",
        price: 104.99,
        category: "clothing", 
        image: "img/cargo jeans.jpg",
        description: "Men's Cargo Jeans Cotton Solid - Green.",
        rating: 4.9
    }
];

// Pagination state
let currentPage = 1;
const pageSize = 6; // items per page

function getTotalPages(items) {
    return Math.max(1, Math.ceil(items.length / pageSize));
}

//handle image uploads//
function handleImageUpload(event, productId) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            //update product image//
            const product = products.find(p => p.id === productId);
            if (product) {
                product.image = e.target.result;

                displayProducts(products);  //refresh display//
            }
        };

        reader.readAsDataURL(file);
    }
}

//modified product card creation with upload option//
function createProductCard(product) {
    const card = document.createElement('div');
    card.className = 'product-card';
    card.innerHTML = `
    <img src="${product.image}" alt="${product.name}" class="product-image">
    <div class="product-info">
    <h3 class="product-title">${product.name}</h3>
    <div class="product-price">GHS${product.price}</div>
    <div class="product-rating">${createStarRating(product.rating)}</div>
    <div class="product-actions">
    <button class="action-btn add-to-cart" onclick="addToCart(${product.id})">Add to Cart</button>
    <button class="action-btn wishlist" onclick="toggleWishlist(${product.id})"><i class="far fa-heart"></i></button>
    </div>
    </div>
    `;

    card.addEventListener('click', (e) => {
        if (!e.target.closest('.product-actions') && !
        e.target.classList.contains('image-upload')) {
            showProductModal(product);
        }
    });

    return card;
}

//css for file input//
const uploadStyle = document.createElement('style');
uploadStyle.textContent = `
    .image-upload {
        width: 30%;
        margin-top: 0.5rem;
        font-size: 0.8rem;
    }
`;

document.head.appendChild(uploadStyle);

//Cart and wishlist data//
let cart = JSON.parse(localStorage.getItem('cart')) || [];
let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];

//DOM elements//
const productsGrid= document.getElementById('productsGrid');
const cartSidebar= document.getElementById('cartSidebar');
const cartItems= document.getElementById('cartItems');
const cartTotal= document.getElementById('cartTotal');
const cartCount= document.getElementById('cartCount');
const wishlistCount= document.getElementById('wishlistCount');
const cartBtn= document.getElementById('cartBtn');
const wishlistBtn= document.getElementById('wishlistBtn');
const closeCart= document.getElementById('closeCart');
const checkoutBtn= document.getElementById('checkoutBtn');
const categoryFilter= document.getElementById('categoryFilter');
const sortFilter= document.getElementById('sortFilter');
const searchInput= document.getElementById('searchInput');
const modalBody= document.getElementById('modalBody');
const productModal = document.getElementById('productModal');

//Initialize the application//
function init() {
    // Try server-backed loading first; fall back to local `products` variable
    fetch('api/products.json')
        .then(r => { if (!r.ok) throw new Error('no remote'); return r.json(); })
        .then(data => {
            if (Array.isArray(data) && data.length) products = data;
        })
        .catch(() => {})
        .finally(() => {
            currentPage = 1;
            displayProducts(getFilteredProducts());
            updateCartCount();
            updateWishlistCount();
            setupEventListeners();
        });
}

//Display products in the grid//
function displayProducts(productsToDisplay) {
    productsGrid.innerHTML = '';

    // ensure page bounds
    const totalPages = getTotalPages(productsToDisplay);
    if (currentPage > totalPages) currentPage = totalPages;

    const start = (currentPage - 1) * pageSize;
    const pageItems = productsToDisplay.slice(start, start + pageSize);

    pageItems.forEach(product => {
        const productCard = createProductCard(product);
        productsGrid.appendChild(productCard);
    });

    renderPaginationControls(productsToDisplay.length);
}

function renderPaginationControls(totalItems) {
    let pagination = document.getElementById('pagination');
    if (!pagination) {
        pagination = document.createElement('div');
        pagination.id = 'pagination';
        pagination.className = 'pagination';
        const parent = document.getElementById('products') || document.body;
        parent.appendChild(pagination);
    }

    const totalPages = getTotalPages(Array.from({length: totalItems}));
    pagination.innerHTML = '';

    if (totalPages <= 1) return;

    const prev = document.createElement('button');
    prev.textContent = 'Prev';
    prev.disabled = currentPage === 1;
    prev.addEventListener('click', () => { currentPage--; displayProducts(getFilteredProducts()); });
    pagination.appendChild(prev);

    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('button');
        btn.textContent = String(i);
        if (i === currentPage) btn.className = 'active';
        btn.addEventListener('click', () => { currentPage = i; displayProducts(getFilteredProducts()); });
        pagination.appendChild(btn);
    }

    const next = document.createElement('button');
    next.textContent = 'Next';
    next.disabled = currentPage === totalPages;
    next.addEventListener('click', () => { currentPage++; displayProducts(getFilteredProducts()); });
    pagination.appendChild(next);
}

function getFilteredProducts() {
    const category = (categoryFilter && categoryFilter.value) ? categoryFilter.value : 'all';
    const sortBy = (sortFilter && sortFilter.value) ? sortFilter.value : 'price-low';

    let filteredProducts = products.slice();
    if (category !== 'all') {
        filteredProducts = filteredProducts.filter(p => p.category === category);
    }

    switch (sortBy) {
        case 'price-low': filteredProducts.sort((a, b) => a.price - b.price); break;
        case 'price-high': filteredProducts.sort((a, b) => b.price - a.price); break;
        case 'name': filteredProducts.sort((a, b) => a.name.localeCompare(b.name)); break;
    }

    return filteredProducts;
}


//Create star rating HTML//
function createStarRating(rating) {
    const stars = [];
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 !== 0;

    for (let i = 0; i < fullStars; i++) {
        stars.push('<i class="fas fa-star"></i>');
    }

    if (hasHalfStar) {
        stars.push('<i class="fas fa-star-half-alt"></i>');
    }

    const emptyStars = 5 - stars.length;
    for (let i = 0; i < emptyStars; i++) {
        stars.push('<i class="far fa-star"></i>');
    }

    return stars.join('') + `<span>(${rating})</span>`;
}

//Add product to cart//
function addToCart(productId) {
    const product = products.find(p => p.id === productId);
    const existingItem = cart.find(item => item.id === productId);

    if (existingItem) {
        existingItem.quantity += 1;
    } else {
            cart.push({
                ...product,
                quantity: 1
            });
    }

    updateCartCount();
    updateCartDisplay();
    saveCartToLocalStorage();

//Show confirmation//
    showNotification(`${product.name} added to cart!`);
}

//Remove item from cart//
function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCartCount();
    updateCartDisplay();
    saveCartToLocalStorage();
}

//Update quantity in cart//
function updateQuantity(productId, change) {
    const item = cart.find(item => item.id === productId);
    if (item) {
        item.quantity += change;
        if (item.quantity <= 0) {
            removeFromCart(productId);
        } else {
            updateCartCount();
            updateCartDisplay();
            saveCartToLocalStorage();
        }
    }
}

//show / hide cart function//
function setupCartToggle() {
    const cartBtn = document.getElementById('cartBtn');
    const cartSidebar = document.getElementById('cartSidebar');
    const closeCart = document.getElementById('closeCart');

    //show cart when btn clicked//
    cartBtn.addEventListener('click', () => {
        cartSidebar.classList.add('active');

        updateCartDisplay();
        //refresh contents//
    });

    closeCart.addEventListener('click', () => {
        cartSidebar.classList.remove('active');
    });

    //hide cart clicking outside//
    document.addEventListener('click', (e) => {
        if (!cartSidebar.contains(e.target) && !cartBtn.contains(e.target)) {
            
            cartSidebar.classList.remove('active');
        }
    });

    //close cart with escape key//
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            cartSidebar.classList.remove('active');
        }
    });
}

//initialize cart toggle when page loads//
document.addEventListener('DOMContentLoaded', function() {
    setupCartToggle();
});

//cart toggle overlay//
function toggleCart() {
    const cartSidebar = document.getElementById('cartSidebar');
    const overlay = document.getElementById('cartOverlay');

    cartSidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

//overlay click to close//
document.getElementById('cartOverlay').addEventListener('click', toggleCart);

//Update cart display//
function updateCartDisplay() {
    cartItems.innerHTML = '';

    if (cart.length === 0) {
    cartItems.innerHTML = '<p class="empty-cart">Your cart is empty</p>';
    cartTotal.textContent = '0.00';
    return;
}

let total = 0;

cart.forEach(item => {
    const itemTotal = item.price * item.quantity;
    total += itemTotal;

    const cartItem = document.createElement('div');
    cartItem.className = 'cart-item';
    cartItem.innerHTML = `<img src="${item.image}" alt="${item.name}" class="cart-item-image">
    <div class="cart-item-details">
        <div class="cart-item-title">${item.name}</div>
        <div class="cart-item-price">GHS${item.price}</div>
        <div class="cart-item-quantity">
        <button class="quantity-btn" onclick="updateQuantity(${item.id}, -1)">-</button>
        <span>${item.quantity}</span>
        <button class="quantity-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
        </div>
    </div>
    <button class="remove-item" onclick="removeFromCart(${item.id})"><i class="fas fa-trash"></i>
    </button>
    `;

    cartItems.appendChild(cartItem);
});

cartTotal.textContent = total.toFixed(2);
}

//Update cart count badge//
function updateCartCount() {
    const count = cart.reduce((total, item) => total + item.quantity, 0);
    cartCount.textContent = count;
}

//Toggle wishlist//
function toggleWishlist(productId) {
    const index = wishlist.indexOf(productId);

    if (index === -1) {
        wishlist.push(productId);
        showNotification('Added to wishlist!');
    } else {
        wishlist.splice(index, 1);
        showNotification('Removed from wishlist!');
    }

    updateWishlistCount();
    saveWishlistToLocalStorage();
}

//Update wishlist count//
function updateWishlistCount() {
    wishlistCount.textContent = wishlist.length;
}

//Show product modal//
function showProductModal(product) {
    modalBody.innerHTML = `
        <div class="modal-product">
            <img src="${product.image}" alt="${product.name}" class="modal-image">
            <div class="modal-details">
                <h2>${product.name}</h2>
                <div class="modal-price">GHS${product.price}</div>
                <div class="modal-rating">${createStarRating(product.rating)}</div>
                <p class="modal-description">${product.description}</p>
                <div class="modal-actions">
                    <button class="cta-btn" onclick="addToCart(${product.id}); document.getElementById('productModal').style.display = 'none'">Add to Cart</button>
                    <button class="action-btn wishlist" onclick="toggleWishlist(${product.id})"><i class="fas fa-heart"></i>Wishlist</button>
                </div>
            </div>
        </div>
    `;

    if (productModal) productModal.style.display = 'block';
}

//Filter products by category//
function filterProducts() {
    currentPage = 1;
    displayProducts(getFilteredProducts());
}

//Search products//
function searchProducts() {
    const searchTerm = searchInput.value.toLowerCase();

    if (searchTerm.trim() === '') {
        currentPage = 1;
        displayProducts(getFilteredProducts());
        return;
    }

    const filteredProducts = products.filter(product =>
        product.name.toLowerCase().includes(searchTerm) ||
        product.description.toLowerCase().includes(searchTerm)
    );
    currentPage = 1;
    displayProducts(filteredProducts);
}

//Show notification//
function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    notification.style.cssText = `
    position: fixed;
    top: 100px;
    right: 20px;
    background: #4a90e2;
    color: white;
    padding: 1rem 2rem;
    border-radius: 4px;
    z-index: 1003;
    animation: slideIn 0.3s ease;
`;

document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

//Scroll to products section//
function scrollToProducts() {
    document.getElementById('products').scrollIntoView({
        behavior: 'smooth'
    });
}

//Save cart to localStorage//
function saveCartToLocalStorage() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

//Save wishlist to localStorage//
function saveWishlistToLocalStorage() {
    localStorage.setItem('wishlist', JSON.stringify(wishlist));
}

//Event listeners//
function setupEventListeners() {
    //cart toggle//
    cartBtn.addEventListener('click', () => {
        cartSidebar.classList.add('active');
    });

    //wishlist button: go to wishlist page//
    if (wishlistBtn) {
        wishlistBtn.addEventListener('click', () => {
            window.location.href = 'wishlist.html';
        });
    }

    closeCart.addEventListener('click', () => {
        cartSidebar.classList.remove('active');
    });

    //checkout button//
    checkoutBtn.addEventListener('click', () => {
        if (cart.length === 0) {
            showNotification('Your cart is empty!');
            return;
        }
        showNotification('Proceeding to checkout...');
    });

    //filters//
    categoryFilter.addEventListener('change', filterProducts);

    sortFilter.addEventListener('change', filterProducts);

    //Search//
    searchInput.addEventListener('input', searchProducts);

    //Modal close: attach to all close buttons//
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
        });
    });

    //Close modal clicking outside any modal//
    window.addEventListener('click', (e) => {
        if (e.target && e.target.classList && e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });

    //Category cards//
    document.querySelectorAll('.category-card').forEach(card => {
        card.addEventListener('click', () => {
            const category = card.dataset.category;
            categoryFilter.value = category;
            filterProducts();
            scrollToProducts();
        });
    });
}

//Add CSS for notification animation//
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform:translateX(0);
            opacity: 1;
        }
    }

    .empty-cart {
        text-align: center;
        color: #666;
        padding: 2rem;
    }

    .modal-product {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        align-items; start;
    }

    .modal-image {
        width: 100%;
        border-radius; 8px;
    }

    .modal-details h2 {
        margin-bottom: 1rem;
    }

    .modal-price {
        font-size: 1.5rem;
        color: #4a90e2;
        font-weight: bold;
        margin-bottom: 1rem;
    }

    .modal-rating {
        margin-bottom: 1rem;
    }

    .modal-description {
        margin-bottom; 2rem;
        line-height: 1.6;
    }

    .modal-actions {
        display: flex;
        gap: 1rem;
    }

    @media (max-width: 768px) {
        .modal-product {
            grid-template-columns: 1fr;
        }
    }
`;
document.head.appendChild(style);

//Initialize app when DOM is loaded//
// Modal helpers for auth modals
function showLoginModal() {
    const m = document.getElementById('loginModal');
    if (m) m.style.display = 'block';
}

function showRegisterModal() {
    const m = document.getElementById('registerModal');
    if (m) m.style.display = 'block';
}

function closeModal(id) {
    const m = document.getElementById(id);
    if (m) m.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', init);