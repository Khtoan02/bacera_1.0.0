<?php
/*
Template Name: Design System Preview
*/
get_header(); ?>

<main class="min-h-screen bg-gray-50/50 py-20 font-sans selection:bg-primary/10 selection:text-primary">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <!-- Header -->
        <header class="mb-20 text-center relative z-10">
            <div class="absolute inset-0 -z-10 flex items-center justify-center pointer-events-none opacity-20">
                <div class="w-[30rem] h-[30rem] bg-gradient-to-tr from-primary to-accent rounded-full blur-[100px] animate-pulse"></div>
            </div>
            
            <h1 class="text-h1 tracking-tight text-gray-900 sm:text-5xl md:text-6xl mb-6">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-accent font-bold">Bacera</span> Design System
            </h1>
            <p class="text-body-reg text-gray-600 max-w-2xl mx-auto">
                A unified design language and component library for your next-generation WordPress theme. View typography, colors, and building blocks dynamically.
            </p>
        </header>

        <div class="space-y-20">
            <!-- Typography Section -->
            <section class="bg-white rounded-[2rem] p-10 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 overflow-hidden relative">
                <!-- abstract decoration -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full blur-3xl -mr-10 -mt-20"></div>

                <div class="border-b border-gray-100 pb-5 mb-10 relative z-10">
                    <h2 class="text-h2 font-semibold text-gray-900">Typography</h2>
                    <p class="text-body-small text-gray-500 mt-2">Bricolage Grotesque Font Family Scale</p>
                </div>
                
                <div class="space-y-4 relative z-10">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-baseline border-b border-gray-50 pb-8 hover:bg-gray-50/80 p-6 rounded-2xl transition-all duration-300">
                        <div class="col-span-1 text-body-small text-gray-400 font-medium tracking-wide uppercase">Heading / H1</div>
                        <div class="col-span-3">
                            <h1 class="text-h1 text-gray-900">Building better digital experiences</h1>
                            <p class="text-body-small text-gray-400 mt-3 font-mono">28px / 29px / -3% LS</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-baseline border-b border-gray-50 pb-8 hover:bg-gray-50/80 p-6 rounded-2xl transition-all duration-300">
                        <div class="col-span-1 text-body-small text-gray-400 font-medium tracking-wide uppercase">Heading / H2</div>
                        <div class="col-span-3">
                            <h2 class="text-h2 text-gray-900">Designing for the future</h2>
                            <p class="text-body-small text-gray-400 mt-3 font-mono">22px / 24px / -3% LS</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-baseline border-b border-gray-50 pb-8 hover:bg-gray-50/80 p-6 rounded-2xl transition-all duration-300">
                        <div class="col-span-1 text-body-small text-gray-400 font-medium tracking-wide uppercase">Heading / H3</div>
                        <div class="col-span-3">
                            <h3 class="text-h3 text-gray-800">Essential design principles</h3>
                            <p class="text-body-small text-gray-400 mt-3 font-mono">18px / 21px / -3% LS</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-baseline border-b border-gray-50 pb-8 hover:bg-gray-50/80 p-6 rounded-2xl transition-all duration-300">
                        <div class="col-span-1 text-body-small text-gray-400 font-medium tracking-wide uppercase">Body / Regular</div>
                        <div class="col-span-3">
                            <p class="text-body-reg text-gray-600">Typography is the foundation of any good design system. The quick brown fox jumps over the lazy dog. A cohesive system starts with great typography, ensuring readability across all devices and screen sizes.</p>
                            <p class="text-body-small text-gray-400 mt-3 font-mono">18px / 22px / 0% LS</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-baseline border-b border-gray-50 pb-8 hover:bg-gray-50/80 p-6 rounded-2xl transition-all duration-300">
                        <div class="col-span-1 text-body-small text-gray-400 font-medium tracking-wide uppercase">Body / Small</div>
                        <div class="col-span-3">
                            <p class="text-body-small text-gray-500">Perfect for fine print, captions, tooltips, and secondary metadata information within complex interfaces.</p>
                            <p class="text-body-small text-gray-400 mt-3 font-mono">15px / 14px / -2% LS</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-baseline hover:bg-gray-50/80 p-6 rounded-2xl transition-all duration-300">
                        <div class="col-span-1 text-body-small text-gray-400 font-medium tracking-wide uppercase">Nav / Link</div>
                        <div class="col-span-3">
                            <a href="#" class="inline-flex text-nav-link text-primary hover:text-accent hover:underline underline-offset-4 decoration-2 transition-all">Explore our components &rarr;</a>
                            <p class="text-body-small text-gray-400 mt-3 font-mono">15px / auto / 0% LS</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Colors Section -->
            <?php
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
            <section class="bg-white rounded-[2rem] p-10 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 relative">
                <div class="border-b border-gray-100 pb-5 mb-10">
                    <h2 class="text-h2 font-semibold text-gray-900">Color</h2>
                    <p class="text-body-small text-gray-500 mt-2">Brand Palette Scale. Click any color to copy its Hex code.</p>
                </div>
                
                <div class="space-y-8 relative z-10">
                    <?php foreach ($brand_colors as $group_name => $shades): ?>
                    <!-- <?php echo $group_name; ?> Row -->
                    <div class="flex flex-col md:flex-row md:items-start md:items-center gap-6 border-b border-gray-100 last:border-0 pb-8 hover:bg-gray-50/50 p-4 -ml-4 rounded-xl transition-colors">
                        <div class="w-32 shrink-0">
                            <h3 class="text-body-reg font-bold text-gray-900"><?php echo $group_name; ?></h3>
                        </div>
                        <div class="flex flex-wrap gap-3 sm:gap-4">
                            <?php foreach ($shades as $shade): ?>
                            <button 
                                onclick="copyColorToClipboard('<?php echo $shade['hex']; ?>')"
                                class="w-16 sm:w-20 group relative flex flex-col items-center gap-2 focus:outline-none transition-all cursor-pointer"
                                title="Click to copy <?php echo $shade['hex']; ?>"
                            >
                                <div class="w-full h-16 sm:h-20 <?php echo $shade['class']; ?> shadow-inner rounded-xl group-hover:scale-110 group-active:scale-95 transition-all duration-300 flex items-center justify-center ring-offset-2 focus-within:ring-2 focus-within:ring-primary">
                                    <span class="opacity-0 group-hover:opacity-100 transition-opacity font-mono text-[10px] sm:text-xs <?php echo $shade['text']; ?> font-medium">Copy</span>
                                </div>
                                <div class="text-center mt-1">
                                    <p class="text-[11px] sm:text-xs font-semibold text-gray-900"><?php echo $shade['name']; ?></p>
                                    <p class="text-[10px] sm:text-[11px] font-mono text-gray-400 uppercase"><?php echo $shade['hex']; ?></p>
                                </div>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Toast Notification -->
                <div id="copy-toast" class="fixed bottom-10 left-1/2 -translate-x-1/2 bg-gray-900 text-white px-6 py-3 rounded-2xl shadow-2xl shadow-gray-900/20 text-sm font-medium z-50 flex items-center gap-3 transition-all duration-300 opacity-0 translate-y-4 pointer-events-none">
                    <svg class="w-5 h-5 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    <span>Copied <span id="toast-hex" class="font-mono text-accent"></span></span>
                </div>

                <script>
                function copyColorToClipboard(hex) {
                    navigator.clipboard.writeText(hex).then(() => {
                        const toast = document.getElementById('copy-toast');
                        const toastHex = document.getElementById('toast-hex');
                        toastHex.textContent = hex;
                        
                        // Show toast
                        toast.classList.remove('opacity-0', 'translate-y-4');
                        toast.classList.add('opacity-100', 'translate-y-0');
                        
                        // Hide toast after 2s
                        setTimeout(() => {
                            toast.classList.add('opacity-0', 'translate-y-4');
                            toast.classList.remove('opacity-100', 'translate-y-0');
                        }, 2000);
                    }).catch(err => {
                        console.error('Failed to copy text: ', err);
                    });
                }
                </script>
            </section>

            <!-- Components Preview Section -->
            <section class="bg-white rounded-[2rem] p-10 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100">
                <div class="border-b border-gray-100 pb-5 mb-10 flex items-center justify-between">
                    <div>
                        <h2 class="text-h2 font-semibold text-gray-900">Interactive Components</h2>
                        <p class="text-body-small text-gray-500 mt-2">Buttons, Inputs, and Badges preview</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                    <!-- Buttons Group -->
                    <div>
                        <h3 class="text-h3 text-gray-900 mb-8 flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                            Buttons
                        </h3>
                        <div class="flex flex-col gap-6">
                            <div class="flex flex-wrap gap-4 items-center p-6 bg-gray-50/50 rounded-2xl border border-gray-100">
                                <button class="px-6 py-3 bg-primary text-white rounded-xl text-nav-link hover:bg-primary/90 hover:shadow-lg hover:shadow-primary/30 hover:-translate-y-0.5 transition-all active:scale-95 duration-200">
                                    Primary Action
                                </button>
                                <button class="px-6 py-3 bg-accent text-white rounded-xl text-nav-link hover:bg-accent/90 hover:shadow-lg hover:shadow-accent/30 hover:-translate-y-0.5 transition-all active:scale-95 duration-200">
                                    Accent Action
                                </button>
                            </div>
                            
                            <div class="flex flex-wrap gap-4 items-center p-6 bg-gray-900 rounded-2xl">
                                <button class="px-6 py-3 border border-white/20 text-white rounded-xl text-nav-link hover:border-white hover:bg-white/5 hover:-translate-y-0.5 transition-all active:scale-95 duration-200">
                                    Outline Dark
                                </button>
                                <button class="px-6 py-3 bg-white text-gray-900 rounded-xl text-nav-link hover:bg-gray-50 hover:shadow-lg hover:shadow-white/20 hover:-translate-y-0.5 transition-all active:scale-95 duration-200">
                                    Soft Light
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Layout Components -->
                    <div>
                        <h3 class="text-h3 text-gray-900 mb-8 flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            Cards & Badges
                        </h3>
                        <div class="space-y-6">
                            <!-- Card -->
                            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow duration-300 group cursor-pointer">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-accent/10 text-accent">New Feature</span>
                                </div>
                                <h4 class="text-h3 text-gray-900 mb-2 group-hover:text-primary transition-colors">Component Architecture</h4>
                                <p class="text-body-small text-gray-500">A clean layout structure demonstrating how typography and spacing work together within a container.</p>
                            </div>
                            
                            <!-- Inputs -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-body-small font-medium text-gray-700 mb-2">Input Field</label>
                                    <input type="text" placeholder="Start typing..." class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-body-reg placeholder:text-gray-400">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</main>

<?php get_footer(); ?>
