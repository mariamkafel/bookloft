
var jQuery = jQuery || {}

document.addEventListener("DOMContentLoaded", () => {
  updateCartCount()
  const addToCartButtons = document.querySelectorAll('.btn-cart, .book-icons button')

  addToCartButtons.forEach((button) => {
    button.addEventListener("click", function (event) {
      if (button.parentElement.tagName === "A") {
        event.preventDefault()
      }

      if (button.parentElement.href && button.parentElement.href.includes("cart.html")) {
        return
      }

      const giftcardForm = this.closest("#giftcard-form")
      if (giftcardForm) {
        event.preventDefault()
        const emailTo = document.getElementById('email_to').value
        const emailFrom = document.getElementById('email_from').value
        const selectedAmount = document.querySelector('input[name="price"]:checked')
        const message = document.getElementById('message').value

        if (!emailTo || !emailFrom || !selectedAmount) {
          alert('Please fill out all required fields.')
          return
        }

        const giftcardId = "giftcard-" + Date.now()
        const giftcard = {
          id: giftcardId,
          type: "giftcard",
          title: `Gift Card - $${selectedAmount.value}`,
          price: parseFloat(selectedAmount.value),
          recipient: emailTo,
          sender: emailFrom,
          message: message,
          image: "/imgs/giftcars.png"
        }

        addToCart(giftcard)
        
        const confirmationMessage = document.getElementById('confirmation-message')
        if (confirmationMessage) {
          confirmationMessage.style.display = 'block'
          setTimeout(() => {
            giftcardForm.reset()
            confirmationMessage.style.display = 'none'
            const charCount = document.getElementById('char-count')
            if (charCount) charCount.textContent = '0/250 characters'
          }, 3000)
        }
        
        return
      }
      const bookElement = this.closest(".book")
      if (!bookElement) return

      let bookTitle, bookAuthor, bookPrice, bookImage

      if (bookElement.querySelector(".book-title")) {
        bookTitle = bookElement.querySelector(".book-title").textContent
        bookAuthor = bookElement.querySelector(".book-author").textContent

        const priceElement = bookElement.querySelector(".book-price, .book-details .book-price")
        if (priceElement) {
          const priceText = priceElement.textContent
          bookPrice = Number.parseFloat(priceText.replace("$", ""))
          if (priceText.toLowerCase().includes("free")) {
            bookPrice = 0
          }
        } else {
          const detailsElement = bookElement.querySelector(".book-details")
          if (detailsElement) {
            const detailsText = detailsElement.textContent
            const priceMatch = detailsText.match(/\$(\d+\.\d+)/)
            bookPrice = priceMatch ? Number.parseFloat(priceMatch[1]) : 0
          } else {
            bookPrice = 0
          }
        }

        bookImage = bookElement.querySelector("img").src
      } else if (bookElement.querySelector("strong")) {
        const bookInfo = bookElement.querySelector(".book-info")
        if (bookInfo) {
          const paragraphs = bookInfo.querySelectorAll("p")
          
          for (const p of paragraphs) {
            if (p.textContent.includes("Title:")) {
              bookTitle = p.textContent.replace("Title:", "").trim()
              break
            }
          }
          
          for (const p of paragraphs) {
            if (p.textContent.includes("Author:")) {
              bookAuthor = p.textContent.replace("Author:", "").trim()
              break
            }
          }
          
          for (const p of paragraphs) {
            if (p.textContent.includes("Price:") || p.textContent.includes("price:")) {
              const priceText = p.textContent
              if (priceText.toLowerCase().includes("free")) {
                bookPrice = 0
              } else {
                const priceMatch = priceText.match(/(\d+(\.\d+)?)/)
                bookPrice = priceMatch ? Number.parseFloat(priceMatch[1]) : 0
              }
              break
            }
          }
        }
        if (!bookTitle) {
          bookTitle = "Unknown Book"
        }
        
        if (!bookAuthor) {
          bookAuthor = "Unknown Author"
        }
        
        if (bookPrice === undefined) {
          bookPrice = 0
        }

        bookImage = bookElement.querySelector("img").src
      } else {
        bookTitle = "Unknown Book"
        bookAuthor = "Unknown Author"
        bookPrice = 0
        bookImage = bookElement.querySelector("img")?.src || "/placeholder.svg"
      }

      const bookId = "book-" + Date.now()
      const book = {
        id: bookId,
        title: bookTitle,
        author: bookAuthor,
        price: bookPrice,
        image: bookImage,
      }

      addToCart(book)
      
    })
  })
})

function getCart() {
  return JSON.parse(localStorage.getItem("bookLoftCart")) || []
}

function saveCart(cart) {
  localStorage.setItem("bookLoftCart", JSON.stringify(cart))
}

function addToCart(item) {
  const cart = getCart()

  if (item.type === "giftcard") {
    cart.push({
      ...item,
      quantity: 1,
    })
  } else {
    const existingItemIndex = cart.findIndex(
      (cartItem) => cartItem.title === item.title && cartItem.author === item.author,
    )

    if (existingItemIndex !== -1) {
      cart[existingItemIndex].quantity += 1
    } else {
      cart.push({
        ...item,
        quantity: 1,
      })
    }
  }

  saveCart(cart)
  updateCartCount()
}

function updateCartCount() {
  const cart = getCart()
  const totalItems = cart.reduce((total, item) => total + (item.quantity || 1), 0)
  const cartCountElement = document.getElementById("cart-count")

  if (cartCountElement) {
    cartCountElement.textContent = totalItems
  }
}