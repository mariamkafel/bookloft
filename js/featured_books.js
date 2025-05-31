// featured_books.js - Updated version

// Function to fetch featured books from the server
function fetchFeaturedBooks() {
    return fetch('featured_books.php?action=getFeaturedBooks')
        .then(response => response.json())
        .catch(error => {
            console.error('Error fetching featured books:', error);
            return [];
        });
}

// Function to load and display featured books
async function loadFeaturedBooks() {
    const books = await fetchFeaturedBooks();
    const container = document.getElementById('featured-books');
    
    if (!container) return;
    
    container.innerHTML = '';
    
    if (books.length === 0) {
        container.innerHTML = '<p>No featured books available at this time.</p>';
        return;
    }
    
    books.forEach(book => {
        const bookElement = document.createElement('div');
        bookElement.classList.add('book');
        
        // Check if book is in wishlist
        const isInWishlist = book.in_wishlist === true;
        const heartHtml = isInWishlist ? "&#10084;" : "&#9825;";
        const heartClass = isInWishlist ? "wishlist-added" : "";
        
        bookElement.innerHTML = `
            <div class="book-image-container">
                <img src="${book.image_link}" alt="${book.title}" class="book-image" onclick="showFeaturedBookDetails(${book.id})">
            </div>
            <div class="book-info">
                <h3>${book.title}</h3>
                <p>by ${book.author}</p>
                <p>$${parseFloat(book.price).toFixed(2)}</p>
            </div>
            <div class="book-actions">
                <span onclick="toggleWishlist(this, ${book.id})" class="wishlist-heart ${heartClass}" data-book-id="${book.id}">${heartHtml}</span>
                <button onclick="event.stopPropagation(); addToCart('${book.title}')" class="add-to-cart-btn t">Add to Cart</button>
            </div>
        `;
        
        container.appendChild(bookElement);
    });
}

// Function to show featured book details
async function showFeaturedBookDetails(bookId) {
    const response = await fetch(`featured_books.php?action=getBookDetails&id=${bookId}`);
    const book = await response.json();
    
    if (!book) {
        console.error("Book not found:", bookId);
        return;
    }

    const modalBody = document.getElementById("featured-book-modal-body");
    if (!modalBody) return;
    
    // Check if book is in wishlist
    let wishlistButtonHtml = book.in_wishlist 
        ? `<button onclick="toggleWishlist(document.querySelector('.wishlist-heart[data-book-id=\\'${book.id}\\']'), ${book.id})" class="btn-remove-wishlist">Remove from Wishlist</button>`
        : `<button onclick="toggleWishlist(document.querySelector('.wishlist-heart[data-book-id=\\'${book.id}\\']'), ${book.id})" class="btn-add-wishlist">Add to Wishlist</button>`;

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
                <p><strong>Price:</strong> $${parseFloat(book.price).toFixed(2)}</p>
                <p><strong>Rating:</strong> ${book.rating ? book.rating + " &#9733;" : "N/A"}</p>
                <p><strong>Year:</strong> ${book.year || "N/A"}</p>
                <p><strong>Abstract:</strong> ${book.abstract || "No description available."}</p>
                <div class="modal-buttons">
                    <button onclick="addToCart('${book.title}')" class="t">Add to Cart</button>
                    ${wishlistButtonHtml}
                </div>
            </div>
        </div>
    `;
    
    // Show modal
    document.getElementById("featured-book-modal").style.display = "block";
}

// Function to close featured book modal
function closeFeaturedBookModal() {
    document.getElementById("featured-book-modal").style.display = "none";
}

// Initialize page when document is ready
document.addEventListener("DOMContentLoaded", function() {
    // Load featured books on page load
    loadFeaturedBooks();
    
    // Set up modal close button
    const closeButton = document.getElementById("close-featured-book-modal");
    if (closeButton) {
        closeButton.addEventListener("click", closeFeaturedBookModal);
    }
    
    // Close modal when clicking outside
    window.addEventListener("click", function(event) {
        const modal = document.getElementById("featured-book-modal");
        if (event.target === modal) {
            closeFeaturedBookModal();
        }
    });
});