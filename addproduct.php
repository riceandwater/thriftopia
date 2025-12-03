<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../thriftopia/login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "thriftopia");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to create seller record
function createSellerRecord($conn, $user_id) {
    // First, get user information from users table
    $getUserQuery = "SELECT username, email, phone FROM users WHERE id = ?";
    $getUserStmt = mysqli_prepare($conn, $getUserQuery);
    
    if ($getUserStmt) {
        mysqli_stmt_bind_param($getUserStmt, "i", $user_id);
        mysqli_stmt_execute($getUserStmt);
        $userResult = mysqli_stmt_get_result($getUserStmt);
        
        if ($userResult && mysqli_num_rows($userResult) > 0) {
            $userData = mysqli_fetch_assoc($userResult);
            
            // Insert into seller table - FIXED: Using exact column names from your database
            $insertSellerQuery = "INSERT INTO seller (seller_name, Email, Phone_number, Number_of_Listing, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())";
            $insertSellerStmt = mysqli_prepare($conn, $insertSellerQuery);
            
            if ($insertSellerStmt) {
                mysqli_stmt_bind_param($insertSellerStmt, "sss", 
                    $userData['username'], 
                    $userData['email'], 
                    $userData['phone']
                );
                
                if (mysqli_stmt_execute($insertSellerStmt)) {
                    // Also update the user's role to 'seller' in users table
                    $updateRoleQuery = "UPDATE users SET role = 'seller' WHERE id = ?";
                    $updateRoleStmt = mysqli_prepare($conn, $updateRoleQuery);
                    
                    if ($updateRoleStmt) {
                        mysqli_stmt_bind_param($updateRoleStmt, "i", $user_id);
                        mysqli_stmt_execute($updateRoleStmt);
                        mysqli_stmt_close($updateRoleStmt);
                    }
                    
                    mysqli_stmt_close($insertSellerStmt);
                    mysqli_stmt_close($getUserStmt);
                    return true;
                } else {
                    error_log("Error creating seller record: " . mysqli_stmt_error($insertSellerStmt));
                }
                mysqli_stmt_close($insertSellerStmt);
            }
        }
        mysqli_stmt_close($getUserStmt);
    }
    return false;
}

// Function to update seller listing count
function updateSellerListingCount($conn, $user_id) {
    // FIXED: Using exact column names from your database
    $updateQuery = "UPDATE seller SET Number_of_Listing = Number_of_Listing + 1, updated_at = NOW() WHERE Email = (SELECT email FROM users WHERE id = ?)";
    $updateStmt = mysqli_prepare($conn, $updateQuery);
    
    if ($updateStmt) {
        mysqli_stmt_bind_param($updateStmt, "i", $user_id);
        mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);
    }
}

// Function to check if user is already a seller
function isUserAlreadySeller($conn, $user_id) {
    // Check both the users table role and seller table existence
    $checkQuery = "SELECT role FROM users WHERE id = ?";
    $checkStmt = mysqli_prepare($conn, $checkQuery);
    
    if ($checkStmt) {
        mysqli_stmt_bind_param($checkStmt, "i", $user_id);
        mysqli_stmt_execute($checkStmt);
        $result = mysqli_stmt_get_result($checkStmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $userData = mysqli_fetch_assoc($result);
            mysqli_stmt_close($checkStmt);
            return $userData['role'] === 'seller';
        }
        mysqli_stmt_close($checkStmt);
    }
    return false;
}

// Enhanced error handling and validation
$error_message = '';
$success_message = '';

if (isset($_POST['Add_product'])) {
    // STRICT VALIDATION: Check if terms and conditions are accepted
    if (!isset($_POST['acceptTerms']) || $_POST['acceptTerms'] !== 'on') {
        $error_message = "You must accept the Terms & Conditions before selling any product.";
    } else {
        $productname = mysqli_real_escape_string($conn, trim($_POST['productName']));
        $productprice = mysqli_real_escape_string($conn, trim($_POST['productPrice'])); 
        $description = mysqli_real_escape_string($conn, trim($_POST['description']));
        
        // Additional validation (quantity removed)
        if (empty($productname) || empty($productprice) || empty($description)) {
            $error_message = "All fields are required.";
        } elseif ($productprice <= 0) {
            $error_message = "Price must be greater than 0.";
        } elseif (!isset($_FILES['productImage']) || $_FILES['productImage']['error'] !== UPLOAD_ERR_OK) {
            $error_message = "Please select a valid product image.";
        } else {
            $user_id = $_SESSION['user_id']; 
           
            $productimage = $_FILES['productImage']['name'];
            $tmp = explode(".", $productimage);
            $newfilename = round(microtime(true)) . '.' . end($tmp);
            
            // Validate file type
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_extension = strtolower(end($tmp));
            
            if (!in_array($file_extension, $allowed_types)) {
                $error_message = "Only JPG, JPEG, PNG, GIF, and WebP images are allowed.";
            } else {
                if (!file_exists("uploads")) {
                    mkdir("uploads", 0777, true);
                }
                
                $uploadpath = "uploads/" . $newfilename;
                
                if (move_uploaded_file($_FILES['productImage']['tmp_name'], $uploadpath)) {
                    // Start transaction
                    mysqli_begin_transaction($conn);
                    
                    try {
                        // Check if user is already a seller
                        $isAlreadySeller = isUserAlreadySeller($conn, $user_id);
                        
                        // Insert product (quantity set to 1 by default for thrift items)
                        $sql = "INSERT INTO products(name, description, image, price, quantity, user_id) VALUES (?, ?, ?, ?, 1, ?)";
                        $stmt = mysqli_prepare($conn, $sql);
                        
                        if ($stmt) {
                            mysqli_stmt_bind_param($stmt, "sssdi", $productname, $description, $newfilename, $productprice, $user_id);
                            
                            if (mysqli_stmt_execute($stmt)) {
                                if ($isAlreadySeller) {
                                    // User is already a seller, just update listing count
                                    updateSellerListingCount($conn, $user_id);
                                    $success_message = "Product added successfully!";
                                } else {
                                    // User is not a seller yet, create seller record
                                    if (createSellerRecord($conn, $user_id)) {
                                        $success_message = "Product added successfully! You are now a seller on our platform!";
                                    } else {
                                        throw new Exception("Failed to create seller record");
                                    }
                                }
                                
                                // Commit transaction
                                mysqli_commit($conn);
                                echo "<script>alert('$success_message'); window.location.href='shop.php';</script>";
                            } else {
                                throw new Exception("Error adding product: " . mysqli_stmt_error($stmt));
                            }
                            mysqli_stmt_close($stmt);
                        } else {
                            throw new Exception("Error preparing statement: " . mysqli_error($conn));
                        }
                    } catch (Exception $e) {
                        // Rollback transaction on error
                        mysqli_rollback($conn);
                        $error_message = $e->getMessage();
                        if (file_exists($uploadpath)) {
                            unlink($uploadpath);
                        }
                    }
                } else {
                    $error_message = "Error uploading file. Please try again.";
                }
            }
        }
    }
}

$sql = "SELECT name, price, image FROM products ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Add Product</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-...==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        * {
            margin: 0;
            padding: 2px;
            text-decoration: none;
            list-style: none;
            box-sizing: border-box;
        }

        body {
            font-family:Neue Haas Grotesk Display Bold;
            background-color: #f5f5f5;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        /* Top Navbar */
        .topnav {
            background-color: #fff;
            text-align: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .topnav h1 {
            font-size: 2.5rem;
            letter-spacing: 1px;
            font-weight: 600;
            color: #333;
        }

        /* Main Layout */
        .container {
            display: flex;
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            
        }

        /* Sidebar */
        .sidebar {
            background-color: #fff;
            padding: 16px 20px;
            width: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            border-right: 1px solid #e0e0e0;
            box-shadow: 2px 0 4px rgba(0,0,0,0.05);
            margin-left:-150px;
        }

        .sidebar button, .sidebar a {
            background: none;
            border: none;
            cursor: pointer;
            padding: 16px;
            color: #666;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .sidebar button:hover, .sidebar a:hover {
            background-color: #f0f0f0;
            color: #007bff;
        }

        .sidebar button i, .sidebar a i {
            font-size: 1.8rem;
        }

        /* Upload Section */
        .upload-section {
            flex: 1;
            padding: 2rem;
            background-color: #fff;
            margin: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow-y: auto;
        }

        .upload-section h2 {
            margin-bottom: 2rem;
            font-size: 2rem;
            font-weight: 600;
            color: #333;
        }

        /* Form Styling */
        .form-container {
            max-width: 600px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
            font-size: 1rem;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            font-family: inherit;
        }

        .form-input:focus {
            outline: none;
            border-color: #007bff;
        }

        .form-textarea {
            height: 80px;
            resize: vertical;
        }

        .file-input-wrapper {
            position: relative;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            background: #f9f9f9;
        }

        .file-input {
            width: 100%;
            border: none;
            background: none;
            padding: 0;
        }

        /* Terms Section - Enhanced */
        .terms-section {
            margin: 2rem 0;
            padding: 20px;
            background: #f8f9fa;
            border: 2px solid #007bff;
            border-radius: 8px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .terms-section.required {
            border-color: #dc3545;
            background: #fff5f5;
        }

        .terms-checkbox {
            margin-top: 2px;
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .terms-text {
            color: #333;
            font-size: 1rem;
            line-height: 1.5;
            text-align: left;
            font-weight: 500;
        }

        .terms-link {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }

        .terms-link:hover {
            text-decoration: underline;
        }

        .terms-warning {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 10px;
            display: none;
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            background-color: #4a4a4a;
            color: white;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .submit-btn:hover:not(:disabled) {
            background-color: #333;
        }

        .submit-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* Grid Section */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
            padding: 0 2rem;
        }

        .item {
            text-align: center;
            background-color: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 1rem;
            transition: transform 0.3s ease;
        }

        .item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }

        .item p {
            margin-top: 0.8rem;
            font-size: 1rem;
        }

        .item p:first-of-type {
            font-weight: 600;
            color: #333;
        }

        .item p:last-of-type {
            color: #007bff;
            font-weight: 600;
        }

        /* Error Message Styling */
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                flex-direction: row;
                padding: 1rem;
                margin-left: 0;
            }
            
            .upload-section {
                margin: 1rem;
            }
            
            .topnav h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

<header class="topnav">
    <h1>UPLOAD YOUR PRODUCT</h1>
</header>

<!-- Main Layout -->
<div class="container">
    <aside class="sidebar">
        <a href="index.php" title="Home">
            <i class="fa-solid fa-house"></i>
        </a>
        <a href="shop.php" title="Shop">
            <i class="fa-solid fa-square-plus"></i>
        </a>
    </aside>

    <section class="upload-section">
        <h2>Add New Product</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <strong>Success:</strong> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form id="productForm" action="addproduct.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="productName" class="form-label">Product Name:</label>
                    <input type="text" name="productName" id="productName" class="form-input" placeholder="Enter product name" required>
                </div>
                
                <div class="form-group">
                    <label for="productImage" class="form-label">Product Image:</label>
                    <div class="file-input-wrapper">
                        <input type="file" name="productImage" id="productImage" class="file-input" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Description:</label>
                    <textarea name="description" id="description" class="form-input form-textarea" placeholder="Enter description" required></textarea>
                </div>
                    
                <div class="form-group">
                    <label for="productPrice" class="form-label">Price:</label>
                    <input type="number" name="productPrice" id="productPrice" class="form-input" placeholder="Enter price in Rs." min="0.01" step="0.01" required>
                </div>

                <div class="terms-section" id="termsSection">
                    <input type="checkbox" id="termsCheckbox" name="acceptTerms" class="terms-checkbox" required>
                    <div>
                        <div class="terms-text">
                            <strong>IMPORTANT:</strong> I have read, understood, and agree to the <a href="termsncondition.html" target="_blank" class="terms-link">Terms & Conditions</a> before selling my product on this platform.
                        </div>
                        <div class="terms-warning" id="termsWarning">
                            ⚠️ You must accept the Terms & Conditions to proceed with selling your product.
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="Add_product" id="submitBtn" class="submit-btn" disabled>Add Product</button>
            </form>
        </div>
    </section>
</div>

<section class="grid">
    <?php
    // Display existing products
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '
            <div class="item">
                <img src="uploads/' . htmlspecialchars($row["image"]) . '" alt="' . htmlspecialchars($row["name"]) . '" onerror="this.src=\'placeholder.jpg\'">
                <p>' . htmlspecialchars($row["name"]) . '</p>
                <p>Rs. ' . htmlspecialchars($row["price"]) . '</p>
            </div>';
        }
    }
    ?>
</section>

<script>
    // Enhanced terms and conditions validation
    document.getElementById('termsCheckbox').addEventListener('change', function() {
        const submitBtn = document.getElementById('submitBtn');
        const termsSection = document.getElementById('termsSection');
        const termsWarning = document.getElementById('termsWarning');
        
        if (this.checked) {
            submitBtn.disabled = false;
            termsSection.classList.remove('required');
            termsWarning.style.display = 'none';
        } else {
            submitBtn.disabled = true;
            termsSection.classList.add('required');
            termsWarning.style.display = 'block';
        }
    });

    // Enhanced form submission validation (quantity validation removed)
    document.getElementById('productForm').addEventListener('submit', function(e) {
        const termsCheckbox = document.getElementById('termsCheckbox');
        const price = document.getElementById('productPrice').value;
        const productName = document.getElementById('productName').value.trim();
        const description = document.getElementById('description').value.trim();
        const productImage = document.getElementById('productImage').files[0];
        
        // Comprehensive validation (quantity removed)
        if (!termsCheckbox.checked) {
            e.preventDefault();
            alert('❌ REQUIRED: You must accept the Terms & Conditions before selling any product on our platform.');
            document.getElementById('termsSection').classList.add('required');
            document.getElementById('termsWarning').style.display = 'block';
            termsCheckbox.focus();
            return false;
        }
        
        if (!productName) {
            e.preventDefault();
            alert('Product name is required.');
            document.getElementById('productName').focus();
            return false;
        }
        
        if (!description) {
            e.preventDefault();
            alert('Product description is required.');
            document.getElementById('description').focus();
            return false;
        }
        
        if (!productImage) {
            e.preventDefault();
            alert('Product image is required.');
            document.getElementById('productImage').focus();
            return false;
        }
        
        if (price <= 0) {
            e.preventDefault();
            alert('Price must be greater than 0.');
            document.getElementById('productPrice').focus();
            return false;
        }
        
        // Final confirmation
        if (!confirm('Are you sure you want to add this product? By proceeding, you confirm that you have accepted our Terms & Conditions.')) {
            e.preventDefault();
            return false;
        }
        
        return true;
    });

    // Validate file type on selection
    document.getElementById('productImage').addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please select a valid image file (JPG, JPEG, PNG, GIF, or WebP).');
                this.value = '';
            }
        }
    });

    // Initialize form state
    document.addEventListener('DOMContentLoaded', function() {
        const submitBtn = document.getElementById('submitBtn');
        const termsCheckbox = document.getElementById('termsCheckbox');
        
        // Ensure button is disabled by default
        submitBtn.disabled = !termsCheckbox.checked;
    });
</script>

</body>
</html>

<?php
$conn->close();
?>