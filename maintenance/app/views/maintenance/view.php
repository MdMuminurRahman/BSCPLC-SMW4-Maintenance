<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Details - BSCCL Maintenance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-blue-100 to-green-100">
    <?php include '../app/views/includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6 animate-fadeIn">
            <div class="mb-6">
                <a href="/maintenance" class="text-blue-500 hover:text-blue-700">
                    <i class="fas fa-arrow-left"></i> Back to Maintenance List
                </a>
            </div>

            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($data['maintenance']->title); ?></h2>
                <div class="mt-2 text-gray-600">
                    <p>Start Time (UTC): <?php echo date('Y-m-d H:i', strtotime($data['maintenance']->start_time)); ?></p>
                    <p>End Time (UTC): <?php echo date('Y-m-d H:i', strtotime($data['maintenance']->end_time)); ?></p>
                </div>
            </div>

            <div class="mb-4">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Affected Circuits</h3>
                <?php if (empty($data['circuits'])): ?>
                    <p class="text-gray-600">No affected circuits found.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full table-auto" id="circuitsTable">
                            <thead class="bg-gradient-to-r from-blue-500 to-green-500 text-white">
                                <tr>
                                    <th class="p-3 text-left">SL No</th>
                                    <th class="p-3 text-left">Circuit ID</th>
                                    <th class="p-3 text-left">Bandwidth</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $slNo = 1; foreach ($data['circuits'] as $circuit): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="p-3"><?php echo $slNo++; ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($circuit->circuit_id); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($circuit->bandwidth); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button onclick="copyToClipboard()" 
                                class="px-4 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:from-blue-600 hover:to-green-600 transition duration-300">
                            Copy to Clipboard
                        </button>
                    </div>
                <?php endif; ?>
            </div>
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

    <script>
        function copyToClipboard() {
            const table = document.getElementById('circuitsTable');
            const rows = Array.from(table.rows).slice(1); // Skip header row
            
            let text = '';
            rows.forEach(row => {
                const cells = Array.from(row.cells);
                text += cells.map(cell => cell.textContent.trim()).join('\t') + '\n';
            });

            navigator.clipboard.writeText(text).then(() => {
                alert('Table data copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy: ', err);
                alert('Failed to copy to clipboard');
            });
        }
    </script>

    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
</body>
</html>