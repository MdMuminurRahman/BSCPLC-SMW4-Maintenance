<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Data - BSCCL Maintenance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-blue-100 to-green-100">
    <?php include '../app/views/includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="grid md:grid-cols-2 gap-8">
            <!-- Circuit List Upload Section -->
            <div class="bg-white p-6 rounded-lg shadow-lg animate-slideIn">
                <h2 class="text-2xl font-bold mb-4 text-gray-800">Upload Circuit List</h2>
                
                <?php if (isset($data['circuit'])): ?>
                <p class="text-sm text-gray-600 mb-4">
                    Last upload: <?php echo $data['circuit'] ? date('Y-m-d H:i:s', strtotime($data['circuit'])) : 'Never'; ?>
                </p>
                <?php endif; ?>

                <form action="/upload/circuit" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-blue-500 transition-colors">
                        <input type="file" name="circuit_file" accept=".xlsx" required
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div class="text-center">
                            <i class="fas fa-upload text-gray-400 text-3xl mb-2"></i>
                            <p class="text-gray-600">Drop your circuit list file here or click to browse</p>
                            <p class="text-sm text-gray-500">(Only .xlsx files accepted)</p>
                        </div>
                    </div>
                    <button type="submit" 
                            class="w-full py-2 px-4 bg-gradient-to-r from-blue-500 to-green-500 text-white font-semibold rounded-lg shadow-md hover:from-blue-600 hover:to-green-600 transition duration-300">
                        Upload Circuit List
                    </button>
                </form>
            </div>

            <!-- Maintenance List Upload Section -->
            <div class="bg-white p-6 rounded-lg shadow-lg animate-slideIn" style="animation-delay: 0.2s">
                <h2 class="text-2xl font-bold mb-4 text-gray-800">Upload Maintenance List</h2>
                
                <?php if (isset($data['maintenance'])): ?>
                <p class="text-sm text-gray-600 mb-4">
                    Last upload: <?php echo $data['maintenance'] ? date('Y-m-d H:i:s', strtotime($data['maintenance'])) : 'Never'; ?>
                </p>
                <?php endif; ?>

                <form action="/upload/maintenance" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="title">Maintenance Title</label>
                        <input type="text" name="title" id="title" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="start_time">Start Time (UTC)</label>
                            <input type="datetime-local" name="start_time" id="start_time" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="end_time">End Time (UTC)</label>
                            <input type="datetime-local" name="end_time" id="end_time" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-blue-500 transition-colors">
                        <input type="file" name="maintenance_file" accept=".xlsx" required
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div class="text-center">
                            <i class="fas fa-upload text-gray-400 text-3xl mb-2"></i>
                            <p class="text-gray-600">Drop your maintenance list file here or click to browse</p>
                            <p class="text-sm text-gray-500">(Only .xlsx files accepted)</p>
                        </div>
                    </div>

                    <button type="submit" 
                            class="w-full py-2 px-4 bg-gradient-to-r from-blue-500 to-green-500 text-white font-semibold rounded-lg shadow-md hover:from-blue-600 hover:to-green-600 transition duration-300">
                        Upload Maintenance List
                    </button>
                </form>
            </div>
        </div>
    </div>

    <style>
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slideIn {
            animation: slideIn 0.5s ease-out forwards;
        }
    </style>

    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
</body>
</html>