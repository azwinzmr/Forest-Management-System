<?php
require_once 'header.php'; // Includes database connection and HTML head/nav
require_once __DIR__ . '/db_connection.php'; 
?>


<style>
    /* Homepage Hero Section */
    .hero-section {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%),
                    url('assets/img/forest-hero-bg.jpg') center/cover;
        color: white;
        padding: 100px 0;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.2);
        z-index: 1;
    }

    .hero-content {
        position: relative;
        z-index: 2;
        max-width: 800px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .hero-title {
        font-size: 3.5em;
        font-weight: 700;
        margin-bottom: 20px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        animation: fadeInUp 1s ease;
    }

    .hero-subtitle {
        font-size: 1.3em;
        margin-bottom: 30px;
        opacity: 0.95;
        animation: fadeInUp 1s ease 0.2s both;
    }

    .hero-cta {
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 15px 35px;
        border: 2px solid rgba(255,255,255,0.5);
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        font-size: 1.1em;
        transition: all 0.3s ease;
        display: inline-block;
        backdrop-filter: blur(10px);
        animation: fadeInUp 1s ease 0.4s both;
    }

    .hero-cta:hover {
        background: rgba(255,255,255,0.3);
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        color: white;
        text-decoration: none;
    }

    /* Main Features Grid */
    .features-section {
        padding: 80px 0;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    }

    .features-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 30px;
    }

    .section-header {
        text-align: center;
        margin-bottom: 60px;
    }

    .section-header h2 {
        font-size: 2.8em;
        font-weight: 700;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 15px;
    }

    .section-header p {
        font-size: 1.2em;
        color: #4a5568;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
        margin-top: 50px;
    }

    .feature-card {
        background: linear-gradient(145deg, #ffffff, #f7fafc);
        border: 1px solid rgba(255,255,255,0.8);
        border-radius: 20px;
        padding: 40px 30px;
        text-align: center;
        box-shadow: 
            15px 15px 30px #d1d9e6,
            -15px -15px 30px #ffffff;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }

    .feature-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }

    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 
            20px 20px 40px #d1d9e6,
            -20px -20px 40px #ffffff;
    }

    .feature-card:hover::before {
        transform: scaleX(1);
    }

    .feature-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 25px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        position: relative;
        overflow: hidden;
    }

    .feature-icon::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 100%);
    }

    .feature-icon i {
        font-size: 2.5em;
        color: white;
        position: relative;
        z-index: 1;
    }

    .feature-title {
        font-size: 1.5em;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 15px;
    }

    .feature-description {
        color: #4a5568;
        line-height: 1.6;
        margin-bottom: 25px;
    }

    .feature-link {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-block;
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }

    .feature-link:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        color: white;
        text-decoration: none;
    }

    /* Stats Section */
    .stats-section {
        background: linear-gradient(135deg, #2d3748, #4a5568);
        color: white;
        padding: 60px 0;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 40px;
        max-width: 1000px;
        margin: 0 auto;
        padding: 0 30px;
    }

    .stat-item {
        text-align: center;
    }

    .stat-number {
        font-size: 3em;
        font-weight: 700;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        display: block;
        margin-bottom: 10px;
    }

    .stat-label {
        font-size: 1.1em;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .feature-card {
        opacity: 0;
        animation: fadeInUp 0.6s ease forwards;
    }

    .feature-card:nth-child(1) { animation-delay: 0.1s; }
    .feature-card:nth-child(2) { animation-delay: 0.2s; }
    .feature-card:nth-child(3) { animation-delay: 0.3s; }
    .feature-card:nth-child(4) { animation-delay: 0.4s; }

    /* Responsive Design */
    @media (max-width: 768px) {
        .hero-title {
            font-size: 2.5em;
        }
        
        .hero-subtitle {
            font-size: 1.1em;
        }
        
        .features-container {
            padding: 0 20px;
        }
        
        .section-header h2 {
            font-size: 2.2em;
        }
        
        .features-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
        }
    }
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-content">
        <h1 class="hero-title">Forest Management System</h1>
        <p class="hero-subtitle">Comprehensive tools for sustainable forestry management, growth simulation, and timber analysis</p>
        <a href="#features" class="hero-cta">Explore Features</a>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="features-section">
    <div class="features-container">
        <div class="section-header">
            <h2>Forestry Management Tools</h2>
            <p>Comprehensive solutions for modern forestry professionals and researchers</p>
        </div>
        
        <div class="features-grid">
            <!-- Forest Background -->
            <div class="feature-card" onclick="location.href='forest-background.php'">
                <div class="feature-icon">
                    <i class="🌲">🌲</i>
                </div>
                <h3 class="feature-title">Forest Background</h3>
                <p class="feature-description">Understand the fundamentals of forestry, including its importance and management strategies.</p>
            </div>

            <!-- Forestry Calculations -->
            <div class="feature-card" onclick="location.href='forestry-calculations.php'">
                <div class="feature-icon">
                    <i class="📊">📊</i>
                </div>
                <h3 class="feature-title">Forestry Calculations</h3>
                <p class="feature-description">Learn to calculate tree volume, production yield, growth rates, and damage impact.</p>
            </div>

            <!-- Growth Simulation -->
            <div class="feature-card" onclick="location.href='growth-simulation.php'">
                <div class="feature-icon">
                    <i class="📈">📈</i>
                </div>
                <h3 class="feature-title">Growth Simulation</h3>
                <p class="feature-description">Simulate forest growth over decades, predicting changes in tree size and forest composition.</p>
            </div>

            <!-- Logging Analysis -->
            <div class="feature-card" onclick="location.href='logging-analysis.php'">
                <div class="feature-icon">
                    <i class="🪓">🪓</i>
                </div>
                <h3 class="feature-title">Logging Analysis</h3>
                <p class="feature-description">Analyze cutting regimes, tree damage, and sustainable timber harvesting practices.</p>
            </div>
        </div>
    </div>
</section>

<!-- Additional Features or Tree Species Preview -->
<section class="features-section" style="padding-top: 60px;">
    <div class="features-container">
        <div class="section-header">
            <h2>Explore Tree Species</h2>
            <p>Discover the diverse tree species in Mixed Dipterocarp Forests</p>
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
            <a href="#portfolio" class="hero-cta" style="background: linear-gradient(135deg, #667eea, #764ba2); border: none; animation: none;">
                View All Tree Species
            </a>
        </div>
    </div>
</section>

<script>
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });
    });

    // Intersection Observer for animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
            }
        });
    }, observerOptions);

    // Observe all feature cards
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.feature-card').forEach(card => {
            observer.observe(card);
        });

        // Counter animation for stats
        const counters = document.querySelectorAll('.stat-number');
        counters.forEach(counter => {
            const target = parseInt(counter.textContent);
            const increment = target / 100;
            let current = 0;
            
            const updateCounter = () => {
                if (current < target) {
                    current += increment;
                    counter.textContent = Math.ceil(current) + (counter.textContent.includes('+') ? '+' : '');
                    setTimeout(updateCounter, 20);
                } else {
                    counter.textContent = target + (counter.textContent.includes('+') ? '+' : '');
                }
            };
            
            // Start animation when stats section is visible
            const statsObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        updateCounter();
                        statsObserver.unobserve(entry.target);
                    }
                });
            });
            
            statsObserver.observe(counter.closest('.stats-section'));
        });
    });

    // Add hover effects for feature cards
    document.querySelectorAll('.feature-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
</script>


<style>
    /* Tree Species Specific Styles */
    .tree-species-container {
        max-width: 1400px;
        margin: 30px auto;
        padding: 40px;
        background: rgba(255,255,255,0.95);
        border-radius: 20px;
        box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        backdrop-filter: blur(10px);
    }

    .section-title {
        text-align: center;
        margin-bottom: 50px;
    }

    .section-title h2 {
        font-size: 3em;
        font-weight: 700;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 20px;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    .section-title p {
        font-size: 1.1em;
        color: #4a5568;
        max-width: 800px;
        margin: 0 auto;
        line-height: 1.8;
    }

    /* Tree grid layout */
    .tree-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 30px;
        margin-top: 40px;
    }

    .tree-card {
        background: linear-gradient(145deg, #ffffff, #f7fafc);
        border: 1px solid rgba(255,255,255,0.8);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 
            12px 12px 24px #d1d9e6,
            -12px -12px 24px #ffffff;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .tree-card:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: 
            20px 20px 40px #d1d9e6,
            -20px -20px 40px #ffffff;
    }

    .tree-image {
        position: relative;
        overflow: hidden;
        height: 250px;
    }

    .tree-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }

    .tree-card:hover .tree-image img {
        transform: scale(1.1);
    }

    .tree-image::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.3) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .tree-card:hover .tree-image::after {
        opacity: 1;
    }

    .tree-info {
        padding: 25px;
        position: relative;
    }

    .tree-info h4 {
        font-size: 1.4em;
        font-weight: 700;
        color: #2d3748;
        margin: 0 0 15px 0;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .tree-info p {
        color: #4a5568;
        margin: 0;
        line-height: 1.6;
        font-size: 0.95em;
    }

    /* Category badges */
    .category-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 0.8em;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    /* Filter buttons */
    .filter-container {
        text-align: center;
        margin: 40px 0;
    }

    .filter-btn {
        background: linear-gradient(145deg, #f7fafc, #edf2f7);
        border: 2px solid #e2e8f0;
        color: #4a5568;
        padding: 12px 25px;
        margin: 0 8px 10px 0;
        border-radius: 25px;
        cursor: pointer;
        font-size: 0.9em;
        font-weight: 600;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-block;
    }

    .filter-btn:hover,
    .filter-btn.active {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .tree-species-container {
            margin: 15px;
            padding: 20px;
        }
        
        .section-title h2 {
            font-size: 2.2em;
        }
        
        .tree-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .filter-btn {
            display: block;
            margin: 10px auto;
            width: 200px;
        }
    }

    /* Animation for cards appearing */
    .tree-card {
        opacity: 0;
        animation: fadeInUp 0.6s ease forwards;
    }

    .tree-card:nth-child(1) { animation-delay: 0.1s; }
    .tree-card:nth-child(2) { animation-delay: 0.2s; }
    .tree-card:nth-child(3) { animation-delay: 0.3s; }
    .tree-card:nth-child(4) { animation-delay: 0.4s; }
    .tree-card:nth-child(5) { animation-delay: 0.5s; }
    .tree-card:nth-child(6) { animation-delay: 0.6s; }
    .tree-card:nth-child(7) { animation-delay: 0.7s; }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<!-- Tree Species Section -->
<section id="portfolio" class="portfolio">
    <div class="tree-species-container">
        <div class="section-title">
            <h2>Tree Species</h2>
            <p>A Mixed Dipterocarp Forest is a type of tropical rainforest that is characteristic of Southeast Asia. It's named after the dominant tree family found in these forests, which is Dipterocarpaceae. These forests are incredibly diverse in terms of flora and fauna and are considered one of the most biologically rich ecosystems on the planet.</p>
        </div>

        <div class="filter-container">
            <button class="filter-btn active" onclick="filterTrees('all')">All Species</button>
            <button class="filter-btn" onclick="filterTrees('dipterocarp')">Dipterocarp</button>
            <button class="filter-btn" onclick="filterTrees('non-dipterocarp')">Non-Dipterocarp</button>
            <button class="filter-btn" onclick="filterTrees('commercial')">Commercial</button>
            <button class="filter-btn" onclick="filterTrees('non-commercial')">Non-Commercial</button>
        </div>

        <div class="tree-grid" id="treeGrid">
            <div class="tree-card" data-category="dipterocarp commercial">
                <div class="tree-image">
                    <img src="assets/mersawa.jpg" alt="Mersawa tree">
                    <div class="category-badge">Commercial</div>
                </div>
                <div class="tree-info">
                    <h4>Mersawa</h4>
                    <p><strong>Anisoptera spp.</strong> Mersawa belongs to the Dipterocarpaceae family and is valued for its timber, which is used in construction, furniture making, and woodworking. Known for its durability and attractive grain patterns.</p>
                </div>
            </div>

            <div class="tree-card" data-category="dipterocarp commercial">
                <div class="tree-image">
                    <img src="assets/keruing.png" alt="Keruing tree">
                    <div class="category-badge">Commercial</div>
                </div>
                <div class="tree-info">
                    <h4>Keruing</h4>
                    <p><strong>Dipterocarpus spp.</strong> Keruing is another hardwood used in various applications, including boat-building, flooring, and outdoor furniture due to its durability and resistance to decay. Highly prized in marine construction.</p>
                </div>
            </div>

            <div class="tree-card" data-category="dipterocarp commercial">
                <div class="tree-image">
                    <img src="assets/Dipterocarp Commercial.jpg" alt="Dipterocarp Commercial">
                    <div class="category-badge">Commercial</div>
                </div>
                <div class="tree-info">
                    <h4>Dipterocarp Commercial</h4>
                    <p>Refers to commercially valuable trees belonging to the Dipterocarpaceae family. These trees are primarily harvested for their high-quality timber, which is used in construction, furniture making, and other wood products with excellent structural properties.</p>
                </div>
            </div>

            <div class="tree-card" data-category="dipterocarp non-commercial">
                <div class="tree-image">
                    <img src="assets/Dipterocarp Non Commercial.jpeg" alt="Dipterocarp Non Commercial">
                    <div class="category-badge">Ecological</div>
                </div>
                <div class="tree-info">
                    <h4>Dipterocarp Non Commercial</h4>
                    <p>This category includes Dipterocarp species that are not extensively harvested for commercial purposes, perhaps due to inferior wood quality or limited distribution. They play crucial ecological roles in forest ecosystems.</p>
                </div>
            </div>

            <div class="tree-card" data-category="non-dipterocarp commercial">
                <div class="tree-image">
                    <img src="assets/Non Dipterocarp Commercial.jpg" alt="Non Dipterocarp Commercial">
                    <div class="category-badge">Commercial</div>
                </div>
                <div class="tree-info">
                    <h4>Non Dipterocarp Commercial</h4>
                    <p>This category includes various hardwoods and softwoods used in construction, furniture making, and other industries. These species complement Dipterocarp timber with unique properties and applications.</p>
                </div>
            </div>

            <div class="tree-card" data-category="non-dipterocarp non-commercial">
                <div class="tree-image">
                    <img src="assets/Non Dipterocarp Non Commercial.jpg" alt="Non Dipterocarp Non Commercial">
                    <div class="category-badge">Ecological</div>
                </div>
                <div class="tree-info">
                    <h4>Non Dipterocarp Non Commercial</h4>
                    <p>These include a wide range of tree species with different ecological roles and uses, such as fruit trees, ornamentals, or species with limited economic value. Essential for biodiversity and ecosystem balance.</p>
                </div>
            </div>

            </div>
        </div>
    </div>
</section>

<!-- JavaScript for filtering functionality -->
<script>
    function filterTrees(category) {
        const cards = document.querySelectorAll('.tree-card');
        const buttons = document.querySelectorAll('.filter-btn');
        
        // Update active button
        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        // Filter cards with animation
        cards.forEach((card, index) => {
            const cardCategories = card.dataset.category.split(' ');
            const shouldShow = category === 'all' || cardCategories.includes(category);
            
            if (shouldShow) {
                card.style.display = 'block';
                card.style.animation = `fadeInUp 0.6s ease forwards`;
                card.style.animationDelay = `${index * 0.1}s`;
            } else {
                card.style.display = 'none';
            }
        });
    }

    // Add smooth scrolling and interaction effects
    document.addEventListener('DOMContentLoaded', function() {
        // Intersection Observer for scroll animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                }
            });
        }, {
            threshold: 0.1
        });

        document.querySelectorAll('.tree-card').forEach(card => {
            observer.observe(card);
        });
    });
</script>

<?php require_once 'footer.php'; ?>