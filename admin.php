<?php
// Database configuration
$server = "localhost";
$user = "root";
$pwd = "";
$databasename = "thriftopia";

// Create connection
$conn = new mysqli($server, $user, $pwd, $databasename);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Function to get all users
function getUsers($conn) {
    $sql = "SELECT u.*, 
                   COUNT(DISTINCT p.id) as products_listed,
                   COUNT(DISTINCT o.id) as products_sold,
                   COALESCE(SUM(CASE WHEN o.status = 'delivered' THEN o.total_price * 0.77 ELSE 0 END), 0) as total_earnings
            FROM users u 
            LEFT JOIN products p ON u.id = p.user_id 
            LEFT JOIN orders o ON u.id = o.seller_id 
            GROUP BY u.id, u.username, u.email, u.phone, u.address, u.role, u.gender, u.created_at, u.google_id, u.profile_photo
            ORDER BY u.created_at DESC";
    
    $result = $conn->query($sql);
    $users = array();
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    return $users;
}

// Function to get all sellers
function getSellers($conn) {
    $sql = "SELECT s.Seller_ID, s.user_id, s.seller_name, s.Email, s.Phone_number, s.Number_of_Listing, s.created_at, s.updated_at,
                   u.username, u.email as user_email 
            FROM seller s 
            LEFT JOIN users u ON s.user_id = u.id 
            ORDER BY s.created_at DESC";
    
    $result = $conn->query($sql);
    $sellers = array();
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $sellers[] = $row;
        }
    }
    return $sellers;
}

// Function to get all buyers
function getBuyers($conn) {
    $sql = "SELECT b.*, u.username, u.email 
            FROM buyers b 
            LEFT JOIN users u ON b.user_id = u.id 
            ORDER BY b.created_at DESC";
    
    $result = $conn->query($sql);
    $buyers = array();
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $buyers[] = $row;
        }
    }
    return $buyers;
}

// Function to get all products with seller information
function getProducts($conn) {
    $sql = "SELECT p.*, u.username as seller_name, u.email as seller_email,
                   ROUND(p.price * 0.23, 2) as commission
            FROM products p 
            LEFT JOIN users u ON p.user_id = u.id 
            ORDER BY p.created_at DESC";
    
    $result = $conn->query($sql);
    $products = array();
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    return $products;
}

// Function to get all orders/sales
function getOrders($conn) {
    $sql = "SELECT o.*, 
                   p.name as product_name, p.image as product_image,
                   u_seller.username as seller_name, u_seller.email as seller_email,
                   u_buyer.username as buyer_name,
                   ROUND(o.total_price * 0.23, 2) as commission,
                   ROUND(o.total_price * 0.77, 2) as seller_earnings
            FROM orders o
            LEFT JOIN products p ON o.product_id = p.id
            LEFT JOIN users u_seller ON o.seller_id = u_seller.id
            LEFT JOIN users u_buyer ON o.buyer_id = u_buyer.id
            ORDER BY o.order_date DESC";
    
    $result = $conn->query($sql);
    $orders = array();
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    return $orders;
}

// Function to get notifications
function getNotifications($conn) {
    $sql = "SELECT n.*, u.username as user_name 
            FROM notifications n 
            LEFT JOIN users u ON n.user_id = u.id
            ORDER BY n.created_at DESC 
            LIMIT 50";
    
    $result = $conn->query($sql);
    $notifications = array();
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }
    return $notifications;
}

// Function to get reviews
function getReviews($conn) {
    $sql = "SELECT r.*, 
                   p.name as product_name,
                   u.username as reviewer_name
            FROM reviews r
            LEFT JOIN products p ON r.product_id = p.id
            LEFT JOIN users u ON r.user_id = u.id
            ORDER BY r.created_at DESC";
    
    $result = $conn->query($sql);
    $reviews = array();
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
    }
    return $reviews;
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_GET['action']) {
            case 'get_users':
                echo json_encode(getUsers($conn));
                break;
            case 'get_sellers':
                echo json_encode(getSellers($conn));
                break;
            case 'get_buyers':
                echo json_encode(getBuyers($conn));
                break;
            case 'get_products':
                echo json_encode(getProducts($conn));
                break;
            case 'get_orders':
                echo json_encode(getOrders($conn));
                break;
            case 'get_notifications':
                echo json_encode(getNotifications($conn));
                break;
            case 'get_reviews':
                echo json_encode(getReviews($conn));
                break;
            case 'get_stats':
                $stats = array();
                
                // Get total users
                $user_result = $conn->query("SELECT COUNT(*) as count FROM users");
                $stats['total_users'] = $user_result ? $user_result->fetch_assoc()['count'] : 0;
                
                // Get total sellers
                $seller_result = $conn->query("SELECT COUNT(*) as count FROM seller");
                $stats['total_sellers'] = $seller_result ? $seller_result->fetch_assoc()['count'] : 0;
                
                // Get total buyers  
                $buyer_result = $conn->query("SELECT COUNT(*) as count FROM buyers");
                $stats['total_buyers'] = $buyer_result ? $buyer_result->fetch_assoc()['count'] : 0;
                
                // Get total products
                $product_result = $conn->query("SELECT COUNT(*) as count FROM products");
                $stats['total_products'] = $product_result ? $product_result->fetch_assoc()['count'] : 0;
                
                // Get total orders
                $order_result = $conn->query("SELECT COUNT(*) as count FROM orders");
                $stats['total_orders'] = $order_result ? $order_result->fetch_assoc()['count'] : 0;
                
                // Get total commission
                $commission_result = $conn->query("SELECT SUM(total_price * 0.23) as total FROM orders WHERE status = 'delivered'");
                $commission_row = $commission_result ? $commission_result->fetch_assoc() : null;
                $stats['total_commission'] = $commission_row ? ($commission_row['total'] ?? 0) : 0;
                
                echo json_encode($stats);
                break;
                
            // NEW ADMIN CONTROL FUNCTIONS
            case 'delete_user':
                $user_id = intval($_POST['user_id']);
                $conn->begin_transaction();
                
                try {
                    // Delete from related tables first
                    $conn->query("DELETE FROM notifications WHERE user_id = $user_id");
                    $conn->query("DELETE FROM reviews WHERE user_id = $user_id");
                    $conn->query("DELETE FROM orders WHERE buyer_id = $user_id OR seller_id = $user_id");
                    $conn->query("DELETE FROM products WHERE user_id = $user_id");
                    $conn->query("DELETE FROM buyers WHERE user_id = $user_id");
                    $conn->query("DELETE FROM seller WHERE user_id = $user_id");
                    $conn->query("DELETE FROM users WHERE id = $user_id");
                    
                    $conn->commit();
                    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
                } catch (Exception $e) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()]);
                }
                break;
                
            case 'delete_seller':
                $seller_id = intval($_POST['seller_id']);
                $result = $conn->query("DELETE FROM seller WHERE Seller_ID = $seller_id");
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Seller deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error deleting seller']);
                }
                break;
                
            case 'delete_buyer':
                $buyer_id = intval($_POST['buyer_id']);
                $result = $conn->query("DELETE FROM buyers WHERE id = $buyer_id");
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Buyer deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error deleting buyer']);
                }
                break;
                
            case 'delete_product':
                $product_id = intval($_POST['product_id']);
                $conn->begin_transaction();
                
                try {
                    // Delete related orders and reviews first
                    $conn->query("DELETE FROM orders WHERE product_id = $product_id");
                    $conn->query("DELETE FROM reviews WHERE product_id = $product_id");
                    $conn->query("DELETE FROM products WHERE id = $product_id");
                    
                    $conn->commit();
                    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
                } catch (Exception $e) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => 'Error deleting product: ' . $e->getMessage()]);
                }
                break;
                
            case 'update_order_status':
                $order_id = intval($_POST['order_id']);
                $status = $conn->real_escape_string($_POST['status']);
                $delivered_date = ($status === 'delivered') ? "NOW()" : "NULL";
                
                $sql = "UPDATE orders SET status = '$status', delivered_date = $delivered_date WHERE id = $order_id";
                $result = $conn->query($sql);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error updating order status']);
                }
                break;
                
            case 'delete_order':
                $order_id = intval($_POST['order_id']);
                $result = $conn->query("DELETE FROM orders WHERE id = $order_id");
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error deleting order']);
                }
                break;
                
            case 'ban_user':
                $user_id = intval($_POST['user_id']);
                $ban_status = $_POST['ban_status'] === 'true' ? 'banned' : 'active';
                
                $sql = "UPDATE users SET role = '$ban_status' WHERE id = $user_id";
                $result = $conn->query($sql);
                
                if ($result) {
                    $action = $ban_status === 'banned' ? 'banned' : 'unbanned';
                    echo json_encode(['success' => true, 'message' => "User $action successfully"]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error updating user status']);
                }
                break;
                
            case 'get_latest_notifications':
                $sql = "SELECT n.*, u.username as user_name 
                        FROM notifications n 
                        LEFT JOIN users u ON n.user_id = u.id
                        WHERE n.created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                        ORDER BY n.created_at DESC";
                $result = $conn->query($sql);
                $notifications = array();
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $notifications[] = $row;
                    }
                }
                echo json_encode($notifications);
                break;
                
            default:
                echo json_encode(array('error' => 'Invalid action'));
                break;
        }
    } catch (Exception $e) {
        echo json_encode(array('error' => 'Database error: ' . $e->getMessage()));
    }
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Neue Haas Grotesk Display Bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }

        .header h1 {
            color: #333;
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            font-size: 1.1em;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .stat-card .number {
            font-size: 2.2em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .tabs {
            display: flex;
            margin-bottom: 30px;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 5px;
            flex-wrap: wrap;
        }

        .tab {
            flex: 1;
            min-width: 100px;
            padding: 12px 8px;
            text-align: center;
            background: transparent;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            color: #666;
            transition: all 0.3s ease;
        }

        .tab.active {
            background: #667eea;
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            min-width: 800px;
        }

        th, td {
            padding: 10px 6px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }

        th {
            background: #667eea;
            color: white;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .status.active, .status.delivered {
            background: #d4edda;
            color: #155724;
        }

        .status.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status.confirmed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status.shipped {
            background: #e2e3e5;
            color: #383d41;
        }

        .status.banned {
            background: #f8d7da;
            color: #721c24;
        }

        /* ACTION BUTTONS STYLES */
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .btn-edit {
            background: #ffc107;
            color: #212529;
        }

        .btn-edit:hover {
            background: #e0a800;
            transform: translateY(-1px);
        }

        .btn-ban {
            background: #6c757d;
            color: white;
        }

        .btn-ban:hover {
            background: #545b62;
            transform: translateY(-1px);
        }

        .btn-unban {
            background: #28a745;
            color: white;
        }

        .btn-unban:hover {
            background: #218838;
            transform: translateY(-1px);
        }

        /* STATUS SELECT DROPDOWN */
        .status-select {
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 10px;
            background: white;
            cursor: pointer;
        }

        /* MODAL STYLES */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 30px;
            border-radius: 15px;
            width: 400px;
            max-width: 90%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .modal h3 {
            margin-bottom: 20px;
            color: #333;
        }

        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }

        .modal-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-btn-confirm {
            background: #dc3545;
            color: white;
        }

        .modal-btn-cancel {
            background: #6c757d;
            color: white;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: #28a745;
            color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            max-width: 300px;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.error {
            background: #dc3545;
        }

        .commission-highlight {
            background: #fff3cd;
            color: #856404;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 10px;
        }

        .search-box {
            width: 100%;
            padding: 15px;
            margin-bottom: 20px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .search-box:focus {
            outline: none;
            border-color: #667eea;
        }

        .product-image {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            object-fit: cover;
        }

        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 11px;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .error {
            text-align: center;
            padding: 20px;
            color: #dc3545;
            background: #f8d7da;
            border-radius: 5px;
            margin: 10px 0;
        }

        .rating-stars {
            color: #ffc107;
        }

        .text-truncate {
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .tab {
                min-width: auto;
                flex: none;
            }
            
            table {
                font-size: 11px;
            }
            
            th, td {
                padding: 6px 4px;
            }

            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                padding: 4px 6px;
                font-size: 9px;
            }

            .modal-content {
                width: 300px;
                margin: 30% auto;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> Thriftopia Admin Panel</h1>
            <p></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="number" id="totalUsers">0</div>
                <p>Registered</p>
            </div>
            <div class="stat-card">
                <h3>Total Sellers</h3>
                <div class="number" id="totalSellers">0</div>
                <p>Active Sellers</p>
            </div>
            <div class="stat-card">
                <h3>Total Buyers</h3>
                <div class="number" id="totalBuyers">0</div>
                <p>Active Buyers</p>
            </div>
            <div class="stat-card">
                <h3>Total Products</h3>
                <div class="number" id="totalProducts">0</div>
                <p>Listed Items</p>
            </div>
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="number" id="totalOrders">0</div>
                <p>Placed Orders</p>
            </div>
            <div class="stat-card">
                <h3>Commission</h3>
                <div class="number" id="totalCommission">$0</div>
                <p>23% Earned</p>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="switchTab('users')"> Users</button>
            <button class="tab" onclick="switchTab('sellers')"> Sellers</button>
            <button class="tab" onclick="switchTab('buyers')"> Buyers</button>
            <button class="tab" onclick="switchTab('products')"> Products</button>
            <button class="tab" onclick="switchTab('orders')"> Orders</button>
            <button class="tab" onclick="switchTab('reviews')"> Reviews</button>
            <button class="tab" onclick="switchTab('notifications')"> Notifications</button>
        </div>

        <div id="users-tab" class="tab-content active">
            <input type="text" class="search-box" placeholder="Search users..." onkeyup="searchTable('users-table', this.value)">
            <div class="table-container">
                <table id="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Join Date</th>
                            <th>Products Listed</th>
                            <th>Products Sold</th>
                            <th>Total Earnings</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="users-tbody">
                        <tr><td colspan="10" class="loading">Loading users...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="sellers-tab" class="tab-content">
            <input type="text" class="search-box" placeholder="Search sellers..." onkeyup="searchTable('sellers-table', this.value)">
            <div class="table-container">
                <table id="sellers-table">
                    <thead>
                        <tr>
                            <th>Seller ID</th>
                            <th>User ID</th>
                            <th>Seller Name</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                            <th>Number of Listings</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sellers-tbody">
                        <tr><td colspan="8" class="loading">Loading sellers...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="buyers-tab" class="tab-content">
            <input type="text" class="search-box" placeholder="Search buyers..." onkeyup="searchTable('buyers-table', this.value)">
            <div class="table-container">
                <table id="buyers-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Buyer Name</th>
                            <th>Buyer Phone</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="buyers-tbody">
                        <tr><td colspan="8" class="loading">Loading buyers...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="products-tab" class="tab-content">
            <input type="text" class="search-box" placeholder="Search products..." onkeyup="searchTable('products-table', this.value)">
            <div class="table-container">
                <table id="products-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Category ID</th>
                            <th>Seller</th>
                            <th>Listed Date</th>
                            <th>Commission</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="products-tbody">
                        <tr><td colspan="11" class="loading">Loading products...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="orders-tab" class="tab-content">
            <input type="text" class="search-box" placeholder="Search orders..." onkeyup="searchTable('orders-table', this.value)">
            <div class="table-container">
                <table id="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Buyer Name</th>
                            <th>Seller Name</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Total Price</th>
                            <th>Commission</th>
                            <th>Seller Earnings</th>
                            <th>Status</th>
                            <th>Order Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="orders-tbody">
                        <tr><td colspan="11" class="loading">Loading orders...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="reviews-tab" class="tab-content">
            <input type="text" class="search-box" placeholder="Search reviews..." onkeyup="searchTable('reviews-table', this.value)">
            <div class="table-container">
                <table id="reviews-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User ID</th>
                            <th>Reviewer</th>
                            <th>Product ID</th>
                            <th>Product</th>
                            <th>Rating</th>
                            <th>Review Text</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="reviews-tbody">
                        <tr><td colspan="8" class="loading">Loading reviews...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="notifications-tab" class="tab-content">
            <div class="table-container">
                <table id="notifications-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User ID</th>
                            <th>User Name</th>
                            <th>Title</th>
                            <th>Message</th>
                            <th>Product Name</th>
                            <th>Amount</th>
                            <th>Order ID</th>
                            <th>Buyer Name</th>
                            <th>Is Read</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody id="notifications-tbody">
                        <tr><td colspan="11" class="loading">Loading notifications...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- CONFIRMATION MODAL -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Confirm Action</h3>
            <p id="modalMessage">Are you sure you want to perform this action?</p>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-confirm" id="confirmBtn">Confirm</button>
                <button class="modal-btn modal-btn-cancel" onclick="closeModal()">Cancel</button>
            </div>
        </div>
    </div>

    <div id="notification-popup" class="notification">
        <span id="notification-text"></span>
    </div>

    <script>
        let currentTab = 'users';
        let currentAction = null;
        let currentItemId = null;

        function switchTab(tabName) {
            // Hide all tabs
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab');
            tabButtons.forEach(button => button.classList.remove('active'));
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked tab button
            event.target.classList.add('active');
            
            currentTab = tabName;
            loadTabData(tabName);
        }

        function searchTable(tableId, searchValue) {
            const table = document.getElementById(tableId);
            const tbody = table.querySelector('tbody');
            const rows = tbody.querySelectorAll('tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchValue.toLowerCase())) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function showNotification(message, isError = false) {
            const notification = document.getElementById('notification-popup');
            const notificationText = document.getElementById('notification-text');
            
            notificationText.textContent = message;
            notification.className = isError ? 'notification error show' : 'notification show';
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 5000);
        }

        function showConfirmModal(title, message, action, itemId, additionalData = null) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            document.getElementById('confirmModal').style.display = 'block';
            
            currentAction = action;
            currentItemId = itemId;
            
            document.getElementById('confirmBtn').onclick = () => {
                executeAction(action, itemId, additionalData);
                closeModal();
            };
        }

        function closeModal() {
            document.getElementById('confirmModal').style.display = 'none';
            currentAction = null;
            currentItemId = null;
        }

        function executeAction(action, itemId, additionalData) {
            const formData = new FormData();
            
            switch (action) {
                case 'delete_user':
                    formData.append('user_id', itemId);
                    break;
                case 'delete_seller':
                    formData.append('seller_id', itemId);
                    break;
                case 'delete_buyer':
                    formData.append('buyer_id', itemId);
                    break;
                case 'delete_product':
                    formData.append('product_id', itemId);
                    break;
                case 'delete_order':
                    formData.append('order_id', itemId);
                    break;
                case 'ban_user':
                    formData.append('user_id', itemId);
                    formData.append('ban_status', additionalData);
                    break;
                case 'update_order_status':
                    formData.append('order_id', itemId);
                    formData.append('status', additionalData);
                    break;
            }

            fetch(`?action=${action}`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message);
                    loadTabData(currentTab);
                    loadStats();
                } else {
                    showNotification(data.message, true);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', true);
            });
        }

        function deleteUser(userId) {
            showConfirmModal(
                'Delete User',
                'Are you sure you want to delete this user? This will also delete all their products, orders, and reviews. This action cannot be undone.',
                'delete_user',
                userId
            );
        }

        function deleteSeller(sellerId) {
            showConfirmModal(
                'Delete Seller',
                'Are you sure you want to delete this seller account? This action cannot be undone.',
                'delete_seller',
                sellerId
            );
        }

        function deleteBuyer(buyerId) {
            showConfirmModal(
                'Delete Buyer',
                'Are you sure you want to delete this buyer account? This action cannot be undone.',
                'delete_buyer',
                buyerId
            );
        }

        function deleteProduct(productId) {
            showConfirmModal(
                'Delete Product',
                'Are you sure you want to delete this product? This will also delete all related orders and reviews. This action cannot be undone.',
                'delete_product',
                productId
            );
        }

        function deleteOrder(orderId) {
            showConfirmModal(
                'Delete Order',
                'Are you sure you want to delete this order? This action cannot be undone.',
                'delete_order',
                orderId
            );
        }

        function banUser(userId, currentRole) {
            const isBanned = currentRole === 'banned';
            const action = isBanned ? 'unban' : 'ban';
            const banStatus = isBanned ? 'false' : 'true';
            
            showConfirmModal(
                `${action.charAt(0).toUpperCase() + action.slice(1)} User`,
                `Are you sure you want to ${action} this user?`,
                'ban_user',
                userId,
                banStatus
            );
        }

        function updateOrderStatus(orderId, currentStatus) {
            const statusOptions = ['pending', 'confirmed', 'shipped', 'delivered'];
            const currentIndex = statusOptions.indexOf(currentStatus);
            const nextStatus = statusOptions[(currentIndex + 1) % statusOptions.length];
            
            showConfirmModal(
                'Update Order Status',
                `Are you sure you want to change the order status from "${currentStatus}" to "${nextStatus}"?`,
                'update_order_status',
                orderId,
                nextStatus
            );
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }

        function formatCurrency(amount) {
            return 'Rs.' + parseFloat(amount || 0).toFixed(2);
        }

        function getInitials(name) {
            if (!name) return '?';
            return name.split(' ').map(word => word[0]).join('').toUpperCase().substring(0, 2);
        }

        function createStars(rating) {
            const stars = '★★★★★';
            const emptyStars = '☆☆☆☆☆';
            const fullStars = stars.substring(0, rating);
            const remainingStars = emptyStars.substring(rating);
            return `<span class="rating-stars">${fullStars}</span>${remainingStars}`;
        }

        function handleError(error, tabName) {
            console.error(`Error loading ${tabName}:`, error);
            const tbody = document.getElementById(`${tabName}-tbody`);
            if (tbody) {
                const colCount = tbody.closest('table').querySelectorAll('th').length;
                tbody.innerHTML = `<tr><td colspan="${colCount}" class="error">Error loading ${tabName}. Please check your database connection.</td></tr>`;
            }
        }

        function loadStats() {
            fetch('?action=get_stats')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    document.getElementById('totalUsers').textContent = data.total_users || 0;
                    document.getElementById('totalSellers').textContent = data.total_sellers || 0;
                    document.getElementById('totalBuyers').textContent = data.total_buyers || 0;
                    document.getElementById('totalProducts').textContent = data.total_products || 0;
                    document.getElementById('totalOrders').textContent = data.total_orders || 0;
                    document.getElementById('totalCommission').textContent = formatCurrency(data.total_commission);
                })
                .catch(error => {
                    console.error('Error loading stats:', error);
                    showNotification('Error loading statistics', true);
                });
        }

        function loadUsers() {
            fetch('?action=get_users')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    const tbody = document.getElementById('users-tbody');
                    if (!data || data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="10" class="loading">No users found</td></tr>';
                        return;
                    }
                    
                    tbody.innerHTML = data.map(user => `
                        <tr>
                            <td>#${user.id}</td>
                            <td>${user.username || 'N/A'}</td>
                            <td><div class="text-truncate">${user.email || 'N/A'}</div></td>
                            <td>${user.phone || 'N/A'}</td>
                            <td><span class="status ${user.role || 'active'}">${user.role || 'user'}</span></td>
                            <td>${formatDate(user.created_at)}</td>
                            <td>${user.products_listed || 0}</td>
                            <td>${user.products_sold || 0}</td>
                            <td>${formatCurrency(user.total_earnings)}</td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn ${(user.role === 'banned') ? 'btn-unban' : 'btn-ban'}" 
                                            onclick="banUser(${user.id}, '${user.role || 'active'}')">
                                        ${(user.role === 'banned') ? 'Unban' : 'Ban'}
                                    </button>
                                    <button class="btn btn-delete" onclick="deleteUser(${user.id})">Delete</button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                })
                .catch(error => handleError(error, 'users'));
        }

        function loadSellers() {
            fetch('?action=get_sellers')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    const tbody = document.getElementById('sellers-tbody');
                    if (!data || data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" class="loading">No sellers found</td></tr>';
                        return;
                    }
                    
                    tbody.innerHTML = data.map(seller => `
                        <tr>
                            <td>#${seller.Seller_ID}</td>
                            <td>#${seller.user_id || 'N/A'}</td>
                            <td>${seller.seller_name || 'N/A'}</td>
                            <td><div class="text-truncate">${seller.Email || 'N/A'}</div></td>
                            <td>${seller.Phone_number || 'N/A'}</td>
                            <td>${seller.Number_of_Listing || 0}</td>
                            <td>${formatDate(seller.created_at)}</td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-delete" onclick="deleteSeller(${seller.Seller_ID})">Delete</button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                })
                .catch(error => handleError(error, 'sellers'));
        }

        function loadBuyers() {
            fetch('?action=get_buyers')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    const tbody = document.getElementById('buyers-tbody');
                    if (!data || data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" class="loading">No buyers found</td></tr>';
                        return;
                    }
                    
                    tbody.innerHTML = data.map(buyer => `
                        <tr>
                            <td>#${buyer.id}</td>
                            <td>#${buyer.user_id || 'N/A'}</td>
                            <td>${buyer.username || 'N/A'}</td>
                            <td><div class="text-truncate">${buyer.email || 'N/A'}</div></td>
                            <td>${buyer.buyer_name || 'N/A'}</td>
                            <td>${buyer.buyer_phone || 'N/A'}</td>
                            <td>${formatDate(buyer.created_at)}</td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-delete" onclick="deleteBuyer(${buyer.id})">Delete</button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                })
                .catch(error => handleError(error, 'buyers'));
        }

        function loadProducts() {
            fetch('?action=get_products')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    const tbody = document.getElementById('products-tbody');
                    if (!data || data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="11" class="loading">No products found</td></tr>';
                        return;
                    }
                    
                    tbody.innerHTML = data.map(product => `
                        <tr>
                            <td>#${product.id}</td>
                            <td>
                                ${product.image ? 
                                    `<img src="${product.image}" alt="${product.name}" class="product-image" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"><div class="product-image" style="background:#f0f0f0;display:none;align-items:center;justify-content:center;">📦</div>` : 
                                    '<div class="product-image" style="background:#f0f0f0;display:flex;align-items:center;justify-content:center;">📦</div>'
                                }
                            </td>
                            <td><div class="text-truncate">${product.name || 'N/A'}</div></td>
                            <td><div class="text-truncate">${(product.description || 'N/A').substring(0, 50)}${product.description && product.description.length > 50 ? '...' : ''}</div></td>
                            <td>${formatCurrency(product.price)}</td>
                            <td>${product.quantity || 0}</td>
                            <td>${product.category_id || 'N/A'}</td>
                            <td>${product.seller_name || 'N/A'}</td>
                            <td>${formatDate(product.created_at)}</td>
                            <td><span class="commission-highlight">${formatCurrency(product.commission)}</span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-delete" onclick="deleteProduct(${product.id})">Delete</button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                })
                .catch(error => handleError(error, 'products'));
        }

        function loadOrders() {
            fetch('?action=get_orders')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    const tbody = document.getElementById('orders-tbody');
                    if (!data || data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="11" class="loading">No orders found</td></tr>';
                        return;
                    }
                    
                    tbody.innerHTML = data.map(order => `
                        <tr>
                            <td>#${order.id}</td>
                            <td>${order.buyer_name || 'N/A'}</td>
                            <td>${order.seller_name || 'N/A'}</td>
                            <td><div class="text-truncate">${order.product_name || 'N/A'}</div></td>
                            <td>${order.quantity || 0}</td>
                            <td>${formatCurrency(order.total_price)}</td>
                            <td><span class="commission-highlight">${formatCurrency(order.commission)}</span></td>
                            <td>${formatCurrency(order.seller_earnings)}</td>
                            <td><span class="status ${order.status}">${order.status || 'pending'}</span></td>
                            <td>${formatDate(order.order_date)}</td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-edit" onclick="updateOrderStatus(${order.id}, '${order.status || 'pending'}')">
                                        Update Status
                                    </button>
                                    <button class="btn btn-delete" onclick="deleteOrder(${order.id})">Delete</button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                })
                .catch(error => handleError(error, 'orders'));
        }

        function loadReviews() {
            fetch('?action=get_reviews')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    const tbody = document.getElementById('reviews-tbody');
                    if (!data || data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" class="loading">No reviews found</td></tr>';
                        return;
                    }
                    
                    tbody.innerHTML = data.map(review => `
                        <tr>
                            <td>#${review.id}</td>
                            <td>#${review.user_id}</td>
                            <td>${review.reviewer_name || 'Anonymous'}</td>
                            <td>#${review.product_id}</td>
                            <td><div class="text-truncate">${review.product_name || 'N/A'}</div></td>
                            <td>${createStars(review.rating || 0)}</td>
                            <td><div class="text-truncate">${(review.review_text || 'No comment').substring(0, 100)}${review.review_text && review.review_text.length > 100 ? '...' : ''}</div></td>
                            <td>${formatDate(review.created_at)}</td>
                        </tr>
                    `).join('');
                })
                .catch(error => handleError(error, 'reviews'));
        }

        function loadNotifications() {
            fetch('?action=get_notifications')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    const tbody = document.getElementById('notifications-tbody');
                    if (!data || data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="11" class="loading">No notifications found</td></tr>';
                        return;
                    }
                    
                    tbody.innerHTML = data.map(notification => `
                        <tr>
                            <td>#${notification.id}</td>
                            <td>#${notification.user_id}</td>
                            <td>${notification.user_name || 'N/A'}</td>
                            <td><div class="text-truncate">${notification.title || 'N/A'}</div></td>
                            <td><div class="text-truncate">${notification.message || 'N/A'}</div></td>
                            <td><div class="text-truncate">${notification.product_name || 'N/A'}</div></td>
                            <td>${formatCurrency(notification.amount)}</td>
                            <td>${notification.order_id ? '#' + notification.order_id : 'N/A'}</td>
                            <td>${notification.buyer_name || 'N/A'}</td>
                            <td><span class="status ${notification.is_read ? 'delivered' : 'pending'}">${notification.is_read ? 'read' : 'unread'}</span></td>
                            <td>${formatDate(notification.created_at)}</td>
                        </tr>
                    `).join('');
                })
                .catch(error => handleError(error, 'notifications'));
        }

        function loadTabData(tabName) {
            switch(tabName) {
                case 'users':
                    loadUsers();
                    break;
                case 'sellers':
                    loadSellers();
                    break;
                case 'buyers':
                    loadBuyers();
                    break;
                case 'products':
                    loadProducts();
                    break;
                case 'orders':
                    loadOrders();
                    break;
                case 'reviews':
                    loadReviews();
                    break;
                case 'notifications':
                    loadNotifications();
                    break;
            }
        }

        function checkForNewNotifications() {
            fetch('?action=get_latest_notifications')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data && !data.error && data.length > 0) {
                        data.forEach(notification => {
                            showNotification(`${notification.title}: ${notification.message}`);
                        });
                        // Reload notifications if that tab is active
                        if (currentTab === 'notifications') {
                            loadNotifications();
                        }
                        // Always reload stats for real-time updates
                        loadStats();
                    }
                })
                .catch(error => {
                    console.error('Error checking for new notifications:', error);
                });
        }

        // Initialize the admin panel
        function initializeAdminPanel() {
            loadStats();
            loadUsers(); // Load initial tab data
            
            // Show welcome notification
            setTimeout(() => {
                showNotification(' Welcome to Thriftopia Admin Panel!');
            }, 1000);
            
            // Check for new notifications every 30 seconds for real-time updates
            setInterval(checkForNewNotifications, 30000);
            
            // Refresh current tab data every 2 minutes
            setInterval(() => {
                loadTabData(currentTab);
                loadStats();
            }, 120000);
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('confirmModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Initialize when page loads
        window.addEventListener('load', initializeAdminPanel);
        
        // Add error handling for network issues
        window.addEventListener('online', () => {
            showNotification(' Connection restored');
            loadTabData(currentTab);
            loadStats();
        });
        
        window.addEventListener('offline', () => {
            showNotification('⚠️ Connection lost - some features may not work', true);
        });

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(event) {
            // ESC key closes modal
            if (event.key === 'Escape') {
                closeModal();
            }
            
            // Ctrl + R refreshes current tab data
            if (event.ctrlKey && event.key === 'r') {
                event.preventDefault();
                loadTabData(currentTab);
                loadStats();
                showNotification(' Data refreshed');
            }
        });
    </script>
</body>
</html>