document.addEventListener('DOMContentLoaded', function() {
    // Character counter for message field
    const messageField = document.getElementById('message');
    const charCountDisplay = document.getElementById('char-count');
    
    if (messageField && charCountDisplay) {
        messageField.addEventListener('input', function() {
            const currentLength = this.value.length;
            charCountDisplay.textContent = `${currentLength}/250 characters`;
        });
    }
    
    // Gift card form submission handler
    const giftcardForm = document.getElementById('giftcard-form');
    
    if (giftcardForm) {
        giftcardForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form values
            const recipientEmail = document.getElementById('email_to').value;
            const senderEmail = document.getElementById('email_from').value;
            const message = document.getElementById('message').value;
            
            // Get selected price
            let selectedPrice = null;
            const priceOptions = document.querySelectorAll('input[name="price"]');
            for (let option of priceOptions) {
                if (option.checked) {
                    selectedPrice = option.value;
                    break;
                }
            }
            
            // Validate form
            if (!recipientEmail || !senderEmail || !selectedPrice) {
                alert('Please fill out all required fields.');
                return;
            }
            
            // Create form data for AJAX request
            const formData = new FormData();
            formData.append('item_type', 'giftcard');
            formData.append('recipient_email', recipientEmail);
            formData.append('sender_email', senderEmail);
            formData.append('value', selectedPrice);
            formData.append('message', message);
            
            // Send AJAX request to add_to_cart.php
            fetch('add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    const confirmationMessage = document.getElementById('confirmation-message');
                    if (confirmationMessage) {
                        confirmationMessage.textContent = 'Gift card added to your cart!';
                        confirmationMessage.style.display = 'block';
                        
                        // Update cart count if available
                        const cartCountElements = document.querySelectorAll('.cart-count');
                        if (cartCountElements.length > 0) {
                            cartCountElements.forEach(element => {
                                element.textContent = data.cart_count;
                                element.style.display = 'inline-block';
                            });
                        }
                        
                        // Reset form
                        giftcardForm.reset();
                        charCountDisplay.textContent = '0/250 characters';
                        
                        // Hide confirmation message after 3 seconds
                        setTimeout(() => {
                            confirmationMessage.style.display = 'none';
                        }, 3000);
                    }
                } else {
                    if (data.message === 'login_required') {
                        alert('Please log in to add items to your cart.');
                        window.location.href = 'login.php'; // Redirect to login page
                    } else {
                        alert('Error: ' + (data.message || 'Could not add gift card to cart.'));
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again later.');
            });
        });
    }
});