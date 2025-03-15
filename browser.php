<?php
// Define the root directory (starting point)
$rootDir = 'files'; // Change this to your desired starting directory
$currentDir = isset($_GET['dir']) ? $_GET['dir'] : $rootDir;

// Ensure the current directory is within the root directory
if (strpos($currentDir, $rootDir) !== 0) {
    $currentDir = $rootDir;
}

// Get all subdirectories and files in the current directory
$subdirs = array_filter(glob($currentDir . '/*'), 'is_dir');
$files = array_filter(glob($currentDir . '/*'), 'is_file');
$parentDir = dirname($currentDir);

// Password protection
$password = 'Nicolas'; // Change this to your desired password
$unlocked = isset($_COOKIE['unlocked']) && $_COOKIE['unlocked'] === 'true';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Editor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }
        #sidebar {
            width: 20%;
            background: #f4f4f4;
            padding: 10px;
            box-sizing: border-box;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        #content {
            flex: 1;
            padding: 10px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        #editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        #editor-header h2 {
            margin: 0;
        }
					 
							  
							
		 
        textarea {
            width: 100%;
            height: 300px; /* Default height */
            font-family: monospace;
            background: #2d2d2d;
            color: #ccc;
            border: 1px solid #444;
            padding: 10px;
            box-sizing: border-box;
        }
        iframe {
            width: 100%;
            flex: 1;
            border: 1px solid #ccc;
            background: #fff;
        }
        #vertical-separator {
            width: 10px;
            background: #ddd;
            cursor: col-resize;
        }
        #horizontal-separator {
            height: 10px;
            background: #ddd;
            cursor: row-resize;
            margin: 5px 0;
        }
        .button-container {
            margin-bottom: 10px;
            margin-top: 10px; /* Added space above buttons */
        }
        .button-container button {
            padding: 5px 10px;
            margin-right: 5px;
            cursor: pointer;
        }
        .file-item, .directory-item {
            display: flex;
            align-items: center;
        }
        .file-item input[type="checkbox"], .directory-item input[type="checkbox"] {
            margin-right: 5px;
        }
        .directory-item {
            cursor: pointer;
            padding: 5px;
            margin: 2px 0;
            background: #e0e0e0;
            border-radius: 3px;
        }
        .directory-item:hover {
            background: #d0d0d0;
        }
        #upButton {
            margin-bottom: 10px;
            cursor: pointer;
            opacity: <?php echo $currentDir === $rootDir ? '0.5' : '1'; ?>;
            pointer-events: <?php echo $currentDir === $rootDir ? 'none' : 'auto'; ?>;
        }
        .password-form {
            margin-top: auto;
            padding: 10px;
            background: #e0e0e0;
            border-top: 1px solid #ccc;
        }
        .password-form input {
            padding: 5px;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <!-- Sidebar for folder and file selection -->
    <div id="sidebar">
        <!-- Subdirectories -->
        <h3>Subdirectories</h3>
        <button id="upButton" onclick="navigateTo('<?php echo $parentDir; ?>')">Up</button>
        <ul id="directoryList">
																	  
																								   
						   
            <?php foreach ($subdirs as $dir): ?>
                <li class="directory-item">
                    <input type="checkbox" value="<?php echo basename($dir); ?>" onchange="updateDeleteButtonState()">
                    <span onclick="navigateTo('<?php echo $dir; ?>')"><?php echo basename($dir); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Add and Delete Directory buttons -->
        <div class="button-container">
            <button onclick="addSubdirectory()" id="addSubdirectoryButton" <?php echo $unlocked ? '' : 'disabled'; ?>>Add Subdirectory</button>
            <button onclick="deleteSubdirectory()" id="deleteDirectoryButton" disabled>Delete Directory</button>
        </div>

        <!-- Files -->
        <h3>Files</h3>
        <ul id="fileList">
            <?php foreach ($files as $file): ?>
                <li class="file-item">
                    <input type="checkbox" value="<?php echo basename($file); ?>" onchange="updateDeleteButtonState()">
                    <a href="#" onclick="loadFileContent('<?php echo $currentDir; ?>', '<?php echo basename($file); ?>')"><?php echo basename($file); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- New and Delete buttons -->
        <div>
            <button id="newButton" onclick="createNewFile()" <?php echo $unlocked ? '' : 'disabled'; ?>>New</button>
            <button id="deleteButton" onclick="deleteFiles()" disabled>Delete</button>
        </div>

        <!-- Password form -->
        <div class="password-form">
            <?php if (!$unlocked): ?>
                <input type="password" id="passwordInput" placeholder="Enter password">
                <button onclick="unlock()">Unlock</button>
            <?php else: ?>
                <button onclick="lock()">Lock</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Vertical draggable separator -->
    <div id="vertical-separator"></div>

    <!-- Main content area for editing and running files -->
    <div id="content">
        <!-- Editor header -->
        <div id="editor-header">
            <div>
                <h2>Current Directory: <?php echo $currentDir; ?></h2>
                <h2>Current File: <span id="currentFile"></span></h2>
            </div>
																		 
        </div>

        <!-- Editor area -->
        <textarea id="fileContent" disabled></textarea>

        <!-- Buttons -->
        <div class="button-container">
            <button id="copyButton" onclick="copyContent()">Copy</button>
            <button id="saveButton" onclick="saveFile()" disabled>Save</button>
            <button id="runButton" onclick="runFile()">Run</button>
            <button id="runNewWindowButton" onclick="runFileInNewWindow()">Run in New Window</button>
        </div>

        <!-- Horizontal draggable separator -->
        <div id="horizontal-separator"></div>

        <!-- Output frame -->
        <iframe id="outputFrame" src="about:blank"></iframe>
    </div>

    <script>
        // Password protection
        const password = 'Nicolas'; // Change this to your desired password

        // Unlock functionality
        function unlock() {
            const passwordInput = document.getElementById('passwordInput').value;
            if (passwordInput === password) {
                setUnlocked(true);
                updateUI();
                alert('Unlocked!');
            } else {
                alert('Incorrect password.');
            }
        }

        // Lock functionality
        function lock() {
            setUnlocked(false);
            updateUI();
            alert('Locked!');
        }

        // Set unlocked state in a cookie
        function setUnlocked(unlocked) {
            document.cookie = `unlocked=${unlocked}; path=/`;
            location.reload(); // Refresh the page to apply changes
        }

        // Update UI based on unlock state
        function updateUI() {
            const unlocked = document.cookie.includes('unlocked=true');
            document.getElementById('addSubdirectoryButton').disabled = !unlocked;
            document.getElementById('newButton').disabled = !unlocked;
            document.getElementById('saveButton').disabled = !unlocked || !document.querySelector('#fileList a.active');
            document.getElementById('deleteButton').disabled = !unlocked;
            document.getElementById('deleteDirectoryButton').disabled = !unlocked;
        }

        // Copy content to clipboard
        function copyContent() {
            const fileContent = document.getElementById('fileContent');
            fileContent.select();
            document.execCommand('copy');
            // alert('Content copied to clipboard!');
        }

        // Navigate to a directory
        function navigateTo(dir) {
            window.location.href = `browser.php?dir=${dir}`;
        }

        // Load content of the selected file
        function loadFileContent(folder, file) {
            fetch(`get_file.php?folder=${folder}&file=${file}`)
                .then(response => response.text())
                .then(content => {
                    const fileContent = document.getElementById('fileContent');
                    fileContent.value = content;
                    fileContent.disabled = false;
                    document.getElementById('saveButton').disabled = !document.cookie.includes('unlocked=true');
                    document.getElementById('runButton').disabled = false;
                    document.getElementById('runNewWindowButton').disabled = false;
                    document.getElementById('outputFrame').src = 'about:blank';
                    document.getElementById('currentFile').textContent = file;
                });
        }

        // Save edited content back to the file
        function saveFile() {
            const currentDir = "<?php echo $currentDir; ?>";
            const fileContent = document.getElementById('fileContent');
            const file = document.querySelector('#fileList a.active').textContent;

            fetch('save_file.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    folder: currentDir,
                    file: file,
                    content: fileContent.value,
                }),
            })
            .then(response => response.text())
            //.then(message => alert(message));
        }

        // Run the file in the iframe
        function runFile() {
            const currentDir = "<?php echo $currentDir; ?>";
            const file = document.querySelector('#fileList a.active').textContent;
            const outputFrame = document.getElementById('outputFrame');
            outputFrame.src = `${currentDir}/${file}`;
        }

        // Run the file in a new window
        function runFileInNewWindow() {
            const currentDir = "<?php echo $currentDir; ?>";
            const file = document.querySelector('#fileList a.active').textContent;
            window.open(`${currentDir}/${file}`, '_blank');
        }

        // Create a new file in the current directory
        function createNewFile() {
            const currentDir = "<?php echo $currentDir; ?>";

            if (!currentDir) {
                alert('Please select a folder first.');
                return;
            }

            const fileName = prompt('Enter the name of the new file (e.g., example.php):');
            if (!fileName) return;

            fetch('create_file.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    folder: currentDir,
                    file: fileName,
                }),
            })
            .then(response => response.text())
            .then(message => {
                if (message === "File created successfully.") {
                    // alert(message);
                    window.location.reload(); // Refresh the page to show the new file
                } else {
                    alert(message); // Show error message
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while creating the file.');
            });
        }

        // Add a new subdirectory
        function addSubdirectory() {
            const currentDir = "<?php echo $currentDir; ?>";
            const subdirectoryName = prompt('Enter the name of the new subdirectory:');
            if (!subdirectoryName) return;

            fetch('create_directory.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    folder: currentDir,
                    subdirectory: subdirectoryName,
                }),
            })
            .then(response => response.text())
            .then(message => {
                // alert(message);
                window.location.reload(); // Refresh the page to show the new directory
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while creating the subdirectory.');
            });
        }

        // Delete the selected subdirectory
        function deleteSubdirectory() {
            const currentDir = "<?php echo $currentDir; ?>";
            const checkboxes = document.querySelectorAll('#directoryList input[type="checkbox"]:checked');

            if (checkboxes.length === 0) {
                alert('No directories selected.');
                return;
            }

            const directoriesToDelete = Array.from(checkboxes).map(checkbox => checkbox.value);

            if (!confirm(`Are you sure you want to delete the selected directories?`)) {
                return;
            }

            fetch('delete_directory.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    folder: currentDir,
                    directories: directoriesToDelete,
                }),
            })
            .then(response => response.text())
            .then(message => {
                // alert(message);
                window.location.reload(); // Refresh the page to reflect the changes
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the directories.');
            });
        }

        // Delete selected files
        function deleteFiles() {
            const currentDir = "<?php echo $currentDir; ?>";
            const checkboxes = document.querySelectorAll('#fileList input[type="checkbox"]:checked');

            if (checkboxes.length === 0) {
                alert('No files selected.');
                return;
            }

            if (!confirm('Are you sure you want to delete the selected files?')) {
                return;
            }

            const filesToDelete = Array.from(checkboxes).map(checkbox => checkbox.value);

            fetch('delete_files.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    folder: currentDir,
                    files: filesToDelete,
                }),
            })
            .then(response => response.text())
            .then(message => {
                // alert(message);
                window.location.reload(); // Refresh the page to reflect the changes
            });
        }

        // Update the state of the Delete buttons
        function updateDeleteButtonState() {
            const fileCheckboxes = document.querySelectorAll('#fileList input[type="checkbox"]:checked');
            const dirCheckboxes = document.querySelectorAll('#directoryList input[type="checkbox"]:checked');

            document.getElementById('deleteButton').disabled = fileCheckboxes.length === 0 || !document.cookie.includes('unlocked=true');
            document.getElementById('deleteDirectoryButton').disabled = dirCheckboxes.length === 0 || !document.cookie.includes('unlocked=true');
        }

        // Highlight the selected file
        document.addEventListener('click', (e) => {
            if (e.target.tagName === 'A') {
                const links = document.querySelectorAll('#fileList a');
                links.forEach(link => link.classList.remove('active'));
                e.target.classList.add('active');
                document.getElementById('saveButton').disabled = !document.cookie.includes('unlocked=true');
            }
        });

        // Draggable vertical separator functionality
        const verticalSeparator = document.getElementById('vertical-separator');
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        let isVerticalDragging = false;

        verticalSeparator.addEventListener('mousedown', (e) => {
            isVerticalDragging = true;
            document.addEventListener('mousemove', onVerticalMouseMove);
            document.addEventListener('mouseup', onVerticalMouseUp);
        });

        function onVerticalMouseMove(e) {
            if (!isVerticalDragging) return;
            const offset = e.clientX;
            const sidebarWidth = offset - sidebar.offsetLeft;
            const contentWidth = content.offsetWidth - (offset - sidebar.offsetLeft - sidebar.offsetWidth);

            sidebar.style.width = `${sidebarWidth}px`;
            content.style.width = `calc(100% - ${sidebarWidth + verticalSeparator.offsetWidth}px)`;
        }

        function onVerticalMouseUp() {
            isVerticalDragging = false;
            document.removeEventListener('mousemove', onVerticalMouseMove);
            document.removeEventListener('mouseup', onVerticalMouseUp);
        }

        // Draggable horizontal separator functionality
        const horizontalSeparator = document.getElementById('horizontal-separator');
        const fileContent = document.getElementById('fileContent');
        const outputFrame = document.getElementById('outputFrame');
        let isHorizontalDragging = false;

        horizontalSeparator.addEventListener('mousedown', (e) => {
            isHorizontalDragging = true;
            document.addEventListener('mousemove', onHorizontalMouseMove);
            document.addEventListener('mouseup', onHorizontalMouseUp);
        });

        function onHorizontalMouseMove(e) {
            if (!isHorizontalDragging) return;
            const offset = e.clientY;
            const editorHeight = offset - fileContent.offsetTop;
            const outputHeight = document.getElementById('content').offsetHeight - (offset - fileContent.offsetTop - horizontalSeparator.offsetHeight);

            fileContent.style.height = `${editorHeight}px`;
            outputFrame.style.height = `${outputHeight}px`;
        }

        function onHorizontalMouseUp() {
            isHorizontalDragging = false;
            document.removeEventListener('mousemove', onHorizontalMouseMove);
            document.removeEventListener('mouseup', onHorizontalMouseUp);
        }

        // Load files on page load
        updateUI();
        updateDeleteButtonState();
    </script>
</body>
</html>
		 

										
										 
																			 
																		 
		 

								  
				   
								  
			 
	   
	   