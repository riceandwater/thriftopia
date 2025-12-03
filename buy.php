<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../thriftopia/login.php");
    exit();
}

// Database connection with error handling
$conn = new mysqli("localhost", "root", "", "thriftopia");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset for proper character handling
$conn->set_charset("utf8mb4");

// Get product ID from URL
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id <= 0) {
    header("Location: shop.php");
    exit();
}

// Get product details with seller information
$sql = "SELECT p.id, p.name, p.description, p.price, p.image, p.quantity, p.user_id, 
               u.username as seller_name, u.email as seller_email, u.phone as seller_phone, u.address as seller_address 
        FROM products p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = ?";
        
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: shop.php");
    exit();
}

$product = $result->fetch_assoc();

// Check if user is trying to buy their own product
$current_user_id = $_SESSION['user_id'];
if ($product['user_id'] == $current_user_id) {
    $error_message = "Sorry - You cannot buy your own product!";
}

// Get product reviews with buyer information
$reviews_sql = "SELECT r.*, u.username as reviewer_name, u.profile_photo 
                FROM reviews r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.product_id = ? 
                ORDER BY r.created_at DESC";
$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param("i", $product_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();

// Calculate average rating and total reviews
$rating_stats_sql = "SELECT 
                        COUNT(*) as total_reviews,
                        AVG(rating) as average_rating,
                        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_stars,
                        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_stars,
                        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_stars,
                        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_stars,
                        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                     FROM reviews 
                     WHERE product_id = ?";
$rating_stats_stmt = $conn->prepare($rating_stats_sql);
$rating_stats_stmt->bind_param("i", $product_id);
$rating_stats_stmt->execute();
$rating_stats = $rating_stats_stmt->get_result()->fetch_assoc();

// Function to create notification for seller
function createSellerNotification($conn, $seller_id, $buyer_id, $product_id, $order_id, $product_name, $buyer_name, $total_price) {
    $title = "New Order Received!";
    $message = "You have received a new order for '{$product_name}' from {$buyer_name}. Order amount: Rs. " . number_format($total_price, 2);
    
    $notification_sql = "INSERT INTO notifications (user_id, title, message, product_name, amount, order_id, buyer_name, is_read, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())";
    $notification_stmt = $conn->prepare($notification_sql);
    if ($notification_stmt) {
        $notification_stmt->bind_param("isssdis", $seller_id, $title, $message, $product_name, $total_price, $order_id, $buyer_name);
        $notification_stmt->execute();
        $notification_stmt->close();
    }
}

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($error_message)) {
    $buyer_id = $current_user_id;
    $quantity_requested = 1; // Fixed quantity to 1
    $total_price = $product['price']; // Single item price
    $delivery_address = trim($_POST['delivery_address']);
    $phone_number = trim($_POST['phone_number']);
    $buyer_name = trim($_POST['buyer_name']);
    
    // Validate input
    if (empty($buyer_name) || empty($phone_number) || empty($delivery_address)) {
        $error_message = "Please fill in all required fields.";
    } elseif ($product['quantity'] <= 0) {
        $error_message = "Product is out of stock!";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert or update buyer information in buyers table
            $buyer_check_sql = "SELECT id FROM buyers WHERE user_id = ?";
            $buyer_check_stmt = $conn->prepare($buyer_check_sql);
            
            if (!$buyer_check_stmt) {
                throw new Exception("Buyer check prepare failed: " . $conn->error);
            }
            
            $buyer_check_stmt->bind_param("i", $buyer_id);
            $buyer_check_stmt->execute();
            $buyer_check_result = $buyer_check_stmt->get_result();
            
            if ($buyer_check_result->num_rows > 0) {
                // Update existing buyer record
                $update_buyer_sql = "UPDATE buyers SET buyer_name = ?, buyer_phone = ?, buyer_address = ?, updated_at = NOW() WHERE user_id = ?";
                $update_buyer_stmt = $conn->prepare($update_buyer_sql);
                if (!$update_buyer_stmt) {
                    throw new Exception("Buyer update prepare failed: " . $conn->error);
                }
                $update_buyer_stmt->bind_param("sssi", $buyer_name, $phone_number, $delivery_address, $buyer_id);
                if (!$update_buyer_stmt->execute()) {
                    throw new Exception("Buyer update failed: " . $update_buyer_stmt->error);
                }
                $update_buyer_stmt->close();
            } else {
                // Insert new buyer record
                $insert_buyer_sql = "INSERT INTO buyers (user_id, buyer_name, buyer_phone, buyer_address, created_at) VALUES (?, ?, ?, ?, NOW())";
                $insert_buyer_stmt = $conn->prepare($insert_buyer_sql);
                if (!$insert_buyer_stmt) {
                    throw new Exception("Buyer insert prepare failed: " . $conn->error);
                }
                $insert_buyer_stmt->bind_param("isss", $buyer_id, $buyer_name, $phone_number, $delivery_address);
                if (!$insert_buyer_stmt->execute()) {
                    throw new Exception("Buyer insertion failed: " . $insert_buyer_stmt->error);
                }
                $insert_buyer_stmt->close();
            }
            $buyer_check_stmt->close();
            
            // Insert order into database
            $order_sql = "INSERT INTO orders (buyer_id, seller_id, product_id, quantity, total_price, delivery_address, phone_number, buyer_name, seller_name, status, order_date) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
            $order_stmt = $conn->prepare($order_sql);
            if (!$order_stmt) {
                throw new Exception("Order prepare failed: " . $conn->error);
            }
            
            $order_stmt->bind_param("iiidsssss", 
                $buyer_id,                  // 1. buyer_id (int)
                $product['user_id'],        // 2. seller_id (int)  
                $product_id,                // 3. product_id (int)
                $quantity_requested,        // 4. quantity (int)
                $total_price,               // 5. total_price (double)
                $delivery_address,          // 6. delivery_address (string)
                $phone_number,              // 7. phone_number (string)
                $buyer_name,                // 8. buyer_name (string)
                $product['seller_name']     // 9. seller_name (string)
            );
            
            if (!$order_stmt->execute()) {
                throw new Exception("Order insertion failed: " . $order_stmt->error);
            }
            
            // Get the inserted order ID
            $order_id = $conn->insert_id;
            $order_stmt->close();
            
            // Update product quantity
            $new_quantity = $product['quantity'] - 1;
            $update_sql = "UPDATE products SET quantity = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            if (!$update_stmt) {
                throw new Exception("Product update prepare failed: " . $conn->error);
            }
            $update_stmt->bind_param("ii", $new_quantity, $product_id);
            if (!$update_stmt->execute()) {
                throw new Exception("Product update failed: " . $update_stmt->error);
            }
            $update_stmt->close();
            
            // Create notification for seller
            createSellerNotification($conn, $product['user_id'], $buyer_id, $product_id, $order_id, $product['name'], $buyer_name, $total_price);
            
            // Commit transaction
            $conn->commit();
            
            $success_message = "Your order has been placed successfully! Thank you for shopping with us. The seller will contact you soon.";
            $redirect_to_shop = true;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = "Failed to place order. Error: " . $e->getMessage();
        }
    }
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Now - <?php echo htmlspecialchars($product['name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Neue Haas Grotesk Display Bold;
            background-color: #eeebeb;
            min-height: 100vh;
        }

        .header {
            background-color: #fff;
            padding: 1rem;
            border-bottom: 1px solid #ddd;
            text-align: center;
            position: relative;
        }

        .header h1 {
            font-size: 2rem;
            color: #333;
        }

        .back-btn {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #333;
            padding: 0.5rem;
        }

        .back-btn:hover {
            color: #007bff;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .product-details {
            background-color: #fff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .product-image {
            width: 100%;
            height: 300px;
            overflow: hidden;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .product-info p {
            margin-bottom: 0.5rem;
            line-height: 1.5;
            color: #555;
        }

        .price {
            font-size: 1.3rem;
            font-weight: bold;
            color: #e74c3c;
            margin: 1rem 0;
        }

        .seller-info {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .seller-info h3 {
            margin-bottom: 0.5rem;
            color: #333;
        }

        .seller-info p {
            margin-bottom: 0.3rem;
            color: #555;
        }

        /* Reviews Section Styles */
        .reviews-section {
            background-color: #fff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }

        .reviews-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .reviews-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .reviews-title i {
            font-size: 1.5rem;
            color: #ffd700;
        }

        .reviews-title h3 {
            color: #333;
            font-size: 1.3rem;
        }

        .rating-summary {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .average-rating {
            text-align: center;
            min-width: 120px;
        }

        .average-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .average-stars {
            display: flex;
            justify-content: center;
            gap: 0.25rem;
            margin-bottom: 0.25rem;
        }

        .star {
            color: #ffd700;
            font-size: 1.2rem;
        }

        .star.empty {
            color: #ddd;
        }

        .total-reviews {
            color: #666;
            font-size: 0.9rem;
        }

        .rating-breakdown {
            flex: 1;
        }

        .rating-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .rating-label {
            min-width: 60px;
            font-size: 0.9rem;
            color: #666;
        }

        .rating-bar {
            flex: 1;
            height: 6px;
            background-color: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
        }

        .rating-fill {
            height: 100%;
            background-color: #ffd700;
            transition: width 0.3s ease;
        }

        .rating-count {
            min-width: 30px;
            font-size: 0.9rem;
            color: #666;
        }

        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .review-item {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            border-left: 4px solid #10b981;
            transition: all 0.3s ease;
        }

        .review-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .review-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .reviewer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .reviewer-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .review-meta {
            flex: 1;
        }

        .reviewer-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .review-rating {
            display: flex;
            gap: 0.25rem;
            margin-bottom: 0.25rem;
        }

        .review-date {
            color: #666;
            font-size: 0.9rem;
        }

        .review-text {
            color: #555;
            line-height: 1.6;
            margin-bottom: 0.5rem;
        }

        .no-reviews {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-reviews i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .order-form {
            background-color: #fff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            font-family: inherit;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }

        .total-price {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 1rem;
        }

        .order-btn {
            width: 100%;
            background-color: rgb(234, 234, 234);
            color: black;
            border: none;
            padding: 1rem;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .order-btn:hover:not(:disabled) {
            background-color: rgb(197, 205, 199);
        }

        .order-btn:active:not(:disabled) {
            background-color: rgb(94, 196, 119);
        }

        .order-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            color: #666;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .sold-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .modal-content i {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
        }

        .modal-content h2 {
            color: #333;
            margin-bottom: 1rem;
        }

        .modal-content p {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.5;
        }

        .modal-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .modal-btn:hover {
            background-color: #0056b3;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .modal-content {
                margin: 25% auto;
                width: 95%;
                padding: 1.5rem;
            }

            .rating-summary {
                flex-direction: column;
                text-align: center;
            }

            .reviews-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <button class="back-btn" onclick="window.location.href='shop.php'" title="Back to Shop">
            <i class="fas fa-arrow-left"></i>
        </button>
        <h1>Buy Now</h1>
    </div>

    <div class="container">
        <div class="product-details">
            <div class="product-image">
                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     onerror="this.src='placeholder.jpg'">
            </div>
            
            <div class="product-info">
                <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                
                <?php if ($rating_stats['total_reviews'] > 0): ?>
                    <div class="sold-indicator">
                        <i class="fas fa-check-circle"></i>
                        This product has been sold and reviewed!
                    </div>
                <?php endif; ?>
                
                <p><?php echo htmlspecialchars($product['description']); ?></p>
                <div class="price">Rs. <?php echo number_format($product['price'], 2); ?></div>
                <p><strong>Available Quantity:</strong> <?php echo $product['quantity']; ?></p>
                
                <div class="seller-info">
                    <h3>Seller Information</h3>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($product['seller_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($product['seller_email']); ?></p>
                    <?php if (!empty($product['seller_phone'])): ?>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($product['seller_phone']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="order-form">
            <h2>Place Your Order</h2>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="orderForm" <?php echo (isset($error_message) && strpos($error_message, 'own product') !== false) ? 'style="display:none;"' : ''; ?>>
                <div class="form-group">
                    <label for="buyer_name">Your Name: <span style="color: red;">*</span></label>
                    <input type="text" id="buyer_name" name="buyer_name" 
                           placeholder="Enter your full name" required maxlength="255">
                </div>

                <div class="form-group">
                    <label for="phone_number">Your Phone Number: <span style="color: red;">*</span></label>
                    <input type="tel" id="phone_number" name="phone_number" 
                           placeholder="Enter your valid phone number" required maxlength="20">
                </div>

                <div class="form-group">
                    <label for="delivery_address">Delivery Address: <span style="color: red;">*</span></label>
                    <textarea id="delivery_address" name="delivery_address" 
                              placeholder="Enter your complete delivery address..." required></textarea>
                </div>

                <div class="total-price">
                    Total: Rs. <?php echo number_format($product['price'], 2); ?>
                </div>

                <button type="submit" class="order-btn" 
                        <?php echo ($product['quantity'] <= 0) ? 'disabled' : ''; ?>>
                    <?php if ($product['quantity'] <= 0): ?>
                        <i class="fas fa-times-circle"></i> Out of Stock
                    <?php else: ?>
                        <i class="fas fa-shopping-cart"></i> Place Order
                    <?php endif; ?>
                </button>
            </form>

            <?php if (isset($error_message) && strpos($error_message, 'own product') !== false): ?>
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="shop.php" style="color: #007bff; text-decoration: none; font-weight: bold;">
                        <i class="fas fa-arrow-left"></i> Back to Shop
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Product Reviews Section -->
    <?php if ($rating_stats['total_reviews'] > 0): ?>
    <div class="container" style="grid-template-columns: 1fr; margin-top: 0;">
        <div class="reviews-section">
            <div class="reviews-header">
                <div class="reviews-title">
                    <i class="fas fa-star"></i>
                    <h3>Customer Reviews (<?php echo $rating_stats['total_reviews']; ?>)</h3>
                </div>
            </div>

            <!-- Rating Summary -->
            <div class="rating-summary">
                <div class="average-rating">
                    <div class="average-number"><?php echo number_format($rating_stats['average_rating'], 1); ?></div>
                    <div class="average-stars">
                        <?php
                        $avg_rating = round($rating_stats['average_rating']);
                        for ($i = 1; $i <= 5; $i++):
                        ?>
                            <span class="star <?php echo ($i <= $avg_rating) ? '' : 'empty'; ?>">
                                <i class="fas fa-star"></i>
                            </span>
                        <?php endfor; ?>
                    </div>
                    <div class="total-reviews">Based on <?php echo $rating_stats['total_reviews']; ?> reviews</div>
                </div>

                <div class="rating-breakdown">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <?php 
                        $count_field = ($i === 5) ? 'five_stars' : 
                                      (($i === 4) ? 'four_stars' : 
                                      (($i === 3) ? 'three_stars' : 
                                      (($i === 2) ? 'two_stars' : 'one_star')));
                        $count = $rating_stats[$count_field];
                        $percentage = $rating_stats['total_reviews'] > 0 ? ($count / $rating_stats['total_reviews']) * 100 : 0;
                        ?>
                        <div class="rating-row">
                            <div class="rating-label"><?php echo $i; ?> star</div>
                            <div class="rating-bar">
                                <div class="rating-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <div class="rating-count"><?php echo $count; ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Individual Reviews -->
            <div class="reviews-list">
                <?php while ($review = $reviews_result->fetch_assoc()): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="reviewer-avatar">
                                <?php if (!empty($review['profile_photo']) && file_exists($review['profile_photo'])): ?>
                                    <img src="<?php echo htmlspecialchars($review['profile_photo']); ?>" alt="Reviewer Photo">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div class="review-meta">
                                <div class="reviewer-name"><?php echo htmlspecialchars($review['reviewer_name']); ?></div>
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo ($i <= $review['rating']) ? '' : 'empty'; ?>">
                                            <i class="fas fa-star"></i>
                                        </span>
                                    <?php endfor; ?>
                                </div>
                                <div class="review-date"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></div>
                            </div>
                        </div>
                        
                        <div class="review-text">
                            <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Success Modal -->
    <?php if (isset($success_message)): ?>
    <div id="successModal" class="modal" style="display: block;">
        <div class="modal-content">
            <i class="fas fa-check-circle"></i>
            <h2>Order Placed Successfully!</h2>
            <p><?php echo $success_message; ?></p>
            <button class="modal-btn" onclick="redirectToShop()">Continue Shopping</button>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const orderForm = document.getElementById('orderForm');
            
            if (orderForm) {
                orderForm.addEventListener('submit', function(e) {
                    const buyerName = document.getElementById('buyer_name');
                    const phoneNumber = document.getElementById('phone_number');
                    const deliveryAddress = document.getElementById('delivery_address');
                    
                    if (!buyerName.value.trim()) {
                        e.preventDefault();
                        alert('Please enter your name.');
                        buyerName.focus();
                        return;
                    }
                    
                    if (!phoneNumber.value.trim()) {
                        e.preventDefault();
                        alert('Please enter your phone number.');
                        phoneNumber.focus();
                        return;
                    }
                    
                    if (!deliveryAddress.value.trim()) {
                        e.preventDefault();
                        alert('Please enter your delivery address.');
                        deliveryAddress.focus();
                        return;
                    }
                });
            }
        });

        function redirectToShop() {
            window.location.href = 'shop.php';
        }

        // Auto redirect after 5 seconds if success modal is shown
        <?php if (isset($success_message)): ?>
        setTimeout(function() {
            redirectToShop();
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>

<?php
$conn->close();
?>