<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Products</title>
</head>
<body>
    <!-- Link to Logout -->
    <?php
        // Start session
        session_start();

        // Check if the user is logged in
        if(isset($_SESSION['buyer_id'])) {
            echo '<a href="logout.php">Logout</a>';
        }
    ?>

    <h2>Browse Products</h2>

    <!-- Link to Shopping Cart -->
    <a href="shopping_cart.php">View Shopping Cart</a>

    <!-- Product Listings -->
    <?php
        // Include your database connection file
        include('db_connection.php');
        
        // Check if the user is logged in as a buyer
        if(isset($_SESSION['buyer_id'])) {
            // Fetch buyer's address from the session
            $buyer_id = $_SESSION['buyer_id'];
            $query_buyer = "SELECT address FROM Buyers WHERE buyer_id = $buyer_id";
            $result_buyer = mysqli_query($conn, $query_buyer);
            
            if(mysqli_num_rows($result_buyer) > 0) {
                $row_buyer = mysqli_fetch_assoc($result_buyer);
                $buyer_address = $row_buyer['address'];
                
                // Query to select products from sellers with the same address as the buyer
                $query = "SELECT DISTINCT p.* FROM Products p 
                         JOIN Sellers s ON p.seller_id = s.seller_id 
                         WHERE s.address = '$buyer_address'";

                $result = mysqli_query($conn, $query);
                
                // Check if there are any products in the database
                if(mysqli_num_rows($result) > 0) {
                    // Loop through each product and display its information
                    while($row = mysqli_fetch_assoc($result)) {
                        echo '<div class="product">';
                        echo '<h3>' . $row['name'] . '</h3>';
                        echo '<p>Description: ' . $row['description'] . '</p>';
                        echo '<p>Price: $' . $row['price'] . '</p>';
                        echo '<button onclick="addToCart(' . $row['product_id'] . ')">Add to Cart</button>'; // Add onClick event to call addToCart function
                        echo '</div>';
                    }
                } else {
                    echo '<p>No products available from sellers in your location</p>';
                }
            } else {
                echo '<p>Error fetching buyer information</p>';
            }
        } else {
            echo '<p>You must be logged in as a buyer to view products</p>';
        }

        // Close database connection
        mysqli_close($conn);
    ?>

    <!-- JavaScript Function to Add to Cart -->
    <script>
        function addToCart(productId) {
            // Send AJAX request to add the product to the cart
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'add_to_cart.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        alert('Product added to cart successfully!');
                    } else {
                        console.error('Error adding product to cart');
                    }
                }
            };
            xhr.send('product_id=' + productId);
        }
    </script>
</body>
</html>
