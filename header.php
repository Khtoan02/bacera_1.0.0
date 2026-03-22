<?php
/**
 * Header
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-gray-50 text-gray-800 font-sans antialiased'); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site flex flex-col min-h-screen">
    
    <!-- Header -->
    <header id="masthead" class="site-header sticky top-0 z-50 w-full backdrop-blur-md bg-white/80 border-b border-gray-100 shadow-sm transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <?php if ( has_custom_logo() ) : ?>
                        <?php the_custom_logo(); ?>
                    <?php else : ?>
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary to-secondary">
                            <?php bloginfo( 'name' ); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Desktop Menu -->
                <nav class="hidden md:flex space-x-8">
                    <?php
                    if ( has_nav_menu( 'menu-1' ) ) {
                        wp_nav_menu( array(
                            'theme_location' => 'menu-1',
                            'menu_id'        => 'primary-menu',
                            'container'      => false,
                            'menu_class'     => 'flex items-center space-x-8 text-sm font-medium text-gray-700',
                            'fallback_cb'    => false,
                        ) );
                    } else {
                        ?>
                        <a href="#" class="text-gray-700 hover:text-primary transition-colors font-medium">Trang chủ</a>
                        <a href="#" class="text-gray-700 hover:text-primary transition-colors font-medium">Về chúng tôi</a>
                        <a href="#" class="text-gray-700 hover:text-primary transition-colors font-medium">Dịch vụ</a>
                        <a href="#" class="text-gray-700 hover:text-primary transition-colors font-medium">Lĩnh vực</a>
                        <a href="#" class="text-gray-700 hover:text-primary transition-colors font-medium">Tin tức</a>
                        <?php
                    }
                    ?>
                </nav>

                <!-- Actions / Button -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="#" class="inline-flex items-center justify-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-full text-white bg-gradient-to-r from-primary to-secondary hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
                        Nhận tư vấn ngay
                    </a>
                </div>

                <!-- Mobile menu button -->
                <div class="flex items-center md:hidden">
                    <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-primary hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary transition-colors" aria-expanded="false">
                        <span class="sr-only">Mở menu</span>
                        <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main id="primary" class="site-main flex-grow mt-8">
