<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Maintenance - BSCCL Maintenance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-blue-100 to-green-100">
    <?php include '../app/views/includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6 animate-fadeIn max-w-2xl mx-auto">
            <div class="mb-6">
                <a href="/maintenance" class="text-blue-500 hover:text-blue-700">
                    <i class="fas fa-arrow-left"></i> Back to Maintenance List
                </a>
            </div>

            <h2 class="text-2xl font-bold text-gray-800 mb-6">Edit Maintenance</h2>

            <form action="/maintenance/edit" method="POST" class="space-y-4">
                <input type="hidden" name="id" value="<?php echo $data['maintenance']->id; ?>">
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                        Maintenance Title
                    </label>
                    <input type="text" name="title" id="title" 
                           value="<?php echo htmlspecialchars($data['maintenance']->title); ?>"
                           required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="start_time">
                            Start Time (UTC)
                        </label>
                        <input type="datetime-local" 
                               name="start_time" 
                               id="start_time" 
                               value="<?php echo date('Y-m-d\TH:i', strtotime($data['maintenance']->start_time)); ?>"
                               required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="end_time">
                            End Time (UTC)
                        </label>
                        <input type="datetime-local" 
                               name="end_time" 
                               id="end_time" 
                               value="<?php echo date('Y-m-d\TH:i', strtotime($data['maintenance']->end_time)); ?>"
                               required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="/maintenance" 
                       class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:from-blue-600 hover:to-green-600 transition duration-300">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out;
        }
    </style>

    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
</body>
</html>