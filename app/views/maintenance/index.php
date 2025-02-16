<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Records - BSCCL Maintenance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-blue-100 to-green-100">
    <?php include '../app/views/includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6 animate-fadeIn">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Maintenance Records</h2>
                <div class="flex space-x-2">
                    <button id="deleteSelected" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-300 hidden">
                        Delete Selected
                    </button>
                </div>
            </div>

            <?php if (empty($data['records'])): ?>
                <div class="text-center py-8 text-gray-600">
                    No maintenance records found.
                </div>
            <?php else: ?>
                <form id="maintenanceForm" action="/maintenance/delete" method="POST">
                    <div class="overflow-x-auto">
                        <table class="min-w-full table-auto">
                            <thead class="bg-gradient-to-r from-blue-500 to-green-500 text-white">
                                <tr>
                                    <th class="p-3 text-left">
                                        <input type="checkbox" id="selectAll" class="rounded">
                                    </th>
                                    <th class="p-3 text-left">Title</th>
                                    <th class="p-3 text-left">Start Time (UTC)</th>
                                    <th class="p-3 text-left">End Time (UTC)</th>
                                    <th class="p-3 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['records'] as $record): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="p-3">
                                            <input type="checkbox" name="maintenance_ids[]" value="<?php echo $record->id; ?>" class="maintenance-checkbox rounded">
                                        </td>
                                        <td class="p-3"><?php echo htmlspecialchars($record->title); ?></td>
                                        <td class="p-3"><?php echo date('Y-m-d H:i', strtotime($record->start_time)); ?></td>
                                        <td class="p-3"><?php echo date('Y-m-d H:i', strtotime($record->end_time)); ?></td>
                                        <td class="p-3">
                                            <div class="flex space-x-2">
                                                <a href="/maintenance/view?id=<?php echo $record->id; ?>" 
                                                   class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 transition duration-300">
                                                    View Details
                                                </a>
                                                <a href="/maintenance/edit?id=<?php echo $record->id; ?>" 
                                                   class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 transition duration-300">
                                                    Edit
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            <?php endif; ?>
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
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.getElementsByClassName('maintenance-checkbox');
            const deleteButton = document.getElementById('deleteSelected');
            const form = document.getElementById('maintenanceForm');

            // Toggle all checkboxes
            selectAll?.addEventListener('change', function() {
                Array.from(checkboxes).forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });
                updateDeleteButton();
            });

            // Update delete button visibility
            Array.from(checkboxes).forEach(checkbox => {
                checkbox.addEventListener('change', updateDeleteButton);
            });

            function updateDeleteButton() {
                const checkedBoxes = Array.from(checkboxes).filter(cb => cb.checked);
                deleteButton.classList.toggle('hidden', checkedBoxes.length === 0);
            }

            // Confirm delete
            deleteButton?.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete the selected maintenance records?')) {
                    form.submit();
                }
            });
        });
    </script>
</body>
</html>