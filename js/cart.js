
document.addEventListener("DOMContentLoaded", () => {
    updateCartDisplay()
  
    document.querySelectorAll(".add-to-cart-btn").forEach((button) => {
      button.addEventListener("click", addToCartFromButton)
    })
  
    document.getElementById("clear-cart-btn").addEventListener("click", clearCart)
    document.getElementById("checkout-btn").addEventListener("click", checkout)
  
    updateCartCount()
  })
  
  function getCart() {
    return JSON.parse(localStorage.getItem("bookLoftCart")) || []
  }
  
  function saveCart(cart) {
    localStorage.setItem("bookLoftCart", JSON.stringify(cart))
  }
  
  function addToCart(item) {
    const cart = getCart()
  
    const existingItemIndex = cart.findIndex((cartItem) => cartItem.id === item.id)
  
    if (existingItemIndex !== -1) {
      cart[existingItemIndex].quantity += 1
    } else {
      cart.push({
        ...item,
        quantity: 1,
      })
    }
  
    saveCart(cart)
    updateCartDisplay()
    updateCartCount()
  }
  
  function addToCartFromButton(event) {
    const button = event.currentTarget
    const item = {
      id: button.dataset.id,
      title: button.dataset.title,
      author: button.dataset.author,
      price: Number.parseFloat(button.dataset.price),
      image: button.dataset.image,
    }
  
    addToCart(item)
  }
  
  function updateCartDisplay() {
    const cart = getCart()
    const cartItemsContainer = document.getElementById("cart-items")
    const cartEmptyMessage = document.getElementById("cart-empty-message")
    const cartSummary = document.getElementById("cart-summary")
  
    cartItemsContainer.innerHTML = ""
  
    if (cart.length === 0) {
      cartEmptyMessage.style.display = "block"
      cartSummary.style.display = "none"
      return
    }
  
    cartEmptyMessage.style.display = "none"
    cartSummary.style.display = "block"
  
    cart.forEach((item) => {
      const cartItemElement = document.createElement("div")
      cartItemElement.className = "cart-item"
      cartItemElement.innerHTML = `
            <img src="${item.image}" alt="${item.title}" class="cart-item-image">
            <div class="cart-item-details">
                <h3 class="cart-item-title">${item.title}</h3>
                <p class="cart-item-author">${item.author}</p>
                <p class="cart-item-price">$${(item.price * item.quantity).toFixed(2)}</p>
                <div class="cart-item-quantity">
                    <button class="quantity-btn decrease" data-id="${item.id}">-</button>
                    <input type="number" class="quantity-input" value="${item.quantity}" min="1" data-id="${item.id}">
                    <button class="quantity-btn increase" data-id="${item.id}">+</button>
                </div>
            </div>
            <button class="remove-item" data-id="${item.id}">&times;</button>
        `
  
      cartItemsContainer.appendChild(cartItemElement)
    })
  
    document.querySelectorAll(".quantity-btn.decrease").forEach((button) => {
      button.addEventListener("click", decreaseQuantity)
    })
  
    document.querySelectorAll(".quantity-btn.increase").forEach((button) => {
      button.addEventListener("click", increaseQuantity)
    })
  
    document.querySelectorAll(".quantity-input").forEach((input) => {
      input.addEventListener("change", updateQuantityFromInput)
    })
  
    document.querySelectorAll(".remove-item").forEach((button) => {
      button.addEventListener("click", removeItem)
    })
  
    updateCartSummary()
  }
  
  function decreaseQuantity(event) {
    const itemId = event.currentTarget.dataset.id
    const cart = getCart()
  
    const itemIndex = cart.findIndex((item) => item.id === itemId)
  
    if (itemIndex !== -1) {
      if (cart[itemIndex].quantity > 1) {
        cart[itemIndex].quantity -= 1
        saveCart(cart)
        updateCartDisplay()
        updateCartCount()
      } else {
        removeItemById(itemId)
      }
    }
  }
  
  function increaseQuantity(event) {
    const itemId = event.currentTarget.dataset.id
    const cart = getCart()
  
    const itemIndex = cart.findIndex((item) => item.id === itemId)
  
    if (itemIndex !== -1) {
      cart[itemIndex].quantity += 1
      saveCart(cart)
      updateCartDisplay()
      updateCartCount()
    }
  }
  
  function updateQuantityFromInput(event) {
    const itemId = event.currentTarget.dataset.id
    const newQuantity = Number.parseInt(event.currentTarget.value)
  
    if (newQuantity < 1) {
      event.currentTarget.value = 1
      return
    }
  
    const cart = getCart()
    const itemIndex = cart.findIndex((item) => item.id === itemId)
  
    if (itemIndex !== -1) {
      cart[itemIndex].quantity = newQuantity
      saveCart(cart)
      updateCartDisplay()
      updateCartCount()
    }
  }
  
  function removeItem(event) {
    const itemId = event.currentTarget.dataset.id
    removeItemById(itemId)
  }
  
  function removeItemById(itemId) {
    const cart = getCart()
    const updatedCart = cart.filter((item) => item.id !== itemId)
  
    saveCart(updatedCart)
    updateCartDisplay()
    updateCartCount()
  }
  
  function updateCartSummary() {
    const cart = getCart()
  
    const subtotal = cart.reduce((total, item) => total + item.price * item.quantity, 0)
  
    const tax = subtotal * 0.1
  
    const total = subtotal + tax
  
    document.getElementById("subtotal").textContent = `$${subtotal.toFixed(2)}`
    document.getElementById("tax").textContent = `$${tax.toFixed(2)}`
    document.getElementById("total").textContent = `$${total.toFixed(2)}`
  }
  
  function clearCart() {
    if (confirm("Are you sure you want to clear your cart?")) {
      saveCart([])
      updateCartDisplay()
      updateCartCount()
    }
  }
  
  function checkout() {
    alert("Thank you for your purchase! This is where the checkout process would begin.")
  }
  
  function updateCartCount() {
    const cart = getCart()
    const totalItems = cart.reduce((total, item) => total + item.quantity, 0)
  
    const cartCountElement = document.getElementById("cart-count")
    if (cartCountElement) {
      cartCountElement.textContent = totalItems
  
      if (totalItems > 0) {
        cartCountElement.style.display = "flex"
      } else {
        cartCountElement.style.display = "none"
      }
    }
  }
  
  
  