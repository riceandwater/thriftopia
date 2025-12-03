<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = mysqli_connect("localhost", "root", "", "thriftopia");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

// Verify the product belongs to the logged-in user
$sql = "SELECT * FROM products WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $product_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: profile.php");
    exit();
}

$product = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    
    $errors = array();
    
    // Validation
    if (empty($name)) {
        $errors[] = "Product name is required";
    }
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    if ($price <= 0) {
        $errors[] = "Price must be greater than 0";
    }
    if ($quantity < 0) {
        $errors[] = "Quantity cannot be negative";
    }
    
    // Handle image upload
    $image_name = $product['image']; // Keep existing image by default
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed";
        } else {
            $upload_dir = "uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Delete old image if exists and it's different from default
            if (!empty($product['image']) && file_exists($upload_dir . $product['image'])) {
                unlink($upload_dir . $product['image']);
            }
            
            $image_name = time() . '_' . $_FILES['image']['name'];
            $upload_path = $upload_dir . $image_name;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $errors[] = "Failed to upload image";
                $image_name = $product['image']; // Revert to original if upload fails
            }
        }
    }
    
    // If no errors, update the product
    if (empty($errors)) {
        $update_sql = "UPDATE products SET name = ?, description = ?, price = ?, quantity = ?, image = ? WHERE id = ? AND user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssdisii", $name, $description, $price, $quantity, $image_name, $product_id, $user_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Product updated successfully!";
            // Refresh product data
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
        } else {
            $errors[] = "Error updating product: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Thriftopia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
        }

        /* Header Styles - Matching profile.php */
        .topnav {
            background: linear-gradient(135deg,rgb(190, 190, 190) 0%,rgb(184, 184, 184) 100%);
            color: black;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo img {
            height: 50px;
            width: auto;
        }

        .header-content h1 {
            font-size: 1.8rem;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .profile a i {
            font-size: 2.5rem;
            color: black;
            transition: transform 0.3s ease;
        }

        .profile a i:hover {
            transform: scale(1.1);
        }

        /* Welcome Bar */
        .welcome-bar {
            background-color: none;
            backdrop-filter: blur(10px);
            padding: 0.75rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .welcome-bar span {
            font-weight: 500;
        }

        .welcome-bar a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .welcome-bar a:hover {
            background-color: rgba(255,255,255,0.2);
            transform: translateY(-1px);
        }

        .back-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #333;
            transition: transform 0.3s ease;
        }

        .back-btn:hover {
            transform: scale(1.1);
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Navigation */
        .navigation {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .nav-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background-color: white;
            color: #333;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            background-color: #667eea;
            color: white;
        }

        /* Form Content */
        .form-content {
            background-color: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-header h2 {
            font-size: 2.2rem;
            color: #333;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .form-header p {
            font-size: 1.1rem;
            color: #666;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 10px;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .alert ul {
            margin: 0;
            padding-left: 1rem;
        }

        .alert li {
            margin-bottom: 0.25rem;
        }

        /* Form Styles */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .edit-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
        }

        .form-group input,
        .form-group textarea {
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background-color: #fafbfc;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-group small {
            color: #666;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* Current Image Display */
        .current-image {
            margin-bottom: 1rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 10px;
            border: 2px dashed #dee2e6;
        }

        .current-image p {
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: #495057;
        }

        .current-image img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 2px solid #fff;
        }

        /* File Input Styling */
        input[type="file"] {
            padding: 1rem;
            border: 2px dashed #667eea;
            border-radius: 10px;
            background-color: #f8f9ff;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        input[type="file"]:hover {
            border-color: #5a67d8;
            background-color: #f0f0ff;
            transform: translateY(-1px);
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #f0f0f0;
        }

        .save-btn, .cancel-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            min-width: 150px;
            justify-content: center;
        }

        .save-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        .cancel-btn {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .cancel-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }

            .header-content h1 {
                font-size: 1.5rem;
            }

            .welcome-bar {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .container {
                padding: 1rem;
            }

            .navigation {
                flex-direction: column;
                align-items: center;
            }

            .form-content {
                padding: 1.5rem;
            }
            
            .form-header h2 {
                font-size: 1.8rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .save-btn,
            .cancel-btn {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .form-actions {
                gap: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <header class="topnav">
        <div class="header-content">
            <div class="logo">
                <img src="new logo.png" alt="Thriftopia Logo">
            </div>
            <h1>EDIT PRODUCT</h1>
            <div class="profile">
                <a href="profile.php"><i class="fa-regular fa-circle-user"></i></a>
            </div>
        </div>

        <div class="welcome-bar">
            <button class="back-btn" onclick="window.history.back()">
                <i class="fas fa-arrow-left"></i>
            </button>
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <a href="logout.php">
                <i class="fa-solid fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>

    <div class="container">
        <!-- Navigation -->
        <div class="navigation">
            <a href="index.php" class="nav-btn">
                <i class="fa-solid fa-house"></i>
                Home
            </a>
            <a href="shop.php" class="nav-btn">
                <i class="fa-solid fa-store"></i>
                Shop
            </a>
            <a href="profile.php" class="nav-btn">
                <i class="fa-regular fa-circle-user"></i>
                Profile
            </a>
            <a href="addproduct.php" class="nav-btn">
                <i class="fa-solid fa-square-plus"></i>
                Add Product
            </a>
        </div>

        <!-- Form Content -->
        <section class="form-content">
            <div class="form-header">
                <h2><i class="fa-solid fa-edit"></i> Edit Product</h2>
                <p>Update your product information</p>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Edit Form -->
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data" class="edit-form">
                    <div class="form-group">
                        <label for="name">
                            <i class="fa-solid fa-tag"></i> Product Name
                        </label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required placeholder="Enter product name">
                    </div>

                    <div class="form-group">
                        <label for="description">
                            <i class="fa-solid fa-align-left"></i> Description
                        </label>
                        <textarea id="description" name="description" required placeholder="Describe your product..."><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">
                                <i class="fa-solid fa-dollar-sign"></i> Price (Rs.)
                            </label>
                            <input type="number" id="price" name="price" step="0.01" min="0.01" value="<?php echo $product['price']; ?>" required placeholder="0.00">
                        </div>

                        <div class="form-group">
                            <label for="quantity">
                                <i class="fa-solid fa-boxes"></i> Quantity
                            </label>
                            <input type="number" id="quantity" name="quantity" min="0" value="<?php echo $product['quantity']; ?>" required placeholder="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="image">
                            <i class="fa-solid fa-image"></i> Product Image
                        </label>
                        <?php if (!empty($product['image'])): ?>
                            <div class="current-image">
                                <p><i class="fa-solid fa-image"></i> Current Image:</p>
                                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="Current product image">
                            </div>
                        <?php endif; ?>
                        <input type="file" id="image" name="image" accept="image/*">
                        <small><i class="fa-solid fa-info-circle"></i> Leave empty to keep current image. Accepted formats: JPG, JPEG, PNG, GIF</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="save-btn">
                            <i class="fa-solid fa-save"></i> Save Changes
                        </button>
                        <a href="profile.php" class="cancel-btn">
                            <i class="fa-solid fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.style.display = 'none', 300);
            });
        }, 5000);

        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Create or update image preview
                    let currentImageDiv = document.querySelector('.current-image');
                    if (!currentImageDiv) {
                        currentImageDiv = document.createElement('div');
                        currentImageDiv.className = 'current-image';
                        document.getElementById('image').parentNode.insertBefore(currentImageDiv, document.getElementById('image'));
                    }
                    
                    currentImageDiv.innerHTML = `
                        <p><i class="fa-solid fa-image"></i> New Image Preview:</p>
                        <img src="${e.target.result}" alt="New product image">
                    `;
                };
                reader.readAsDataURL(file);
            }
        });

        // Form validation
        document.querySelector('.edit-form').addEventListener('submit', function(e) {
            const price = parseFloat(document.getElementById('price').value);
            const quantity = parseInt(document.getElementById('quantity').value);
            
            if (price <= 0) {
                e.preventDefault();
                alert('Price must be greater than 0');
                return;
            }
            
            if (quantity < 0) {
                e.preventDefault();
                alert('Quantity cannot be negative');
                return;
            }
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>