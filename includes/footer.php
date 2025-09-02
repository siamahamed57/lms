<?php
// includes/footer.php
?>
<footer class="mt-20 py-16 bg-card-bg transition-colors duration-300">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="flex flex-col md:flex-row items-center justify-between pb-10 border-b border-header-border text-center md:text-left">
            <div class="mb-6 md:mb-0">
                <h3 class="text-3xl font-bold text-[#b915ff] mb-2">Ready to Learn?</h3>
                <p class="text-card-color opacity-80">Join our newsletter for the latest course updates and offers.</p>
            </div>
            <div class="flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:space-x-4 w-full md:w-auto">
                <input type="email" placeholder="Enter your email" aria-label="Enter your email" class="w-full sm:w-64 px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b915ff] transition-all duration-300 bg-transparent text-card-color border border-header-border">
                <button class="w-full sm:w-auto px-6 py-2 rounded-lg font-semibold bg-[#b915ff] text-white hover:bg-[#9c00e6] transition-colors duration-300">
                    Subscribe
                </button>
            </div>
        </div>

        <div class="mt-10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-y-10 lg:gap-x-10 text-center md:text-left">
            <div class="flex flex-col items-center md:items-start">
                <a href="/home" class="font-bold text-4xl text-[#b915ff] hover:text-[#9c00e6] transition-colors duration-300">UNIES</a>
                <p class="text-card-color text-sm mt-2 max-w-sm">
                    Empowering minds through flexible and accessible online education.
                </p>
            </div>

            <div class="lg:col-span-2 flex flex-col sm:flex-row sm:justify-around space-y-8 sm:space-y-0 text-center sm:text-left">
                <nav>
                    <h4 class="font-semibold text-lg mb-3 text-[#b915ff]">Platform</h4>
                    <ul class="space-y-2">
                        <li><a href="courses" class="text-card-color hover:text-[#60a5fa] transition-colors duration-200">Courses</a></li>
                        <li><a href="about" class="text-card-color hover:text-[#60a5fa] transition-colors duration-200">About Us</a></li>
                        <li><a href="contact" class="text-card-color hover:text-[#60a5fa] transition-colors duration-200">Contact</a></li>
                    </ul>
                </nav>
                <nav>
                    <h4 class="font-semibold text-lg mb-3 text-[#b915ff]">Resources</h4>
                    <ul class="space-y-2">
                        <li><a href="blog" class="text-card-color hover:text-[#60a5fa] transition-colors duration-200">Blog</a></li>
                        <li><a href="support" class="text-card-color hover:text-[#60a5fa] transition-colors duration-200">Support</a></li>
                        <li><a href="privacy" class="text-card-color hover:text-[#60a5fa] transition-colors duration-200">Privacy Policy</a></li>
                    </ul>
                </nav>
            </div>

            <div class="flex flex-col items-center md:items-end">
                <h4 class="font-semibold text-lg mb-3 text-[#b915ff]">Follow Us</h4>
                <div class="flex space-x-4">
                    <a href="https://facebook.com" target="_blank" aria-label="Facebook" class="text-card-color hover:text-[#b915ff] transition-colors duration-200"><i class="fab fa-facebook text-2xl"></i></a>
                    <a href="https://twitter.com" target="_blank" aria-label="Twitter" class="text-card-color hover:text-[#b915ff] transition-colors duration-200"><i class="fab fa-twitter text-2xl"></i></a>
                    <a href="https://linkedin.com" target="_blank" aria-label="LinkedIn" class="text-card-color hover:text-[#b915ff] transition-colors duration-200"><i class="fab fa-linkedin text-2xl"></i></a>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-12 text-sm text-card-color opacity-70">
            <p>&copy; <span id="current-year"></span> UNIES. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const yearSpan = document.getElementById('current-year');
        yearSpan.textContent = new Date().getFullYear();
    });
</script>