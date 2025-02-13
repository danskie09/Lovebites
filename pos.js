class POS {
    constructor() {
        this.currentOrder = {
            items: [],
            paymentMethod: 'card'
        };
        this.init();
    }

    /**
     * Initialize the POS system
     */
    async init() {
        await this.loadCategories();
        await this.loadProducts();
        this.setupEventListeners();
    }

    /**
     * Load and render categories
     */
    async loadCategories() {
        try {
            const response = await fetch('api.php/categories');
            const categories = await response.json();
            this.renderCategories(categories);
        } catch (error) {
            console.error('Error loading categories:', error);
        }
    }

    /**
     * Load and render products based on the selected category
     * @param {number|null} categoryId 
     */
    async loadProducts(categoryId = null) {
        try {
            const url = categoryId ? 
                `api.php/products?category_id=${categoryId}` : 
                'api.php/products';
            const response = await fetch(url);
            const products = await response.json();
            this.renderProducts(products);
        } catch (error) {
            console.error('Error loading products:', error);
        }
    }

    /**
     * Render category menu items
     * @param {Array} categories
     */
    renderCategories(categories) {
        const menuContainer = document.querySelector('.category-menu');
        menuContainer.innerHTML = categories.map(category => `
            <div class="category-item" data-category-id="${category.id}">
                <div class="category-icon"><i class="fas ${category.icon}"></i></div>
                <span>${category.name}</span>
            </div>
        `).join('');
    }

    /**
     * Render product grid
     * @param {Array} products
     */
    renderProducts(products) {
        const gridContainer = document.querySelector('.product-grid');
        gridContainer.innerHTML = products.map(product => `
            <div class="product-card" data-product-id="${product.id}">
                <img src="${product.image_url}" alt="${product.name}" class="product-image">
                <div class="product-info">
                    <div class="product-name">${product.name}</div>
                    <div class="product-price">₱${parseFloat(product.price).toFixed(2)}</div>
                </div>
            </div>
        `).join('');
    }

    /**
     * Add an item to the current order
     * @param {Object} product
     */
    addItemToOrder(product) {
        const existingItem = this.currentOrder.items.find(item => item.id === product.id);
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            this.currentOrder.items.push({
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                quantity: 1,
                image_url: product.image_url
            });
        }
        this.updateBillItems();
    }

    /**
     * Update item quantity in the order
     * @param {number} productId
     * @param {number} change
     */
    updateItemQuantity(productId, change) {
        const item = this.currentOrder.items.find(item => item.id === productId);
        if (item) {
            item.quantity += change;
            if (item.quantity <= 0) {
                this.currentOrder.items = this.currentOrder.items.filter(i => i.id !== productId);
            }
            this.updateBillItems();
        }
    }

    /**
     * Update the bill items section
     */
    updateBillItems() {
        const billItemsContainer = document.querySelector('.bill-items');
        billItemsContainer.innerHTML = this.currentOrder.items.map(item => `
            <div class="bill-item" data-product-id="${item.id}">
                <div class="item-info">
                    <img src="${item.image_url}" alt="${item.name}" class="item-image">
                    <div>
                        <div>${item.name}</div>
                        <div class="product-price">₱${item.price.toFixed(2)}</div>
                    </div>
                </div>
                <div class="quantity-controls">
                    <button class="quantity-btn decrease" data-product-id="${item.id}">-</button>
                    <span>${item.quantity}</span>
                    <button class="quantity-btn increase" data-product-id="${item.id}">+</button>
                </div>
            </div>
        `).join('');
        this.updateBillSummary();
    }

    /**
     * Update the bill summary section
     */
    updateBillSummary() {
        const total = this.currentOrder.items.reduce(
            (sum, item) => sum + (item.price * item.quantity), 0
        );

        document.querySelector('.bill-summary').innerHTML = `
            <div class="summary-row">
                <span>Total</span>
                <span>₱${total.toFixed(2)}</span>
            </div>
        `;
    }

    /**
     * Submit the order
     */
    async submitOrder() {
        if (this.currentOrder.items.length === 0) {
            alert('Please add items to the order before checking out.');
            return;
        }

        try {
            // Changed the API endpoint to match PHP backend
            const response = await fetch('submit_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    items: this.currentOrder.items.map(item => ({
                        product_id: item.id,
                        quantity: item.quantity,
                        price: item.price,
                        name: item.name
                    })),
                    payment_method: this.currentOrder.paymentMethod,
                    total_amount: this.calculateTotal()
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            console.log('Order submission response:', result); // Debug log

            if (result.success) {
                alert(`Order #${result.order_id} completed successfully!`);
                this.resetOrder();
            } else {
                alert('Failed to submit order: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error submitting order:', error);
            alert('Failed to submit order. Please try again. Error: ' + error.message);
        }
    }

    calculateTotal() {
        return this.currentOrder.items.reduce(
            (sum, item) => sum + (item.price * item.quantity), 0
        );
    }

    /**
     * Reset the current order
     */
    resetOrder() {
        this.currentOrder = {
            items: [],
            paymentMethod: 'card'
        };
        this.updateBillItems();
    }

    /**
     * Setup event listeners for various UI interactions
     */
    setupEventListeners() {
        document.querySelector('.category-menu').addEventListener('click', (e) => {
            const categoryItem = e.target.closest('.category-item');
            if (categoryItem) {
                document.querySelectorAll('.category-item').forEach(item => 
                    item.classList.remove('active'));
                categoryItem.classList.add('active');
                this.loadProducts(categoryItem.dataset.categoryId);
            }
        });

        document.querySelector('.product-grid').addEventListener('click', (e) => {
            const productCard = e.target.closest('.product-card');
            if (productCard) {
                const productId = parseInt(productCard.dataset.productId);
                const productName = productCard.querySelector('.product-name').textContent;
                const productPrice = parseFloat(productCard.querySelector('.product-price').textContent.slice(1));
                const productImage = productCard.querySelector('.product-image').src;

                this.addItemToOrder({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    image_url: productImage
                });
            }
        });

        document.querySelector('.bill-items').addEventListener('click', (e) => {
            if (e.target.classList.contains('quantity-btn')) {
                const productId = parseInt(e.target.dataset.productId);
                const change = e.target.classList.contains('increase') ? 1 : -1;
                this.updateItemQuantity(productId, change);
            }
        });

        document.querySelector('.payment-methods').addEventListener('click', (e) => {
            const paymentMethod = e.target.closest('.payment-method');
            if (paymentMethod) {
                document.querySelectorAll('.payment-method').forEach(method => 
                    method.classList.remove('active'));
                paymentMethod.classList.add('active');
                this.currentOrder.paymentMethod = paymentMethod.querySelector('div').textContent.toLowerCase();
            }
        });

        document.querySelector('.search-bar input').addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const productCards = document.querySelectorAll('.product-card');

            productCards.forEach(card => {
                const productName = card.querySelector('.product-name').textContent.toLowerCase();
                card.style.display = productName.includes(searchTerm) ? 'block' : 'none';
            });
        });

        document.querySelector('.checkout-btn').addEventListener('click', () => {
            this.submitOrder();
        });
    }
}

// Instantiate the POS system
const pos = new POS();
