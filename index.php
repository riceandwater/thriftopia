<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thriftopia - Sustainable Fashion</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family:Neue Haas Grotesk Display Bold ;
        }
        body {
            background-color: #f7f7f7;
            color: #333;
        }
        header {
            background: #fff;
            padding: 2px 50px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative; 
            z-index: 10;
            border-bottom-left-radius: 10px; 
            border-bottom-right-radius: 10px; 
        }
        
        header h1 {
            color: #7a9c35;
            font-size: 2rem;
            font-weight: bold;
        }
        nav a {
            margin: 0 15px;
            text-decoration: none;
            color: #333;
            font-weight: 700;
            font-size:26px;
            
            
        }
        nav a:hover {
            color: #7a9c35;

        }
        
        .btn-signin {
         display: inline-block; /* Important */
         background-color: #7a9c35;
         color: #fff;
         padding: 8px 16px;
         border-radius: 5px;
         text-decoration: none;
         font-weight: 500;
}
         .btn-signin:hover {
         background-color: #6a8b2f; /* Darker on hover */
}

        
        .hero {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 90vh;
            background: #fff;
            position: relative;
            overflow: hidden;
        }
        .hero-text {
            font-size: 4rem;
            color: #7a9c35;
            margin-bottom: 20px;
        }
       
        .model {
            margin-left:-50px;
            opacity: 0;
            transform: translateY(50px);
            animation: moveUp 4s forwards;
            border-radius: 8px;
    
        }
       
        .model:nth-child(3) { animation-delay: 0.6s; }
       
        @keyframes moveUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .about, .categories, .how-it-works, footer {
            padding: 50px;
            background: #fff;
            margin: 20px auto;
            border-radius: 10px;
            max-width: 1200px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .about h2, .categories h2, .how-it-works h2 {
            color: #7a9c35;
            margin-bottom: 20px;
            font-weight:bold;
            font-size:2.5rem;
        }
        
        
        footer {
            text-align: center;
            font-size: 0.9rem;
            color: #777;
        }
        .image-grid {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
         gap: 20px;
         margin-top: 20px;
}

.image-grid img {
    width: 100%;
    height: 300px; /* Fixed height for all images */
    object-fit: cover; /* Crop images to fill the space */
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.image-grid img:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
}

       

    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="new logo.png" alt="Thriftopia Logo"style="height: 90px; width: auto;">
      </div
        <h1></h1>
        <nav>
            <a href='#home'>Home</a>
            <a href="aboutus.php">About-us</a>
            <a href="shop.php">Shop</a>
            <a href="registration.php" class="btn-signin">Sign-in</a>

           
        </nav>
    </header>

    <section class="hero">
      
        <div class="models">
           
            <img src="Thriftopia homepage.JPG" alt="Model 3" class="model" height="460">
           
        </div>
    </section>

  

    <section id="categories" class="categories">
        <h2>Shop</h2>
        <div class="image-grid">
            <img src="strip[1].jpg" alt="Product 1">
            <img src="two[1].png" alt="Product 2">
            <img src="Grey_Hoodie[1].jpg" alt="Product 3">
            <img src="color.jpg" alt="Product 4">
            <img src="leather[1].png" alt="Product 5">
            <img src="three[1].png" alt="Product 6">
            <img src="smith.jpeg.png" alt="product 7">
            <img src="grii[1].png" alt="product 8">
        </div>
    </section>
    
   

  
    <footer style="background-color: #2e2e2e; color: #fff; padding: 50px 20px;">
    <div style="max-width: 1200px; margin: auto; display: flex; flex-wrap: wrap; justify-content: space-between;">
      
        <div style="flex: 1 1 250px; margin-bottom: 20px;">
            <h3 style="color: #7a9c35; margin-bottom: 15px;">Thriftopia</h3>
            <p>Making sustainable shopping accessible and enjoyable for everyone. Join our community of conscious consumers.</p>
            <div style="margin-top: 15px;">
            
                <a href="#" style="color: #fff;">Instagram</a>
            </div>
        </div>

        
        <div style="flex: 1 1 150px; margin-bottom: 20px;">
            <h4 style="color: #7a9c35; margin-bottom: 10px;">Shop</h4>
            <ul style="list-style: none; padding: 0;">
                <li><a href="#" style="color: #ccc; text-decoration: none;">Womens clothes</a></li>
                <li><a href="#" style="color: #ccc; text-decoration: none;">Mens clothes</a></li>
        
             
            </ul>
        </div>

       
        <div style="flex: 1 1 150px; margin-bottom: 20px;">
            <h4 style="color: #7a9c35; margin-bottom: 10px;">Support</h4>
            <ul style="list-style: none; padding: 0;">
                <li><a href="#" style="color: #ccc; text-decoration: none;">Help Center</a></li>
                <li><a href="#" style="color: #ccc; text-decoration: none;">Shipping Info</a></li>
                <li><a href="#" style="color: #ccc; text-decoration: none;">Returns</a></li>
                <li><a href="#" style="color: #ccc; text-decoration: none;">Contact Us</a></li>
            </ul>
        </div>

      
        <div style="flex: 1 1 250px; margin-bottom: 20px;">
            <h4 style="color: #7a9c35; margin-bottom: 10px;">Stay Updated</h4>
            <p>Get the latest deals and sustainability tips delivered to your inbox.</p>
            <form>
                
            </form>
        </div>
    </div>

   
    <div style="text-align: center; padding-top: 20px; border-top: 1px solid #444; margin-top: 30px; color: #aaa;">
        &copy; 2025 Thriftopia. All rights reserved. | 
        <a href="#" style="color: #aaa; text-decoration: none;">Privacy</a> | 
        <a href="#" style="color: #aaa; text-decoration: none;">Terms</a> | 

    </div>
</footer>

</body>
</html>