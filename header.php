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
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-stone-50 font-sans antialiased overflow-x-hidden'); ?>>
<?php wp_body_open(); ?>

<div id="page" class="flex flex-col min-h-screen">
    
    <!-- Header Container -->
    <header class="w-full bg-white relative z-50 border-b border-stone-200">
        <div class="max-w-[1920px] mx-auto px-4 lg:px-8 py-4 flex justify-between items-center h-[76px]">
            
            <!-- Logo area -->
            <a href="<?php echo esc_url(home_url('/')); ?>" class="w-24 h-8 relative flex items-center justify-center hover:opacity-80 transition-opacity">
                <!-- Fallback geometric logo matching the Figma export -->
                <div class="w-2 h-6 absolute left-[45px] top-0 bg-stone-600"></div>
                <div class="w-20 h-5 absolute left-0 top-[4px] bg-stone-600"></div>
                <?php if (has_custom_logo()) {
                    $custom_logo_id = get_theme_mod('custom_logo');
                    $logo = wp_get_attachment_image_src($custom_logo_id , 'full');
                    echo '<img src="'.esc_url($logo[0]).'" class="absolute inset-0 w-full h-full object-contain bg-white z-10" alt="'.get_bloginfo('name').'">';
                } ?>
            </a>

            <!-- Navigation Links -->
            <nav class="hidden lg:flex items-stretch justify-center gap-8 h-[76px]">
                
                <!-- Shop Mega Menu -->
                <div class="group flex items-center h-full cursor-pointer relative">
                    <div class="flex items-center gap-1 group-hover:text-accent-500 text-stone-600 transition-colors">
                        <span class="text-[17px] font-medium font-sans">Shop</span>
                        <svg class="w-4 h-4 transition-transform group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                    
                    <!-- Dropdown Panel -->
                    <div class="absolute top-[76px] left-[50%] -translate-x-[50%] w-[1000px] bg-white rounded-lg shadow-xl shadow-stone-900/5 border border-stone-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform translate-y-2 group-hover:translate-y-0 cursor-default p-6 z-[60]">
                        <div class="flex">
                            <!-- Left: Title & CTA -->
                            <div class="w-64 shrink-0 flex flex-col justify-start items-start pr-6">
                                <h3 class="text-stone-800 text-[20px] font-medium leading-[28px] mb-2 font-['Bricolage_Grotesque']">Mua hàng theo công năng</h3>
                                <p class="text-stone-600 text-[15px] font-normal leading-relaxed mb-6">Khám phá sản phẩm theo danh mục để mua sắm nhanh chóng, tiện lợi và dễ dàng hơn.</p>
                                <a href="#" class="mt-auto px-6 py-3.5 bg-accent-500 hover:bg-accent-600 text-white text-[15px] font-medium rounded-lg transition-colors whitespace-nowrap text-center">Xem tất cả</a>
                            </div>
                            
                            <!-- Divider -->
                            <div class="w-px bg-stone-200 self-stretch mx-4"></div>
                            
                            <!-- Right: Category Grid -->
                            <!-- Note: Added custom classes to simulate the Figma boxes -->
                            <div class="flex-1 pl-4 grid grid-cols-4 gap-4">
                                <?php 
                                $categories = [
                                    ['name' => 'Kitchen', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                                    ['name' => 'Plates', 'icon' => 'M12 2A10 10 0 1 0 22 12A10 10 0 0 0 12 2Z'],
                                    ['name' => 'Vases', 'icon' => 'M3 3h18l-3 18H6L3 3z'],
                                    ['name' => 'Storage', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                                    ['name' => 'Teapots', 'icon' => 'M13 10V3L4 14h7v8L20 11h-7z'],
                                    ['name' => 'Drinkware', 'icon' => 'M12 2l-3 5h6l-3-5zm0 5v15M6 7l12 0 M8 22h8'],
                                    ['name' => 'Bowls', 'icon' => 'M2 12A10 10 0 0 0 22 12Z'],
                                    ['name' => 'Serveware', 'icon' => 'M3 3h18v18H3z']
                                ];
                                foreach($categories as $index => $cat): 
                                    if ($index < 7): // Show 7 blocks per design
                                ?>
                                <a href="#" class="group/cat flex flex-col items-center justify-center p-4 h-[120px] rounded-lg border border-stone-200 hover:border-accent-400 hover:shadow-md transition-all">
                                    <div class="w-12 h-12 mb-3 bg-stone-100 rounded-lg flex items-center justify-center text-stone-700 group-hover/cat:text-accent-500 transition-colors">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $cat['icon']; ?>"/></svg>
                                    </div>
                                    <span class="text-stone-600 text-[14px] font-medium"><?php echo $cat['name']; ?></span>
                                </a>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Workshop Mega Menu -->
                <div class="group flex items-center h-full cursor-pointer relative">
                    <div class="flex items-center gap-1 group-hover:text-accent-500 text-stone-600 transition-colors">
                        <span class="text-[17px] font-medium font-sans">Workshop</span>
                        <svg class="w-4 h-4 transition-transform group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>

                    <!-- Dropdown Panel -->
                    <div class="absolute top-[76px] left-[50%] -translate-x-[50%] w-[1280px] h-auto min-h-[400px] bg-white rounded-lg shadow-xl shadow-stone-900/5 border border-stone-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform translate-y-2 group-hover:translate-y-0 cursor-default p-8 z-[60]">
                        <div class="flex">
                            <!-- Left: Title & CTA -->
                            <div class="w-64 shrink-0 flex flex-col justify-start items-start pr-8">
                                <h3 class="text-stone-800 text-[20px] font-medium leading-[28px] mb-2 font-['Bricolage_Grotesque']">Khám phá workshop</h3>
                                <p class="text-stone-600 text-[15px] font-normal leading-relaxed">Khám phá workshop làm gốm và trải nghiệm nghệ thuật thủ công đầy cảm hứng cùng chúng tôi.</p>
                                <a href="#" class="mt-8 px-6 py-3.5 bg-accent-500 hover:bg-accent-600 text-white text-[15px] font-medium rounded-lg transition-colors whitespace-nowrap text-center">Xem tất cả lịch</a>
                            </div>
                            
                            <!-- Divider -->
                            <div class="w-px bg-stone-200 self-stretch mx-2"></div>
                            
                            <!-- Right: Horizontal cards grid (2x2) -->
                            <div class="flex-1 pl-8 grid grid-cols-2 gap-x-8 gap-y-6">
                                <?php 
                                $workshops = [
                                    ['title' => 'Pottery Wheel Throwing', 'desc' => 'A peaceful, hands-on journey for beginners and curious minds.', 'price' => 'From $38.00/Person', 'detail' => 'Day:13/16 - Noon: 1/16'],
                                    ['title' => 'Hand-building Pottery', 'desc' => 'Shape, carve, and create your own ceramic piece by hand.', 'price' => 'From $38.00/Person', 'detail' => 'Day:13/16 - Noon: 1/16'],
                                    ['title' => 'Hanoi School Class', 'desc' => 'Shape, carve, and create your own ceramic piece by hand.', 'price' => 'Contact us', 'detail' => 'Book Your School Visit'],
                                    ['title' => 'Team-building Pottery', 'desc' => 'Shape, carve, and create your own ceramic piece by hand.', 'price' => 'Contact us', 'detail' => 'Start Organize Your Event']
                                ];
                                foreach($workshops as $ws): 
                                ?>
                                <a href="#" class="group/ws flex items-center gap-5 p-2 rounded-xl hover:bg-stone-50 transition-colors">
                                    <div class="w-[140px] h-[140px] overflow-hidden rounded-lg shrink-0 relative bg-neutral-200">
                                        <img src="https://placehold.co/140x140?text=Workshop" class="w-full h-full object-cover group-hover/ws:scale-105 transition-transform duration-700" alt="">
                                    </div>
                                    <div class="flex flex-col flex-1 truncate">
                                        <h4 class="text-stone-800 text-[17px] font-medium mb-1 truncate whitespace-nowrap group-hover/ws:text-accent-500 transition-colors font-['Bricolage_Grotesque']"><?php echo $ws['title']; ?></h4>
                                        <p class="text-stone-600 text-[14px] leading-snug opacity-80 mb-3 text-wrap"><?php echo $ws['desc']; ?></p>
                                        <div class="mt-auto">
                                            <p class="text-stone-800 text-[15px] font-medium truncate"><?php echo $ws['price']; ?></p>
                                            <p class="text-stone-500 text-[12px] mt-1 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                <?php echo $ws['detail']; ?>
                                            </p>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- About Us Simple Dropdown -->
                <div class="group flex items-center h-full cursor-pointer relative">
                    <div class="flex items-center gap-1 group-hover:text-accent-500 text-stone-600 transition-colors">
                        <span class="text-[17px] font-medium font-sans">About us</span>
                        <svg class="w-4 h-4 transition-transform group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                    <!-- Dropdown List -->
                    <div class="absolute top-[76px] left-0 w-56 bg-white rounded-lg shadow-lg border border-stone-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform translate-y-2 group-hover:translate-y-0 cursor-default py-2 z-[60]">
                        <a href="#" class="block px-5 py-2.5 text-stone-600 text-[15px] hover:bg-stone-50 hover:text-accent-500 transition-colors border-b border-stone-100 last:border-none">About us</a>
                        <a href="#" class="block px-5 py-2.5 text-stone-600 text-[15px] hover:bg-stone-50 hover:text-accent-500 transition-colors border-b border-stone-100 last:border-none">Our team</a>
                        <a href="#" class="block px-5 py-2.5 text-stone-600 text-[15px] hover:bg-stone-50 hover:text-accent-500 transition-colors border-b border-stone-100 last:border-none">Our video</a>
                        <a href="#" class="block px-5 py-2.5 text-stone-600 text-[15px] hover:bg-stone-50 hover:text-accent-500 transition-colors border-b border-stone-100 last:border-none">Our process</a>
                        <a href="#" class="block px-5 py-2.5 text-stone-600 text-[15px] hover:bg-stone-50 hover:text-accent-500 transition-colors border-b border-stone-100 last:border-none">Sustainability</a>
                    </div>
                </div>

                <!-- Standard Links -->
                <a href="#" class="flex items-center h-full text-stone-600 hover:text-accent-500 text-[17px] font-medium transition-colors">Blog</a>
                <a href="#" class="flex items-center h-full text-stone-600 hover:text-accent-500 text-[17px] font-medium transition-colors">Contact</a>

            </nav>

            <!-- Actions Right Side -->
            <div class="flex items-center gap-5 shrink-0">
                
                <!-- Language Selector -->
                <div class="hidden lg:flex items-center gap-1 cursor-pointer group">
                    <span class="text-stone-600 text-[15px] group-hover:text-accent-500 transition-colors">English</span>
                    <svg class="w-4 h-4 text-stone-600 group-hover:text-accent-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>

                <!-- Divider -->
                <div class="w-px h-6 bg-stone-300 opacity-60 hidden lg:block"></div>

                <!-- Search Icon -->
                <button class="text-stone-700 hover:text-accent-500 transition-colors focus:outline-none">
                    <svg class="w-[22px] h-[22px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>

                <!-- Shopping Cart -->
                <div class="relative cursor-pointer group hover:-translate-y-0.5 transition-transform">
                    <button class="text-stone-700 hover:text-accent-500 transition-colors focus:outline-none">
                        <svg class="w-[26px] h-[26px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </button>
                    <!-- Enhanced Badge based on Figma -->
                    <div class="absolute -top-1 -right-2 bg-accent-500 flex flex-col items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-white text-[10px] font-bold shadow-sm shadow-accent-500/50">
                        01
                    </div>
                </div>

                <!-- User Profile -->
                <div class="hidden lg:flex items-center gap-2 cursor-pointer group hover:bg-stone-50 px-2 py-1 rounded-full transition-colors">
                    <div class="w-[34px] h-[34px] bg-stone-800 rounded-full flex flex-col justify-center items-center text-accent-500 text-[16px] font-bold shadow-inner">
                        <img src="https://ui-avatars.com/api/?name=Tran+Mai&background=333&color=E67258&bold=true" class="w-full h-full rounded-full" alt="Avatar">
                    </div>
                    <span class="text-stone-700 text-[15px] font-medium leading-none mb-[2px]">Trần Mai</span>
                    <svg class="w-4 h-4 text-stone-500 group-hover:text-accent-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
                
                <!-- Mobile Menu Button -->
                <div class="flex items-center lg:hidden">
                    <button type="button" class="text-stone-700 hover:text-accent-500 p-2 focus:outline-none">
                        <svg class="block h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                </div>

            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main id="primary" class="site-main flex-grow bg-stone-50">
