<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZYVA - Discover the Next Big Thing</title>
    
    <!-- Design System CSS -->
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/globals.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Main JS -->
    <script src="assets/js/main.js" defer></script>

    <style>
        /* Landing Page Specific Styles */
        .landing-navbar {
            position: absolute;
            top: 0;
            width: 100%;
            z-index: 100;
            background: transparent;
            border-bottom: none;
            padding: var(--spacing-4) 0;
        }

        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            text-align: center;
            padding-top: var(--spacing-16);
        }

        .hero-bg-gradient {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: radial-gradient(circle at 50% 0%, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
            z-index: 0;
        }

        .hero-content {
            position: relative;
            z-index: 10;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-title {
            font-size: clamp(3rem, 8vw, 5.5rem);
            font-weight: 700;
            letter-spacing: -0.04em;
            line-height: 1;
            margin-bottom: var(--spacing-6);
            background: linear-gradient(180deg, #FFFFFF 0%, rgba(255, 255, 255, 0.7) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: var(--text-xl);
            color: var(--color-text-secondary);
            margin-bottom: var(--spacing-8);
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-cta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-4);
        }

        .btn-large {
            padding: var(--spacing-4) var(--spacing-8);
            font-size: var(--text-base);
            border-radius: var(--radius-full);
        }

        .trending-section {
            padding: var(--spacing-16) 0;
            background-color: var(--color-bg-primary);
            position: relative;
            z-index: 10;
        }

        .trending-grid {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-3);
            justify-content: center;
            margin-top: var(--spacing-8);
        }

        .trending-tag {
            font-size: var(--text-lg);
            padding: var(--spacing-3) var(--spacing-6);
            background-color: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-full);
            color: var(--color-text-primary);
            transition: all var(--transition-normal);
            cursor: pointer;
        }

        .trending-tag:hover {
            transform: translateY(-2px);
            background-color: var(--color-surface-hover);
            border-color: var(--color-primary);
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.2);
        }
    </style>
</head>
<body>

    <!-- Transparent Landing Navbar -->
    <nav class="landing-navbar">
        <div class="container">
            <div class="flex items-center justify-between w-full">
                <a href="index.php" class="navbar-brand">
                    <i data-lucide="zap" class="text-accent"></i>
                    <span>ZYVA</span>
                </a>
                <div class="flex items-center gap-6">
                    <a href="pages/search.php" class="text-secondary hover:text-primary transition-fast">Explore</a>
                    <a href="pages/auth.php" class="text-secondary hover:text-primary transition-fast">Login</a>
                    <a href="pages/home.php" class="btn btn-primary">Start Watching</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero-section">
        <div class="hero-bg-gradient"></div>
        <div class="container">
            <div class="hero-content animate-slide-up">
                <div class="inline-block mb-6 tag" style="background: rgba(99, 102, 241, 0.1); border-color: rgba(99, 102, 241, 0.2); color: var(--color-primary);">
                    <i data-lucide="sparkles" style="width: 14px; height: 14px;"></i>
                    Discover what moves you
                </div>
                <h1 class="hero-title">Shorts, driven by passion.</h1>
                <p class="hero-subtitle">
                    A hashtag-first video platform where you discover content through interests, not just followers. Experience the next generation of social media.
                </p>
                <div class="hero-cta">
                    <a href="pages/home.php" class="btn btn-primary btn-large">
                        Enter App
                        <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
                    </a>
                    <a href="pages/upload.php" class="btn btn-secondary btn-large">
                        <i data-lucide="upload" style="width: 18px; height: 18px;"></i>
                        Upload Video
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Trending Hashtags Section -->
    <section class="trending-section">
        <div class="container text-center">
            <h2 class="h3 mb-2">Trending Right Now</h2>
            <p class="text-secondary mb-8">Dive into the most popular topics across the globe.</p>
            
            <div class="trending-grid animate-fade-in" style="animation-delay: 0.2s;">
                <div class="trending-tag">#TechStartup</div>
                <div class="trending-tag">#DesignInspiration</div>
                <div class="trending-tag">#Filmmaking</div>
                <div class="trending-tag">#WebDevelopment</div>
                <div class="trending-tag">#3DAnimation</div>
                <div class="trending-tag">#MusicProduction</div>
                <div class="trending-tag">#StreetArt</div>
                <div class="trending-tag">#Minimalism</div>
                <div class="trending-tag">#Aesthetics</div>
            </div>
        </div>
    </section>

</body>
</html>
