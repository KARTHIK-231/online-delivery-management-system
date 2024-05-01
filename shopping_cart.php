<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="styles.css"> <!-- Assuming you have a separate CSS file named styles.css -->
</head>
<body>
    <h2>Shopping Cart</h2>
    
    <?php
        // Include database connection
        include 'db_connection.php';
        
        // Start the session to access session variables
        session_start(); 
        
        // Check if the user is logged in
        if(isset($_SESSION['buyer_id'])) {
            // Check if the cart session is set
            if(isset($_SESSION['cart'])) {
                $current_user_id = $_SESSION['buyer_id'];
                $query = "SELECT * FROM Cart WHERE buyer_id = $current_user_id";
                $result = mysqli_query($conn, $query);
                
                // Check if the user has items in the cart
                if(mysqli_num_rows($result) > 0) {
                    // Display items in the shopping cart
                    foreach($_SESSION['cart'] as $product_id => $quantity) {
                        // Fetch product details from the database based on product_id
                        $query = "SELECT * FROM Products WHERE product_id = $product_id";
                        $result = mysqli_query($conn, $query);
                        
                        // Check if product exists
                        if(mysqli_num_rows($result) > 0) {
                            $product = mysqli_fetch_assoc($result);
                            
                            // Display product details in the cart
                            echo '<div class="cart-item">';
                            echo '<h3>' . $product['name'] . '</h3>';
                            echo '<p>Price: $' . $product['price'] . '</p>';
                            echo '<p>Quantity: ' . $quantity . '</p>';
                            echo '<button onclick="removeFromCart(' . $product_id . ')">Remove</button>';
                            echo '</div>';
                        }
                    }
                    
                    // Provide a "Proceed to Checkout" button
                    echo '<button id="checkoutBtn" onclick="proceedToCheckout()">Proceed to Checkout</button>';
                } else {
                    // If the user has no items in the cart, display a message
                    echo '<p>Your shopping cart is empty</p>';
                }
            } else {
                // If the cart session is not set, display a message
                echo '<p>Your shopping cart is empty</p>';
            }
        } else {
            // If the user is not logged in, prompt them to log in
            echo '<p>Please log in to view your shopping cart</p>';
        }
        
        // Close database connection
        mysqli_close($conn);
    ?>
    
    <script>
        function removeFromCart(productId) {
            // Send AJAX request to remove the product from the cart
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'remove_from_cart.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        // Item removed successfully, update the cart dynamically
                        location.reload(); // Reload the page to reflect changes
                    } else {
                        // Handle error
                        console.error('Error removing item from cart');
                    }
                }
            };
            xhr.send('product_id=' + productId);
        }
        
        function proceedToCheckout() {
            // Redirect the user to the checkout page
            window.location.href = 'checkout.php';
        }

        // Check if the cart is empty and disable the checkout button
        if (document.querySelectorAll('.cart-item').length === 0) {
            document.getElementById('checkoutBtn').disabled = true;
        }
    </script>
</body>
</html>
