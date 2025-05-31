// Function to fetch books from the server
function fetchBooks(search = "", genre = "", language = "", sort = "") {
    return fetch(
      `books.php?action=getBooks&search=${encodeURIComponent(search)}&genre=${encodeURIComponent(genre)}&language=${encodeURIComponent(language)}&sort=${encodeURIComponent(sort)}&type=physical`,
    )
      .then((response) => response.json())
      .catch((error) => {
        console.error("Error fetching books:", error)
        return []
      })
  }
  
  // Store books data globally for reference
  window.booksData = []
  
  // Function to load books with optional filters
  async function loadBooks() {
    const searchInput = document.getElementById("search").value
    const genreFilter = document.getElementById("genre-filter").value
    const languageFilter = document.getElementById("language-filter").value
    const sortOption = document.getElementById("sort-books").value
  
    const books = await fetchBooks(searchInput, genreFilter, languageFilter, sortOption)
    // Store books data globally
    window.booksData = books
  
    // Call the render function
    renderBooks(books)
  }
  
  // Function to render books using the new book card styling
  function renderBooks(books) {
    const booksContainer = document.getElementById("books")
    booksContainer.innerHTML = ""
  
    if (books.length === 0) {
      booksContainer.innerHTML = "<p class='no-books'>No books found matching your criteria.</p>"
      return
    }
  
    books.forEach((book) => {
      // Check if book is in wishlist
      const isInWishlist = checkIfInWishlist(book.id)
  
      const bookElement = document.createElement("div")
      bookElement.className = "book fade-in-up"
      bookElement.dataset.id = book.id
  
      bookElement.innerHTML = `
              <div class="book-cover">
                  <img src="${book.image_link || "placeholder.jpg"}" alt="${book.title}">
                  <div class="book-overlay">
                      <button class="btn-cart add-to-cart" data-id="${book.id}">
                          <img src="imgs/cart.png" alt="Add to Cart" class="cart-icon">
                      </button>
                      ${
                        document.getElementById("user_logged_in").value === "true"
                          ? `
                      <button class="toggle-wishlist" data-id="${book.id}">
                          ${isInWishlist ? "❤️" : "🤍"}
                      </button>
                      `
                          : ""
                      }
                  </div>
              </div>
              <div class="book-info">
                  <div class="book-title-container">
                      <h3 class="book-title">${book.title}</h3>
                  </div>
                  <div class="book-author-container">
                      <p class="book-author">${book.author}</p>
                  </div>
                  <div class="book-details-container">
                      <div class="book-details">
                          <span class="book-year">${book.year || ""}</span>
                          <span class="book-price">$${Number.parseFloat(book.price).toFixed(2)}</span>
                      </div>
                  </div>
                  <div class="btn-details-container">
                      <a href="book-details.php?id=${book.id}" class="btn-details">Show Details</a>
                  </div>
              </div>
          `
  
      booksContainer.appendChild(bookElement)
    })
  
    // Add event listeners for cart and wishlist buttons
    document.querySelectorAll(".add-to-cart").forEach((button) => {
      button.addEventListener("click", function (e) {
        e.preventDefault()
        addToCart(this.dataset.id)
      })
    })
  
    document.querySelectorAll(".toggle-wishlist").forEach((button) => {
      button.addEventListener("click", function (e) {
        e.preventDefault()
        toggleWishlist(this, this.dataset.id)
      })
    })
  }
  
  // Check if a book is in the wishlist
  function checkIfInWishlist(bookId) {
    const wishlistItems = JSON.parse(document.getElementById("wishlist_items").value || "[]")
    return wishlistItems.some((item) => item.book_id == bookId)
  }
  
  // Function to toggle wishlist item
  async function toggleWishlist(element, bookId) {
    // Check if user is logged in
    const userLoggedIn = document.getElementById("user_logged_in").value === "true"
    if (!userLoggedIn) {
      showPopup("Please log in to manage your wishlist")
      setTimeout(() => {
        window.location.href = "login.php"
      }, 1500)
      return
    }
  
    // Toggle visual state immediately for better UX
    const isInWishlist = element.textContent.trim() === "❤️"
    const action = isInWishlist ? "remove" : "add"
  
    // Update UI immediately
    element.textContent = isInWishlist ? "🤍" : "❤️"
  
    try {
      const response = await fetch("add_to_wishlist.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `book_id=${bookId}&action=${action}&csrf_token=${document.getElementById("csrf_token").value}`,
      })
  
      const data = await response.json()
  
      if (data.success) {
        showPopup(data.message || (action === "add" ? "Added to wishlist" : "Removed from wishlist"))
        updateWishlistCounter(data.wishlist_count)
      } else {
        // Revert UI if failed
        element.textContent = isInWishlist ? "❤️" : "🤍"
        showPopup(data.message || "Operation failed")
  
        if (data.message === "login_required") {
          setTimeout(() => {
            window.location.href = "login.php"
          }, 1500)
        }
      }
    } catch (error) {
      // Revert UI if error
      element.textContent = isInWishlist ? "❤️" : "🤍"
      console.error("Error:", error)
      showPopup("Network error. Please try again.")
    }
  }
  
  // Function to update wishlist counter
  function updateWishlistCounter(count) {
    const wishlistLink = document.querySelector('a[href="/bookstore/wishlist.php"]')
    if (!wishlistLink) return
  
    let countElement = wishlistLink.querySelector(".wishlist-count")
  
    if (count > 0) {
      if (!countElement) {
        countElement = document.createElement("span")
        countElement.className = "wishlist-count"
        wishlistLink.appendChild(countElement)
      }
      countElement.textContent = count
      countElement.style.display = "inline-block"
    } else if (countElement) {
      countElement.style.display = "none"
    }
  }
  
  // Function to show popup message
  function showPopup(message) {
    const popup = document.createElement("div")
    popup.classList.add("popup")
    popup.textContent = message
    document.body.appendChild(popup)
  
    setTimeout(() => {
      popup.remove()
    }, 2000)
  }
  
  // Function to show book details in modal
  async function showDetails(bookId) {
    const response = await fetch(`books.php?action=getBook&id=${encodeURIComponent(bookId)}`)
    const book = await response.json()
  
    if (!book) {
      console.error("Book not found:", bookId)
      return
    }
  
    const modalBody = document.getElementById("modal-body")
    if (!modalBody) {
      console.error("Modal body not found!")
      return
    }
  
    // Check if book is in wishlist
    const isInWishlist = checkIfInWishlist(book.id)
    const wishlistButtonHtml = isInWishlist
      ? `<button class="btn-remove-wishlist">Remove from Wishlist</button>`
      : `<button class="btn-add-wishlist">Add to Wishlist</button>`
  
    modalBody.innerHTML = `
          <div class="modal-content-container">
              <div class="modal-img">
                  <img src="${book.image_link}" alt="${book.title}">
              </div>
              <div class="modal-text">
                  <h2>${book.title}</h2>
                  <p><strong>Author:</strong> ${book.author}</p>
                  <p><strong>Language:</strong> ${book.language || "N/A"}</p>
                  <p><strong>Genre:</strong> ${book.genre || "N/A"}</p>
                  <p><strong>Price:</strong> $${Number.parseFloat(book.price).toFixed(2)}</p>
                  <p><strong>Rating:</strong> ${book.rating ? book.rating + " ★" : "N/A"}</p>
                  <p><strong>Year:</strong> ${book.year || "N/A"}</p>
                  <p><strong>Abstract:</strong> ${book.abstract || "No description available."}</p>
                  <div class="modal-buttons">
                      <button class="add-to-cart-modal" data-id="${book.id}">Add to Cart</button>
                      <button class="toggle-wishlist-modal" data-id="${book.id}" data-in-wishlist="${isInWishlist}">
                          ${isInWishlist ? "Remove from Wishlist" : "Add to Wishlist"}
                      </button>
                      <a href="book-details.php?id=${book.id}" class="btn-details">View Full Details</a>
                  </div>
              </div>
          </div>
      `
  
    // Add event listeners for modal buttons
    const addToCartBtn = modalBody.querySelector(".add-to-cart-modal")
    if (addToCartBtn) {
      addToCartBtn.addEventListener("click", function () {
        addToCart(this.dataset.id)
      })
    }
  
    const toggleWishlistBtn = modalBody.querySelector(".toggle-wishlist-modal")
    if (toggleWishlistBtn) {
      toggleWishlistBtn.addEventListener("click", function () {
        const bookId = this.dataset.id
        const isInWishlist = this.dataset.inWishlist === "true"
  
        // Find the corresponding wishlist button in the book grid
        const wishlistBtn = document.querySelector(`.toggle-wishlist[data-id="${bookId}"]`)
        if (wishlistBtn) {
          toggleWishlist(wishlistBtn, bookId)
          // Update modal button text
          this.textContent = isInWishlist ? "Add to Wishlist" : "Remove from Wishlist"
          this.dataset.inWishlist = !isInWishlist
        } else {
          // If button not found in grid, handle directly
          const action = isInWishlist ? "remove" : "add"
          fetch("add_to_wishlist.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `book_id=${bookId}&action=${action}&csrf_token=${document.getElementById("csrf_token").value}`,
          })
            .then((response) => response.json())
            .then((data) => {
              if (data.success) {
                showPopup(data.message || (action === "add" ? "Added to wishlist" : "Removed from wishlist"))
                updateWishlistCounter(data.wishlist_count)
                // Update button text
                this.textContent = isInWishlist ? "Add to Wishlist" : "Remove from Wishlist"
                this.dataset.inWishlist = !isInWishlist
              } else {
                showPopup(data.message || "Operation failed")
              }
            })
            .catch((error) => {
              console.error("Error:", error)
              showPopup("Network error. Please try again.")
            })
        }
      })
    }
  
    // Show modal
    document.getElementById("modal").style.display = "flex"
  }
  
  // Function to add book to cart
  function addToCart(bookId) {
    const book = findBookById(bookId)
    if (!book) return
  
    fetch("add_to_cart.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        book_id: bookId,
        title: book.title,
        author: book.author,
        price: book.price,
        image: book.image_link,
        csrf_token: document.getElementById("csrf_token").value,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showPopup("Book added to cart!")
          updateCartCount(data.cart_count)
        } else {
          if (data.message === "login_required") {
            showPopup("Please log in to add items to your cart")
            setTimeout(() => {
              window.location.href = "login.php"
            }, 1500)
          } else {
            showPopup(data.message || "Error adding to cart")
          }
        }
      })
      .catch((error) => {
        console.error("Error:", error)
        showPopup("Error adding to cart. Please try again.")
      })
  }
  
  // Helper function to find book by ID
  function findBookById(bookId) {
    const books = window.booksData || []
    return books.find((book) => book.id == bookId)
  }
  
  // Update cart count
  function updateCartCount(count) {
    const cartCountElement = document.querySelector(".cart-count")
    if (cartCountElement) {
      cartCountElement.textContent = count
    } else {
      const cartLink = document.querySelector('a[href="/bookstore/cart.php"]')
      const countSpan = document.createElement("span")
      countSpan.className = "cart-count"
      countSpan.textContent = count
      cartLink.appendChild(countSpan)
    }
  }
  
  // Function to close modal
  function closeModal() {
    const modal = document.getElementById("modal")
    if (modal) {
      modal.style.display = "none"
    }
  }
  
  // Initialize page when document is ready
  document.addEventListener("DOMContentLoaded", () => {
    // Load books on page load
    loadBooks()
  
    // Search input listener
    const searchInput = document.getElementById("search")
    if (searchInput) {
      searchInput.addEventListener("input", loadBooks)
    }
  
    // Filter select listeners
    const genreFilter = document.getElementById("genre-filter")
    if (genreFilter) {
      genreFilter.addEventListener("change", loadBooks)
    }
  
    const languageFilter = document.getElementById("language-filter")
    if (languageFilter) {
      languageFilter.addEventListener("change", loadBooks)
    }
  
    // Sort select listener
    const sortBooks = document.getElementById("sort-books")
    if (sortBooks) {
      sortBooks.addEventListener("change", loadBooks)
    }
  
    // Close modal listener
    const closeButton = document.getElementById("close-modal")
    if (closeButton) {
      closeButton.addEventListener("click", closeModal)
    }
  
    // Close modal when clicking outside
    window.addEventListener("click", (event) => {
      const modal = document.getElementById("modal")
      if (event.target === modal) {
        closeModal()
      }
    })
  })