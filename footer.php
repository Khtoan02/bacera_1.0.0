<?php
/**
 * Footer
 */
?>
    </main> <!-- End #primary .site-main -->

    <!-- Footer -->
    <footer id="colophon" class="site-footer bg-gray-900 border-t border-gray-800 pt-16 pb-8 text-gray-300 mt-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                
                <!-- Brand Info -->
                <div class="col-span-1 md:col-span-1">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-2xl font-bold text-white mb-4 block bg-clip-text text-transparent bg-gradient-to-r from-primary to-secondary">
                        <?php bloginfo( 'name' ); ?>
                    </a>
                    <p class="text-sm text-gray-400 leading-relaxed mb-6">
                        Khởi tạo cấu trúc Web hiện đại với chuẩn MVC. Không gì là không thể.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
                            <span class="sr-only">Facebook</span>
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd"/></svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
                            <span class="sr-only">Twitter</span>
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"/></svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
                            <span class="sr-only">LinkedIn</span>
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" clip-rule="evenodd"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Links 1 -->
                <div>
                    <h3 class="text-sm font-semibold text-white tracking-wider uppercase mb-5">Danh Mục</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 text-sm hover:text-primary transition-colors duration-300">Giải pháp Doanh nghiệp</a></li>
                        <li><a href="#" class="text-gray-400 text-sm hover:text-primary transition-colors duration-300">Thiết kế Web/App</a></li>
                        <li><a href="#" class="text-gray-400 text-sm hover:text-primary transition-colors duration-300">Tối ưu hiệu suất</a></li>
                        <li><a href="#" class="text-gray-400 text-sm hover:text-primary transition-colors duration-300">Tích hợp ERP</a></li>
                    </ul>
                </div>

                <!-- Links 2 -->
                <div>
                    <h3 class="text-sm font-semibold text-white tracking-wider uppercase mb-5">Thông Tin</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 text-sm hover:text-primary transition-colors duration-300">Câu chuyện của chúng tôi</a></li>
                        <li><a href="#" class="text-gray-400 text-sm hover:text-primary transition-colors duration-300">Quy trình làm việc</a></li>
                        <li><a href="#" class="text-gray-400 text-sm hover:text-primary transition-colors duration-300">Blog kiến thức</a></li>
                        <li><a href="#" class="text-gray-400 text-sm hover:text-primary transition-colors duration-300">Tuyển dụng</a></li>
                    </ul>
                </div>

                <!-- Newsletter -->
                <div>
                    <h3 class="text-sm font-semibold text-white tracking-wider uppercase mb-5">Đăng Ký Bản Tin</h3>
                    <p class="text-sm text-gray-400 mb-4">Các mẹo tối ưu và thông tin công nghệ mới nhất sẽ được gửi hàng tuần.</p>
                    <form class="flex max-w-sm">
                        <input type="email" placeholder="Email của bạn..." class="min-w-0 flex-auto rounded-l-md border-0 bg-white/5 px-3 py-2 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6">
                        <button type="submit" class="flex-none rounded-r-md bg-primary px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-colors">Gửi</button>
                    </form>
                </div>
            </div>

            <!-- Copyright -->
            <div class="mt-8 border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-sm text-gray-500">
                    &copy; <?php echo date('Y'); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.
                </p>
                <div class="mt-4 md:mt-0 flex space-x-6">
                    <a href="#" class="text-sm text-gray-500 hover:text-white transition-colors duration-300">Chính sách bảo mật</a>
                    <a href="#" class="text-sm text-gray-500 hover:text-white transition-colors duration-300">Điều khoản dịch vụ</a>
                </div>
            </div>
        </div>
    </footer>
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
