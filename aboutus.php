<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>About Us - Thriftopia</title>
  
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: Neue Haas Grotesk Display Bold;
    }

    body, html {
      height: 100%;
      overflow-x: hidden;
      overflow-y: auto;
    }

    .background-grid {
      position: fixed;
      top: 0;
      left: 0;
      height: 100%;
      width: 100%;
      z-index: -1;
      column-count: 4;
      column-gap: 10px;
      padding: 10px;
    }

    .background-grid img {
      width: 100%;
      margin-bottom: 10px;
      border-radius: 10px;
      break-inside: avoid;
    }

    .overlay {
      position: relative;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      background: rgba(0, 0, 0, 0.3);
      padding: 20px;
    }

    .overlay-content {
      background: rgba(255, 255, 255, 0.65);
      padding: 50px;
      border-radius: 20px;
      max-width: 900px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .overlay-content h1 {
      font-size: 48px;
      margin-bottom: 30px;
      color: #2c3e50;
      background: linear-gradient(135deg,  #7a9c35 0%,  #7a9c35 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-weight: bold;
    }

    .intro-section {
      margin-bottom: 40px;
      padding: 30px;
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      border-radius: 15px;
      border-left: 5px solid #7a9c35;
    }

    .intro-section h2 {
      font-size: 24px;
      color: #495057;
      margin-bottom: 20px;
      font-weight: 600;
    }

    .intro-section p {
      font-size: 18px;
      line-height: 1.8;
      color: #6c757d;
      margin-bottom: 15px;
    }

    .how-it-works-section {
      margin-bottom: 40px;
      text-align: left;
    }

    .how-it-works-section h2 {
      font-size: 28px;
      color: #2c3e50;
      margin-bottom: 25px;
      text-align: center;
      background: linear-gradient(135deg,  #7a9c35 0%, #7a9c35 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 25px;
      margin-bottom: 30px;
    }

    .feature-card {
      background: white;
      padding: 25px;
      border-radius: 15px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border-top: 4px solid  #7a9c35;
    }

    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    }

    .feature-card h3 {
      font-size: 20px;
      color: #495057;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .feature-card .icon {
      font-size: 24px;
      background: linear-gradient(135deg, #7a9c35 0%,  #7a9c35 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .feature-card p {
      font-size: 16px;
      line-height: 1.6;
      color: #6c757d;
    }

    .highlight {
      background: linear-gradient(135deg, 0%,100%);
      color: #2d3436;
      padding: 3px 8px;
      border-radius: 5px;
      font-weight: 600;
    }

    .steps-section {
      background: linear-gradient(135deg, #ffffffb1 100%);
      padding: 30px;
      border-radius: 15px;
      margin-bottom: 30px;
    }

    .steps-section h3 {
      font-size: 22px;
      color: #495057;
      margin-bottom: 20px;
      text-align: center;
    }

    .steps-list {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
    }

    .step-item {
      background: white;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .step-number {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, #7a9c35 0%,  #7a9c35 100%);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 10px;
      font-weight: bold;
      font-size: 18px;
    }

    .step-item h4 {
      font-size: 16px;
      color: #495057;
      margin-bottom: 10px;
      font-weight: 600;
    }

    .step-item p {
      font-size: 14px;
      color: #6c757d;
      line-height: 1.4;
    }

    .cta-section {
      background: linear-gradient(135deg, #7a9c35 0%, #7a9c35 100%);
      color: white;
      padding: 40px;
      border-radius: 20px;
      margin-top: 40px;
    }

    .cta-section h2 {
      font-size: 28px;
      margin-bottom: 20px;
      font-weight: 600;
    }

    .cta-section p {
      font-size: 18px;
      margin-bottom: 30px;
      line-height: 1.6;
      opacity: 0.9;
    }

    .cta-buttons {
      display: flex;
      gap: 20px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .btn {
      padding: 15px 30px;
      border: none;
      border-radius: 50px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      min-width: 180px;
      justify-content: center;
    }

    .btn-primary {
      background: transparent;
      color: white;
       border: 2px solid white;
      
    }

    .btn-primary:hover {
       background: white;
      color:  #7a9c35;
      transform: translateY(-3px);
    }

    .btn-secondary {
      background: transparent;
      color: white;
      border: 2px solid white;
    }

    .btn-secondary:hover {
      background: white;
      color:  #7a9c35;
      transform: translateY(-3px);
    }

    .back-to-home {
      position: absolute;
      top: 20px;
      left: 20px;
      background: rgba(255, 255, 255, 0.9);
      color:  #7a9c35 ;
      padding: 12px 20px;
      border-radius: 50px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
      backdrop-filter: blur(10px);
    }

    .back-to-home:hover {
      background: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    @media (max-width: 768px) {
      .background-grid {
        column-count: 2;
      }

      .overlay-content {
        padding: 30px 20px;
        margin: 10px;
      }

      .overlay-content h1 {
        font-size: 36px;
      }

      .intro-section {
        padding: 20px;
      }

      .how-it-works-section h2 {
        font-size: 24px;
      }

      .features-grid {
        grid-template-columns: 1fr;
      }

      .steps-list {
        grid-template-columns: 1fr;
      }

      .cta-buttons {
        flex-direction: column;
        align-items: center;
      }

      .btn {
        width: 100%;
        max-width: 300px;
      }

      .back-to-home {
        position: relative;
        top: 0;
        left: 0;
        margin: 0 auto 20px;
        align-self: center;
      }
    }

    @media (max-width: 480px) {
      .overlay-content h1 {
        font-size: 28px;
      }

      .intro-section h2 {
        font-size: 20px;
      }

      .intro-section p {
        font-size: 16px;
      }
    }
  </style>
</head>
<body>
  
  <div class="background-grid">
    <img src="skirt.jpg.jpg" alt="" width="350px" height="750px">
    <img src="frok (1).jpg" alt="" width="350px" height="750px">
    <img src="christmas.jpg" alt="" width="350px" height="750px">
    <img src="pink.jpg" alt="" width="350px" height="750px">
  </div>

  <div class="overlay">
    <div class="overlay-content">
      <a href="index.php" class="back-to-home">
        <span>←</span> Back to Home
      </a>

      <h1>About Thriftopia</h1>
      
      <div class="intro-section">
        <p><span class= "highlight">Thriftopia</span> is Nepal's premier online thrift marketplace where sustainability meets style. We're a student-driven platform revolutionizing how people buy and sell pre-loved fashion items.</p>
        <p>Our mission is simple: <span class="highlight">reduce fashion waste</span>, promote <span class="highlight">sustainable shopping</span>, and make quality second-hand clothing accessible to everyone in Nepal.</p>
        <p>At Thriftopia, we believe every piece of clothing deserves a second chance, and every person deserves access to affordable, stylish fashion while making <span class="highlight">eco-conscious choices</span>.</p>
        <p>Join our community of mindful shoppers and sellers who are transforming Nepal's fashion landscape, one pre-loved item at a time!</p>
      </div>

      <div class="how-it-works-section">
        <h2> FEATURES</h2>
        
        <div class="features-grid">
          <div class="feature-card">
            <h3><span class="icon"></span> Dual Role Platform</h3>
            <p>Every user can be both a <strong>buyer</strong> and a <strong>seller</strong>. Switch between roles seamlessly and enjoy the full Thriftopia experience.</p>
          </div>

          <div class="feature-card">
            <h3><span class="icon"></span> Unique Selling Model</h3>
            <p>Each seller can list <span class="highlight">only 1 quantity per product</span>, ensuring every item is truly unique and exclusive to our marketplace.</p>
          </div>

          <div class="feature-card">
            <h3><span class="icon"></span> Review System</h3>
            <p>Buyers can <strong>review products after receiving them</strong>, building trust and helping other shoppers make informed decisions. If a seller earns more tehn two review then it will automatically be removed </p>
          </div>

          <div class="feature-card">
            <h3><span class="icon"></span> Secure Transactions</h3>
            <p>Our platform ensures safe, reliable transactions with order tracking and seller-buyer communication features.</p>
          </div>
        </div>

        <div class="steps-section">
          <h3>Getting Started is Easy!</h3>
          <div class="steps-list">
            <div class="step-item">
              <div class="step-number">1</div>
              <h4>Sign Up</h4>
              <p>Create your free Thriftopia account in minutes</p>
            </div>
            <div class="step-item">
              <div class="step-number">2</div>
              <h4>Browse & Shop</h4>
              <p>Discover unique pre-loved items from sellers across Nepal</p>
            </div>
            <div class="step-item">
              <div class="step-number">3</div>
              <h4>List Your Items</h4>
              <p>Upload your pre-loved clothes and start earning</p>
            </div>
            <div class="step-item">
              <div class="step-number">4</div>
              <h4>Connect & Trade</h4>
              <p>Communicate with buyers/sellers and complete transactions</p>
            </div>
            <div class="step-item">
              <div class="step-number">5</div>
              <h4>Review & Repeat</h4>
              <p>Rate your experience and continue the thrift cycle</p>
            </div>
          </div>
        </div>
      </div>

      <div class="cta-section">
        <h2> BE A MEMEBER OF THRIFTOPIA</h2>
        <p>Become a member of Nepal's growing sustainable fashion community. Whether you want to find amazing deals or declutter your wardrobe, Thriftopia is your perfect partner!</p>
        
        <div class="cta-buttons">
          <a href="registration.php" class="btn btn-primary">
            <span></span> Join Thriftopia Today
          </a>
          <a href="login.php" class="btn btn-secondary">
            <span></span> Already a Member?
          </a>
        </div>
      </div>

      <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #e9ecef; text-align: center;">
        <p style="color: #6c757d; font-size: 16px; line-height: 1.6;">
          <strong>Thank you for being part of the Thriftopia journey.</strong><br>
          Together, we're making fashion more sustainable, affordable, and accessible – one pre-loved piece at a time!
        </p>
      </div>
    </div>
  </div>

</body>
</html>