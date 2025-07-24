

<footer class="py-5 mt-5" style="background: linear-gradient(135deg, #01344d 0%, #0a4d6b 100%);">
    <div class="container">
        <div class="row">
            <!-- Company Info -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="d-flex align-items-center mb-3">
                    <img src="images/logo.png" alt="CompNest Logo" class="img-fluid me-2" style="max-width: 40px;">
                    <h5 class="text-white mb-0">CompNest</h5>
                </div>
                <p class="text-light">Your trusted partner for all computer hardware needs. We provide quality products at competitive prices with excellent customer service.</p>
                
                
            </div>
            
            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-white mb-3">Quick Links</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="index.php" class="text-light text-decoration-none footer-link">
                        <i class="bi bi-chevron-right small"></i> Home
                    </a></li>
                    <li class="mb-2"><a href="products.php" class="text-light text-decoration-none footer-link">
                        <i class="bi bi-chevron-right small"></i> All Products
                    </a></li>
                    <?php if (isLoggedIn()): ?>
                    <li class="mb-2"><a href="cart.php" class="text-light text-decoration-none footer-link">
                        <i class="bi bi-chevron-right small"></i> Shopping Cart
                    </a></li>
                    <li class="mb-2"><a href="orders.php" class="text-light text-decoration-none footer-link">
                        <i class="bi bi-chevron-right small"></i> Order History
                    </a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Categories -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-white mb-3">Categories</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="products.php?category=laptops" class="text-light text-decoration-none footer-link">
                        <i class="bi bi-chevron-right small"></i> Laptops
                    </a></li>
                    <li class="mb-2"><a href="products.php?category=desktops" class="text-light text-decoration-none footer-link">
                        <i class="bi bi-chevron-right small"></i> Desktops
                    </a></li>
                    <li class="mb-2"><a href="products.php?category=graphic_cards" class="text-light text-decoration-none footer-link">
                        <i class="bi bi-chevron-right small"></i> Graphics Cards
                    </a></li>
                    <li class="mb-2"><a href="products.php?category=memories" class="text-light text-decoration-none footer-link">
                        <i class="bi bi-chevron-right small"></i> Memory
                    </a></li>
                    <li class="mb-2"><a href="products.php?category=storage" class="text-light text-decoration-none footer-link">
                        <i class="bi bi-chevron-right small"></i> Storage
                    </a></li>
                </ul>
            </div>
            
            <!-- Contact Info -->
            <div class="col-lg-4 col-md-6 mb-4">
                <h6 class="text-white mb-3">Contact Information</h6>
                <div class="contact-info">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-envelope text-light me-2"></i>
                        <a href="mailto:info@compnest.com" class="text-light text-decoration-none">info@compnest.com</a>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-telephone text-light me-2"></i>
                        <a href="tel:+15551234567" class="text-light text-decoration-none">+1 (555) 123-4567</a>
                    </div>
                    <div class="d-flex align-items-start mb-3">
                        <i class="bi bi-geo-alt text-light me-2 mt-1"></i>
                        <span class="text-light">123 Tech Street<br>Brampton, ON L6Y 1N2<br>Canada</span>
                    </div>
                    
                    
                </div>
            </div>
        </div>
        
        <hr class="my-4 border-light">
        
        <!-- Bottom Footer -->
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="text-light mb-0">&copy; <?php echo date('Y'); ?> CompNest. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="footer-links">
                    <a href="#" class="text-light text-decoration-none me-3 small">Privacy Policy</a>
                    <a href="#" class="text-light text-decoration-none me-3 small">Terms of Service</a>
                    <a href="#" class="text-light text-decoration-none small">Return Policy</a>
                </div>
            </div>
        </div>
        
        <!-- Trust Badges -->
        <div class="row mt-3">
            <div class="col-12 text-center">
                <div class="trust-badges">
                    <span class="badge bg-light text-dark me-2 mb-2">
                        <i class="bi bi-shield-check"></i> Secure Payment
                    </span>
                    <span class="badge bg-light text-dark me-2 mb-2">
                        <i class="bi bi-truck"></i> Free Shipping Over $100
                    </span>
                    <span class="badge bg-light text-dark me-2 mb-2">
                        <i class="bi bi-arrow-counterclockwise"></i> 30-Day Returns
                    </span>
                    <span class="badge bg-light text-dark mb-2">
                        <i class="bi bi-headset"></i> 24/7 Support
                    </span>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button type="button" class="btn btn-primary btn-floating" id="backToTop" style="position: fixed; bottom: 20px; right: 20px; display: none; z-index: 1000; border-radius: 50%; width: 50px; height: 50px;">
    <i class="bi bi-arrow-up"></i>
</button>

<style>
.social-link:hover {
    color: #f8f9fa !important;
    transform: translateY(-2px);
    transition: all 0.3s ease;
}

.footer-link:hover {
    color: #f8f9fa !important;
    padding-left: 5px;
    transition: all 0.3s ease;
}

.trust-badges .badge {
    transition: transform 0.2s ease;
}

.trust-badges .badge:hover {
    transform: translateY(-2px);
}

#backToTop {
    transition: all 0.3s ease;
}

#backToTop:hover {
    transform: scale(1.1);
}

.business-hours {
    background-color: rgba(255, 255, 255, 0.1);
    padding: 10px;
    border-radius: 5px;
    border-left: 3px solid #fff;
}
</style>

<script>

document.getElementById('newsletterForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const email = this.querySelector('input[type="email"]').value;
    
    // Here you would typically send the email to your server
    // For now, we'll just show a success message
    alert('Thank you for subscribing! We\'ll keep you updated with the latest deals.');
    this.reset();
});

// Back to top functionality
window.addEventListener('scroll', function() {
    const backToTop = document.getElementById('backToTop');
    if (window.pageYOffset > 300) {
        backToTop.style.display = 'block';
    } else {
        backToTop.style.display = 'none';
    }
});

document.getElementById('backToTop')?.addEventListener('click', function() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});
</script>