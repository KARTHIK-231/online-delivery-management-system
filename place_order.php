<?php
// Include or require PHPMailer files
require 'C:\wamp64\www\9\PHPMailer\src\PHPMailer.php';
require 'C:\wamp64\www\9\PHPMailer\src\SMTP.php';
require 'C:\wamp64\www\9\PHPMailer\src\Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Start the session to access session variables
session_start();

// Check if the user is logged in
if(isset($_SESSION['buyer_id'])) {
    // The user is logged in, retrieve the user's ID
    $user_id = $_SESSION['buyer_id'];

    // Include your database connection file
    include('db_connection.php');

    // Check if the cart is set and not empty
    if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        // Get the buyer's email address and address from the database
        $buyer_query = "SELECT email, address FROM Buyers WHERE buyer_id = ?";
        $buyer_statement = $conn->prepare($buyer_query);
        $buyer_statement->bind_param("i", $user_id);
        $buyer_statement->execute();
        $buyer_result = $buyer_statement->get_result();

        if ($buyer_result->num_rows == 1) {
            $buyer_data = $buyer_result->fetch_assoc();
            $buyer_email = $buyer_data['email'];
            $buyer_address = $buyer_data['address'];

            // Validate shipping address and payment information
            $shipping_address = $_POST['shipping_address'] ?? ''; // Use the null coalescing operator to handle unset shipping_address
            $payment_info = $_POST['payment_info'] ?? ''; // Use the null coalescing operator to handle unset payment_info

            // Calculate the total price of the order and validate cart
            $total_price = 0;
            foreach($_SESSION['cart'] as $product_id => $quantity) {
                $product_query = "SELECT price, seller_id FROM Products WHERE product_id = ?";
                $product_statement = $conn->prepare($product_query);
                $product_statement->bind_param("i", $product_id);
                $product_statement->execute();
                $product_result = $product_statement->get_result();

                if ($product_result->num_rows == 1) {
                    $product_data = $product_result->fetch_assoc();
                    $total_price += $product_data['price'] * $quantity;
                    $seller_id = $product_data['seller_id'];

                    // Insert the order into the Orders table
                    $order_status = 'Pending'; // You can set the initial order status
                    $order_date = date('Y-m-d H:i:s'); // Get the current date and time
                    $insert_order_query = "INSERT INTO Orders (buyer_id, seller_id, total_price, order_status, order_date) VALUES (?, ?, ?, ?, ?)";
                    $insert_order_statement = $conn->prepare($insert_order_query);
                    $insert_order_statement->bind_param("iiiss", $user_id, $seller_id, $total_price, $order_status, $order_date);
                    $insert_order_statement->execute();

                    // Get the auto-generated order_id
                    $order_id = $insert_order_statement->insert_id;

                    // Insert order details into the Order Details table
                    $insert_order_details_query = "INSERT INTO OrderDetails (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)";
                    $insert_order_details_statement = $conn->prepare($insert_order_details_query);
                    $insert_order_details_statement->bind_param("iiid", $order_id, $product_id, $quantity, $product_data['price']);
                    $insert_order_details_statement->execute();

                    // Retrieve seller's email address and name
                    $seller_info_query = "SELECT email, name, address FROM Sellers WHERE seller_id = ?";
                    $seller_info_statement = $conn->prepare($seller_info_query);
                    $seller_info_statement->bind_param("i", $seller_id);
                    $seller_info_statement->execute();
                    $seller_info_result = $seller_info_statement->get_result();

                    if ($seller_info_result->num_rows == 1) {
                        $seller_info = $seller_info_result->fetch_assoc();
                        $seller_email = $seller_info['email'];
                        $seller_name = $seller_info['name'];
                        $seller_address = $seller_info['address'];

                        // Send email notification to seller
                        $mail_seller = new PHPMailer(true);
                        try {
                            //Server settings
                            $mail_seller->isSMTP();
                            $mail_seller->Host       = 'smtp.gmail.com'; // SMTP server address
                            $mail_seller->SMTPAuth   = true;
                            $mail_seller->Username   = '4thhokage009@gmail.com'; // SMTP username
                            $mail_seller->Password   = 'moka wqwm oide suxs'; // SMTP password
                            $mail_seller->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
                            $mail_seller->Port       = 587; // TCP port to connect to

                            //Recipients
                            $mail_seller->setFrom('4thhokage009@gmail.com', 'Run Baby Run');
                            $mail_seller->addAddress($seller_email, $seller_name);

                            // Content
                            $mail_seller->isHTML(true); // Set email format to HTML
                            $mail_seller->Subject = "You have new orders";
                            $mail_seller->Body    = "Dear $seller_name,<br><br>You have received a new order.<br><br>Order ID: $order_id<br>Total Price: $total_price<br><br>Thank you.";

                            $mail_seller->send();
                            echo 'Email sent to seller';
                        } catch (Exception $e) {
                            echo "Error sending email to seller: {$mail_seller->ErrorInfo}";
                        }
                    } else {
                        echo "Error: Seller details not found.";
                    }

                    // Retrieve delivery person's ID
                    $delivery_person_info_query = "SELECT delivery_person_id FROM DeliveryPersons WHERE address = ?";
                    $delivery_person_info_statement = $conn->prepare($delivery_person_info_query);
                    $delivery_person_info_statement->bind_param("s", $buyer_address);
                    $delivery_person_info_statement->execute();
                    $delivery_person_info_result = $delivery_person_info_statement->get_result();

                    if ($delivery_person_info_result->num_rows > 0) {
                        $delivery_person_info_row = $delivery_person_info_result->fetch_assoc();
                        $delivery_person_id = $delivery_person_info_row['delivery_person_id'];

                        // Update the order with delivery person's ID
                        $update_order_query = "UPDATE Orders SET delivery_person_id = ? WHERE order_id = ?";
                        $update_order_statement = $conn->prepare($update_order_query);
                        $update_order_statement->bind_param("ii", $delivery_person_id, $order_id);
                        $update_order_statement->execute();
                    } else {
                        echo "Error: Delivery person not found for the given address.";
                    }

                    // Send email notification to delivery person
                    $mail_delivery_person = new PHPMailer(true);
                    try {
                        //Server settings
                        $mail_delivery_person->isSMTP();
                        $mail_delivery_person->Host       = 'smtp.gmail.com'; // SMTP server address
                        $mail_delivery_person->SMTPAuth   = true;
                        $mail_delivery_person->Username   = '4thhokage009@gmail.com'; // SMTP username
                        $mail_delivery_person->Password   = 'moka wqwm oide suxs'; // SMTP password
                        $mail_delivery_person->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
                        $mail_delivery_person->Port       = 587; // TCP port to connect to

                        //Recipients
                        $mail_delivery_person->setFrom('4thhokage009@gmail.com', 'Run Baby Run');
                        $mail_delivery_person->addAddress($delivery_person_email, $delivery_person_name);

                        // Content
                        $mail_delivery_person->isHTML(true); // Set email format to HTML
                        $mail_delivery_person->Subject = "New order assignment";
                        $mail_delivery_person->Body    = "Dear $delivery_person_name,<br><br>You have been assigned a new order for delivery.<br><br>Order ID: $order_id<br>Buyer's Address: $shipping_address<br>Seller's Name: $seller_name<br>Seller's Address: $seller_address<br><br>Thank you.";

                        $mail_delivery_person->send();
                        echo 'Email sent to delivery person';
                    } catch (Exception $e) {
                        echo "Error sending email to delivery person: {$mail_delivery_person->ErrorInfo}";
                    }
                } else {
                    echo "Error: Product details not found.";
                }
            }

            // Clear the cart after placing the order
            unset($_SESSION['cart']);

            // Send email confirmation to buyer
            $mail_buyer = new PHPMailer(true);
            try {
                //Server settings
                $mail_buyer->isSMTP();
                $mail_buyer->Host       = 'smtp.gmail.com'; // SMTP server address
                $mail_buyer->SMTPAuth   = true;
                $mail_buyer->Username   = '4thhokage009@gmail.com'; // SMTP username
                $mail_buyer->Password   = 'moka wqwm oide suxs'; // SMTP password
                $mail_buyer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
                $mail_buyer->Port       = 587; // TCP port to connect to

                //Recipients
                $mail_buyer->setFrom('4thhokage009@gmail.com', 'Run Baby Run');
                $mail_buyer->addAddress($buyer_email, 'Buyer Name');

                // Content
                $mail_buyer->isHTML(true); // Set email format to HTML
                $mail_buyer->Subject = "Order Confirmation";
                $mail_buyer->Body    = "Dear Buyer,<br><br>Your order has been confirmed.<br><br>Order ID: $order_id<br>Total Price: $total_price<br>Seller's Name: $seller_name<br>Seller's Address: $seller_address<br>Delivery Person's Name: $delivery_person_name<br><br>Thank you.";

                $mail_buyer->send();
                echo 'Email sent to buyer';
            } catch (Exception $e) {
                echo "Error sending email to buyer: {$mail_buyer->ErrorInfo}";
            }

            // Close prepared statements
            $buyer_statement->close();
            $product_statement->close();
            $insert_order_statement->close();
            $insert_order_details_statement->close();
            $seller_info_statement->close();
            $delivery_person_info_statement->close();

            // Close the database connection
            $conn->close();

            // Redirect the user to the thank you page
            header("Location: thank_you.php");
            exit();
        } else {
            // If no buyer found with the given ID, redirect to login page
            header("Location: login.php");
            exit();
        }
    } else {
        // If the cart is not set or empty, display an error message
        echo "Error: Your shopping cart is empty.";
    }
} else {
    // If the user is not logged in, redirect them to the login page
    header("Location: login.php");
    exit();
}
?>
