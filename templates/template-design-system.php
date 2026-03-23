<?php
/*
Template Name: Design System Preview
*/
get_header();

$brand_colors = [
    'Primary' => [
        ['name' => '900', 'hex' => '#3d2f26', 'class' => 'bg-primary-900', 'text' => 'text-white'],
        ['name' => '800', 'hex' => '#4d3d32', 'class' => 'bg-primary-800', 'text' => 'text-white'],
        ['name' => '700', 'hex' => '#6b5344', 'class' => 'bg-primary-700', 'text' => 'text-white'],
        ['name' => '600', 'hex' => '#8d6a54', 'class' => 'bg-primary-600', 'text' => 'text-white'],
        ['name' => '500', 'hex' => '#a9846b', 'class' => 'bg-primary-500', 'text' => 'text-white'],
        ['name' => '400', 'hex' => '#c0a28e', 'class' => 'bg-primary-400', 'text' => 'text-gray-900'],
        ['name' => '300', 'hex' => '#d3bfb1', 'class' => 'bg-primary-300', 'text' => 'text-gray-900'],
        ['name' => '200', 'hex' => '#ded5cd', 'class' => 'bg-primary-200', 'text' => 'text-gray-900'],
        ['name' => '100', 'hex' => '#e9e1d9', 'class' => 'bg-primary-100', 'text' => 'text-gray-900'],
    ],
    'Neutral' => [
        ['name' => '300', 'hex' => '#eae3d1', 'class' => 'bg-neutral-300', 'text' => 'text-gray-900'],
        ['name' => '200', 'hex' => '#f1eee1', 'class' => 'bg-neutral-200', 'text' => 'text-gray-900'],
        ['name' => '100', 'hex' => '#f8f7f3', 'class' => 'bg-neutral-100 border border-gray-200', 'text' => 'text-gray-900'],
    ],
    'Accent' => [
        ['name' => '600', 'hex' => '#c8513b', 'class' => 'bg-accent-600', 'text' => 'text-white'],
        ['name' => '500', 'hex' => '#d95f47', 'class' => 'bg-accent-500', 'text' => 'text-white'],
        ['name' => '400', 'hex' => '#e67258', 'class' => 'bg-accent-400', 'text' => 'text-white'],
    ]
];
?>

<main class="min-h-screen bg-gray-50/50 py-20 font-sans selection:bg-primary-500/10 selection:text-primary-800">
    <div class="max-w-[85rem] mx-auto px-4 md:px-6 lg:px-8">
        
        <!-- Header -->
        <header class="mb-20 text-center relative z-10 rounded-[3rem] bg-white p-12 md:p-20 shadow-sm border border-gray-100 overflow-hidden">
            <div class="absolute inset-0 -z-10 flex items-center justify-center pointer-events-none opacity-30">
                <div class="w-[40rem] h-[40rem] bg-gradient-to-tr from-primary-200 via-transparent to-accent-100 rounded-full blur-[100px]"></div>
            </div>
            
            <h1 class="text-h1 tracking-tight text-gray-900 sm:text-5xl md:text-7xl mb-6 leading-none">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-accent-500 font-bold">Bacera</span><br/>Design System
            </h1>
            <p class="text-body-reg md:text-[20px] text-gray-500 max-w-3xl mx-auto font-medium">
                Hệ thống thư viện UI Component hoàn chỉnh được phân rã cấu trúc theo mô hình Atomic Design, sẵn sàng tái sử dụng cho mọi Layout của bạn.
            </p>
        </header>

        <div class="space-y-16">

            <!-- ============================================== -->
            <!-- 1. FOUNDATIONS                                 -->
            <!-- ============================================== -->
            <div class="flex items-center gap-6 py-4 mt-8">
                <h2 class="text-4xl font-black text-gray-900 tracking-tight shrink-0">1. Foundations</h2>
                <div class="h-1 flex-1 bg-gray-200 rounded-full"></div>
            </div>

            <!-- Typography Section -->
            <section class="bg-white rounded-[2rem] p-8 md:p-12 shadow-sm border border-gray-100">
                <div class="mb-10">
                    <h3 class="text-h2 font-bold text-gray-900">Typography</h3>
                    <p class="text-body-small text-gray-500 mt-2">Bricolage Grotesque Font Scale</p>
                </div>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-baseline border-b border-gray-50 pb-8 hover:bg-gray-50/80 p-6 rounded-2xl transition-all">
                        <div class="col-span-1 text-[11px] text-gray-400 font-bold tracking-widest uppercase">Heading / H1</div>
                        <div class="col-span-3">
                            <h1 class="text-h1 text-gray-900">Building better digital experiences</h1>
                            <p class="text-body-small text-gray-400 mt-3 font-mono">28px / 29px / -3% LS</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-baseline border-b border-gray-50 pb-8 hover:bg-gray-50/80 p-6 rounded-2xl transition-all">
                        <div class="col-span-1 text-[11px] text-gray-400 font-bold tracking-widest uppercase">Heading / H2</div>
                        <div class="col-span-3">
                            <h2 class="text-h2 text-gray-900">Designing for the future</h2>
                            <p class="text-body-small text-gray-400 mt-3 font-mono">22px / 24px / -3% LS</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-baseline border-b border-gray-50 pb-8 hover:bg-gray-50/80 p-6 rounded-2xl transition-all">
                        <div class="col-span-1 text-[11px] text-gray-400 font-bold tracking-widest uppercase">Heading / H3</div>
                        <div class="col-span-3">
                            <h3 class="text-h3 text-gray-900">Essential design principles</h3>
                            <p class="text-body-small text-gray-400 mt-3 font-mono">18px / 21px / -3% LS</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-baseline border-b border-gray-50 pb-8 hover:bg-gray-50/80 p-6 rounded-2xl transition-all">
                        <div class="col-span-1 text-[11px] text-gray-400 font-bold tracking-widest uppercase">Body / Regular</div>
                        <div class="col-span-3">
                            <p class="text-body-reg text-gray-600">Typography is the foundation of any good design system. The quick brown fox jumps over the lazy dog. A cohesive system starts with great typography.</p>
                            <p class="text-body-small text-gray-400 mt-3 font-mono">18px / 22px / 0% LS</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-baseline hover:bg-gray-50/80 p-6 rounded-2xl transition-all">
                        <div class="col-span-1 text-[11px] text-gray-400 font-bold tracking-widest uppercase">Body / Small</div>
                        <div class="col-span-3">
                            <p class="text-body-small text-gray-500">Perfect for fine print, captions, tooltips, and secondary info.</p>
                            <p class="text-body-small text-gray-400 mt-3 font-mono">15px / 14px / -2% LS</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Colors Section -->
            <section class="bg-white rounded-[2rem] p-8 md:p-12 shadow-sm border border-gray-100 relative">
                <div class="mb-10">
                    <h3 class="text-h2 font-bold text-gray-900">Colors</h3>
                    <p class="text-body-small text-gray-500 mt-2">Brand Palette Scale. Click any square to copy HEX code.</p>
                </div>
                
                <div class="space-y-8">
                    <?php foreach ($brand_colors as $group_name => $shades): ?>
                    <div class="flex flex-col md:flex-row md:items-start md:items-center gap-6 border-b border-gray-100 last:border-0 pb-8 p-4 -ml-4 rounded-xl">
                        <div class="w-32 shrink-0">
                            <h4 class="text-body-reg font-black text-gray-900 tracking-tight"><?php echo $group_name; ?></h4>
                        </div>
                        <div class="flex flex-wrap gap-3 md:gap-4">
                            <?php foreach ($shades as $shade): ?>
                            <button onclick="copyColorToClipboard('<?php echo $shade['hex']; ?>')" class="w-16 md:w-20 group relative flex flex-col items-center gap-2 focus:outline-none transition-all cursor-pointer" title="Copy <?php echo $shade['hex']; ?>">
                                <div class="w-full h-16 md:h-20 <?php echo $shade['class']; ?> shadow-inner rounded-[1rem] group-hover:scale-110 group-active:scale-95 transition-all duration-300 flex items-center justify-center ring-offset-2 focus-within:ring-2 focus-within:ring-gray-900">
                                    <span class="opacity-0 group-hover:opacity-100 font-mono text-[10px] md:text-xs <?php echo $shade['text']; ?> font-bold tracking-wider">COPY</span>
                                </div>
                                <div class="text-center mt-2">
                                    <p class="text-[12px] md:text-[13px] font-black text-gray-900"><?php echo $shade['name']; ?></p>
                                    <p class="text-[10px] md:text-[11px] font-mono text-gray-400 font-semibold uppercase"><?php echo $shade['hex']; ?></p>
                                </div>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- ============================================== -->
            <!-- 2. MOLECULES                                   -->
            <!-- ============================================== -->
            <div class="flex items-center gap-6 py-4 mt-24">
                <h2 class="text-4xl font-black text-gray-900 tracking-tight shrink-0">2. Molecules</h2>
                <div class="h-1 flex-1 bg-gray-200 rounded-full"></div>
            </div>

            <!-- Buttons & Badges -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-stretch">
                <!-- Buttons -->
                <section class="bg-white rounded-[2rem] p-8 md:p-10 shadow-sm border border-gray-100 flex flex-col h-full">
                    <h3 class="text-h2 font-bold text-gray-900 mb-8">Buttons</h3>
                    <div class="flex flex-wrap gap-4 items-center p-8 bg-gray-50 rounded-2xl mb-4 border border-gray-100/50">
                        <?php get_template_part('app/Views/components/button', null, ['text' => 'Primary Button', 'type' => 'primary']); ?>
                        <?php get_template_part('app/Views/components/button', null, ['text' => 'Accent Button', 'type' => 'accent']); ?>
                    </div>
                    <div class="flex flex-wrap gap-4 items-center p-8 bg-white border border-gray-200 shadow-sm rounded-2xl">
                        <?php get_template_part('app/Views/components/button', null, ['text' => 'Outline Button', 'type' => 'outline']); ?>
                        <?php get_template_part('app/Views/components/button', null, ['text' => 'Ghost Button', 'type' => 'ghost']); ?>
                    </div>
                </section>

                <!-- Badges & Alerts -->
                <div class="flex flex-col gap-8 h-full">
                    <section class="bg-white rounded-[2rem] p-8 md:p-10 shadow-sm border border-gray-100 flex-1">
                        <h3 class="text-h2 font-bold text-gray-900 mb-8">Badges & Tags</h3>
                        <div class="flex flex-wrap gap-3 items-center">
                            <?php get_template_part('app/Views/components/badge', null, ['text' => 'Brand Tag', 'type' => 'primary']); ?>
                            <?php get_template_part('app/Views/components/badge', null, ['text' => 'Hot Seller', 'type' => 'accent']); ?>
                            <?php get_template_part('app/Views/components/badge', null, ['text' => 'Draft Note', 'type' => 'neutral']); ?>
                            <?php get_template_part('app/Views/components/badge', null, ['text' => 'Completed', 'type' => 'success']); ?>
                        </div>
                    </section>
                    
                    <section class="bg-white rounded-[2rem] p-8 md:p-10 shadow-sm border border-gray-100 flex-1">
                        <h3 class="text-h2 font-bold text-gray-900 mb-8">Toasts / Alerts</h3>
                        <div class="space-y-4">
                            <?php get_template_part('app/Views/components/alert', null, ['type' => 'success', 'title' => 'Thành công', 'message' => 'Thao tác hoàn tất gọn gàng.']); ?>
                            <?php get_template_part('app/Views/components/alert', null, ['type' => 'error', 'title' => 'Lỗi kết nối', 'message' => 'Lòng lòng điền đủ các trường bắt buộc.']); ?>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Form Controls -->
            <section class="bg-white rounded-[2rem] p-8 md:p-10 shadow-sm border border-gray-100">
                <h3 class="text-h2 font-bold text-gray-900 mb-8">Form Controls</h3>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                    <?php get_template_part('app/Views/components/input', null, ['label' => 'Input Text Field', 'placeholder' => 'Ví dụ: Họ và tên của bạn']); ?>
                    <?php get_template_part('app/Views/components/select', null, ['label' => 'Select / Dropdown']); ?>
                    <?php get_template_part('app/Views/components/datepicker', null, ['label' => 'Date / Time Picker']); ?>
                </div>
            </section>

            <!-- Navigation Controls -->
            <section class="bg-white rounded-[2rem] p-8 md:p-10 shadow-sm border border-gray-100">
                <div class="mb-12">
                    <h3 class="text-h2 font-bold text-gray-900 mb-4">Tabs</h3>
                    <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100">
                        <?php get_template_part('app/Views/components/tabs'); ?>
                    </div>
                </div>
                <div class="">
                    <h3 class="text-h2 font-bold text-gray-900 mb-4">Pagination</h3>
                    <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100">
                        <?php get_template_part('app/Views/components/pagination'); ?>
                    </div>
                </div>
            </section>

            <!-- ============================================== -->
            <!-- 3. ORGANISMS                                   -->
            <!-- ============================================== -->
            <div class="flex items-center gap-6 py-4 mt-24">
                <h2 class="text-4xl font-black text-gray-900 tracking-tight shrink-0">3. Organisms</h2>
                <div class="h-1 flex-1 bg-gray-200 rounded-full"></div>
            </div>

            <!-- Main Cards -->
            <section class="bg-white rounded-[2rem] p-8 md:p-12 shadow-sm border border-gray-100">
            <!-- Workshop Card Section -->
            <section class="bg-white rounded-[2rem] p-8 md:p-12 shadow-sm border border-gray-100">
                <div class="flex items-center gap-3 mb-8 border-b border-gray-100 pb-8">
                    <span class="w-3 h-3 rounded-full bg-accent-500"></span>
                    <h3 class="text-h2 font-bold text-gray-900 border-none mb-0">Workshop Card</h3>
                </div>
                <div class="max-w-6xl mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-[30px]">
                    <?php get_template_part('app/Views/components/workshop-card'); ?>
                    <div class="hidden sm:block">
                        <?php get_template_part('app/Views/components/workshop-card', null, ['title' => 'Hand-building Pottery', 'description' => 'Shape, carve, and create your own ceramic piece by hand.']); ?>
                    </div>
                    <div class="hidden lg:block">
                        <?php get_template_part('app/Views/components/workshop-card', null, ['title' => 'Team-building Pottery', 'pricing' => 'Contact us']); ?>
                    </div>
                </div>
            </section>

            <!-- Product Card Section -->
            <section class="bg-white rounded-[2rem] p-8 md:p-12 shadow-sm border border-gray-100">
                <div class="flex items-center gap-3 mb-8 border-b border-gray-100 pb-8">
                    <span class="w-3 h-3 rounded-full bg-primary-600"></span>
                    <h3 class="text-h2 font-bold text-gray-900 border-none mb-0">Product Card</h3>
                </div>
                <div class="max-w-5xl mx-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-[32px]">
                    <?php get_template_part('app/Views/components/product-card'); ?>
                    <div class="hidden sm:block">
                        <?php get_template_part('app/Views/components/product-card', null, ['discount' => '-50%']); ?>
                    </div>
                    <div class="hidden md:block">
                        <?php get_template_part('app/Views/components/product-card', null, ['discount' => false]); ?>
                    </div>
                </div>
            </section>

            <!-- Blog Card -->
            <section class="bg-white rounded-[2rem] p-8 md:p-12 shadow-sm border border-gray-100">
                <div class="flex items-center gap-3 mb-8 border-b border-gray-100 pb-8">
                    <span class="w-3 h-3 rounded-full bg-neutral-300"></span>
                    <h3 class="text-h2 font-bold text-gray-900 border-none pb-0 mb-0">Blog Card</h3>
                </div>
                <div class="max-w-6xl mx-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-[32px]">
                    <?php get_template_part('app/Views/components/blog-card'); ?>
                    <div class="hidden sm:block">
                        <?php get_template_part('app/Views/components/blog-card'); ?>
                    </div>
                    <div class="hidden md:block">
                        <?php get_template_part('app/Views/components/blog-card'); ?>
                    </div>
                </div>
            </section>

            <!-- Separated Collection Cards Section (Bento Grid) -->
            <section class="bg-white rounded-[2rem] p-8 md:p-12 shadow-sm border border-gray-100">
                <div class="mb-10 border-b border-gray-100 pb-8">
                    <h3 class="text-h2 font-bold text-gray-900 mb-2 flex items-center gap-3">
                        <span class="w-3 h-3 rounded-full bg-gray-900"></span> Collection Gallery (Bento Grid)
                    </h3>
                    <p class="text-body-small text-gray-500">
                        Hệ thống thẻ linh hoạt theo Layout thực tế (Tái hiện đúng Specs JSX gốc: Hỗ trợ linh hoạt 16:9, 4:3, 3:4). Tự động co giãn 100% cột chứa, gradient chuẩn 253deg và khoảng cách nội dung y hệt thiết kế.
                    </p>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 w-full">
                    <!-- Banner Full (16:9 => w-[1232px] in JSX specs) -->
                    <div class="lg:col-span-12">
                        <?php get_template_part('app/Views/components/collection-card', null, [
                            'ratio' => '16/9', 
                            'image' => 'https://placehold.co/1200x600/d95f47/111827?text=Hero+Banner'
                        ]); ?>
                    </div>
                    
                    <!-- 3:4 Portrait (w-[452px] in JSX specs) -->
                    <div class="lg:col-span-4 h-full">
                        <?php get_template_part('app/Views/components/collection-card', null, [
                            'ratio' => 'auto',
                            'class' => 'h-[100%] aspect-auto',
                            'title' => 'Earthy Textures',
                            'description' => 'Rough edges and raw finishes.',
                            'image' => 'https://placehold.co/600x800/a9846b/111827?text=Portrait'
                        ]); ?>
                    </div>

                    <!-- 4:3 (w-[764px] / w-[608px] in JSX specs) -->
                    <div class="lg:col-span-8 flex flex-col gap-6">
                        <?php get_template_part('app/Views/components/collection-card', null, [
                            'ratio' => '4/3', 
                            'title' => 'Modern Minimalist',
                            'description' => 'Clean lines for modern homes.',
                            'image' => 'https://placehold.co/800x600/eae3d1/111827?text=Landscape'
                        ]); ?>
                        
                        <!-- Demonstrating 2 smaller 4:3 cards splitting the grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 flex-grow">
                            <?php get_template_part('app/Views/components/collection-card', null, [
                                'ratio' => '4/3', 
                                'title' => 'Ceramic Mugs',
                                'description' => 'Everyday companions.',
                            ]); ?>
                            <?php get_template_part('app/Views/components/collection-card', null, [
                                'ratio' => '4/3', 
                                'title' => 'Handmade Plates',
                                'description' => 'For beautiful dinners.',
                                'image' => 'https://placehold.co/400x300/3d2f26/eae3d1?text=Plates'
                            ]); ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Modals Section -->
            <section class="bg-white rounded-[2rem] p-8 md:p-12 shadow-sm border border-gray-100">
                <div class="flex items-center gap-3 mb-8">
                    <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                    <h4 class="text-[16px] font-bold text-gray-900 uppercase tracking-widest text-body-reg">Modals & Popups</h4>
                </div>
                <div class="relative h-[650px] w-full border border-gray-200 rounded-[2rem] overflow-hidden bg-[url('https://placehold.co/1200x800/f8f7f3/d95f47?text=Background')] bg-cover bg-center flex items-center justify-center shadow-inner">
                    <?php get_template_part('app/Views/components/modal'); ?>
                </div>
            </section>

        </div>
    </div>

    <!-- Toast Component for JS -->
    <div id="copy-toast" class="fixed bottom-10 left-1/2 -translate-x-1/2 bg-gray-900 text-white px-6 py-4 rounded-[1.5rem] shadow-2xl shadow-gray-900/40 text-[15px] font-medium z-50 flex items-center gap-3 transition-all duration-400 opacity-0 translate-y-8 pointer-events-none">
        <svg class="w-6 h-6 text-accent-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
        <span>Copied <span id="toast-hex" class="font-mono text-accent-400 font-bold ml-1"></span></span>
    </div>

    <script>
    function copyColorToClipboard(hex) {
        navigator.clipboard.writeText(hex).then(() => {
            const toast = document.getElementById('copy-toast');
            const toastHex = document.getElementById('toast-hex');
            toastHex.textContent = hex;
            toast.classList.remove('opacity-0', 'translate-y-8');
            toast.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-8');
                toast.classList.remove('opacity-100', 'translate-y-0');
            }, 3000);
        }).catch(err => {
            console.error('Failed to copy text: ', err);
        });
    }
    </script>
</main>

<?php get_footer(); ?>
