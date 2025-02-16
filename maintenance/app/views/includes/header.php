<?php
if (!isset($_SESSION)) {
    session_start();
}
?>
<nav class="bg-white shadow-lg">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <a href="/" class="text-xl font-bold text-gray-800 hover:text-gray-700">
                    BSCCL Maintenance
                </a>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="/upload" class="px-3 py-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-100">
                        Upload Data
                    </a>
                    <a href="/maintenance" class="px-3 py-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-100">
                        Maintenance Info
                    </a>
                    <div class="relative group">
                        <button class="flex items-center px-3 py-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-100">
                            <span class="mr-2"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 hidden group-hover:block">
                            <a href="/logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Logout
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button class="mobile-menu-button p-2 rounded-md hover:bg-gray-100">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Mobile menu -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="md:hidden hidden mobile-menu">
                <div class="px-2 pt-2 pb-3 space-y-1">
                    <a href="/upload" class="block px-3 py-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-100">
                        Upload Data
                    </a>
                    <a href="/maintenance" class="block px-3 py-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-100">
                        Maintenance Info
                    </a>
                    <a href="/logout" class="block px-3 py-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-100">
                        Logout
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</nav>

<script>
    // Mobile menu toggle
    document.querySelector('.mobile-menu-button')?.addEventListener('click', function() {
        document.querySelector('.mobile-menu').classList.toggle('hidden');
    });
</script>