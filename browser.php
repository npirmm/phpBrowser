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
	<!-- CodeMirror CSS -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
	<!-- CodeMirror Themes -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css">
	<!-- CodeMirror JavaScript -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
	<!-- CodeMirror Modes (for syntax highlighting) -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/yaml/yaml.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/clike/clike.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/shell/shell.min.js"></script>
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
			margin: 5px 0;
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
			margin: 2px 0;
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
		/* Style for Up and Root buttons */
		#upButton, #rootButton {
			padding: 5px 10px;
			margin-right: 5px;
			cursor: pointer;
		}

		#upButton:disabled, #rootButton:disabled {
			opacity: 0.5;
			pointer-events: none;
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
		/* Ensure the editor container fills its parent */
		#editor {
			height: 100%; /* Fill the available height */
			overflow: hidden; /* Prevent scrollbars */
		}

		/* Ensure the CodeMirror editor fills its container */
		.CodeMirror {
			height: 100%; /* Fill the height of the #editor container */
		}
		
		#tempPopup {
			position: fixed;
			top: 20px;
			right: 20px;
			background-color: #4CAF50; /* Green background */
			color: white; /* White text */
			padding: 10px 20px;
			border-radius: 5px;
			box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
			z-index: 1000; /* Ensure it appears above other elements */
			font-family: Arial, sans-serif;
			font-size: 14px;
			opacity: 0;
			transition: opacity 0.3s ease-in-out;
		}

		#uploadPopup {
			background: #fff;
			padding: 20px;
			border-radius: 5px;
			box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
		}

		#uploadOverlay {
			background: rgba(0, 0, 0, 0.5);
		}
		
		/* Style for the disabled checkbox */
		input[type="checkbox"]:disabled {
			opacity: 0.5; /* Make the checkbox appear greyed out */
			cursor: not-allowed; /* Change the cursor to indicate it's not clickable */
		}

		/* Style for the label when the checkbox is disabled */
		input[type="checkbox"]:disabled + label {
			opacity: 0.5; /* Grey out the label text */
			cursor: not-allowed; /* Change the cursor to indicate it's not clickable */
		}
		/* Reduce spacing between sections */
		fieldset {
			margin-bottom: 10px;
			padding: 10px;
			border: 1px solid #ccc;
			border-radius: 5px;
		}

		legend {
			font-weight: bold;
		}

		/* Reduce spacing within lists */
		ul {
			margin: 5px 0;
			padding-left: 20px;
		}

		/* Reduce spacing for buttons */
		.button-container {
			margin: 5px 0;
		}

		/* Style for directory and file items */
		.directory-item, .file-item {
			display: flex;
			align-items: center;
			margin: 2px 0;
		}

		.directory-item {
			cursor: pointer;
			padding: 5px;
			background: #e0e0e0;
			border-radius: 3px;
		}

		.directory-item:hover {
			background: #d0d0d0;
		}
		#withPathCheckbox:disabled + label {
			color: grey;
			cursor: not-allowed;
		}
			</style>
</head>
<body>
    <!-- Sidebar for folder and file selection -->
	<!-- Sidebar for folder and file selection -->
	<div id="sidebar">
		<!-- Current Directory -->
<!-- Fieldset for Current Directory and Navigation -->
<fieldset style="margin-bottom: 10px; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
    <legend><strong>Current Directory</strong></legend>
    <p style="margin: 5px 0;"><u>Current Directory:</u> <strong><?php echo $currentDir; ?></strong></p>
    <div style="margin: 5px 0;">
        <button id="rootButton" onclick="navigateTo('<?php echo $rootDir; ?>')" <?php echo $currentDir === $rootDir ? 'disabled' : ''; ?>>/</button>
        <button id="upButton" onclick="navigateTo('<?php echo $parentDir; ?>')" <?php echo $currentDir === $rootDir ? 'disabled' : ''; ?>>↑</button>
    </div>
</fieldset>

<!-- Fieldset for Subdirectories -->
<fieldset style="margin-bottom: 10px; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
    <legend><strong>Subdirectories</strong></legend>
    <ul id="directoryList" style="margin: 5px 0; padding-left: 20px;">
        <?php foreach ($subdirs as $dir): ?>
            <li class="directory-item">
                <input type="checkbox" value="<?php echo basename($dir); ?>" onchange="updateDeleteButtonState()">
                <span onclick="navigateTo('<?php echo $dir; ?>')"><?php echo basename($dir); ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
    <div style="margin: 5px 0;">
        <button onclick="addSubdirectory()" id="addSubdirectoryButton" <?php echo $unlocked ? '' : 'disabled'; ?>>Add Subdirectory</button>
        <button onclick="deleteSubdirectory()" id="deleteDirectoryButton" disabled>Delete Subdirectory</button>
    </div>
</fieldset>

<!-- Fieldset for Files -->
<fieldset style="margin-bottom: 10px; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
    <legend><strong>Files</strong></legend>
    <ul id="fileList" style="margin: 5px 0; padding-left: 20px;">
        <?php foreach ($files as $file): ?>
            <li class="file-item">
                <input type="checkbox" value="<?php echo basename($file); ?>" onchange="updateDeleteButtonState()">
                <a href="#" onclick="loadFileContent('<?php echo $currentDir; ?>', '<?php echo basename($file); ?>')"><?php echo basename($file); ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
    <div style="margin: 5px 0;">
        <button id="newButton" onclick="createNewFile()" <?php echo $unlocked ? '' : 'disabled'; ?>>New</button>
        <button id="deleteButton" onclick="deleteFiles()" disabled>Delete</button>
    </div>
    <div style="margin: 5px 0;">
        <button id="uploadButton" onclick="openUploadPopup()" <?php echo $unlocked ? '' : 'disabled'; ?>>Upload</button>
        <button id="downloadButton" onclick="downloadFiles()" disabled>Download</button>
        <label for="withPathCheckbox" style="margin-left: 10px;">
            <input type="checkbox" id="withPathCheckbox" disabled> With Path
        </label>
    </div>
</fieldset>
		<p></p>
        <!-- Password form -->
		<div class="password-form">
			<?php if (!$unlocked): ?>
				<input type="password" id="passwordInput" placeholder="Enter password" onkeypress="handleKeyPress(event)">
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
	
		<!-- Temporary Popup Container -->
		<div id="tempPopup" style="display: none;"></div>
	
        <!-- Editor header -->
		<div id="editor-header">
			<div>
				<!-- <p><u>Current Directory:</u> <strong><?php echo $currentDir; ?></strong></p> --!>
				<p><u>Current File:</u> <b><span id="currentFile"></span></b></p>
			</div>
			<div>
				<!-- Language Dropdown -->
				<select id="languageSelect" onchange="changeLanguage()">
					<option value="htmlmixed">HTML</option>
					<option value="php">PHP</option>
					<option value="javascript">JavaScript</option>
					<option value="css">CSS</option>
					<option value="xml">XML</option>
					<option value="yaml">YAML</option>
					<option value="text/x-csrc">C</option>
					<option value="text/x-c++src">C++</option>
					<option value="shell">Shell</option>
					<option value="plaintext">Plain Text</option>
				</select>
				<!-- Zoom Buttons -->
				<button id="zoomOutButton" onclick="zoomOut()">-</button>
				<button id="zoomInButton" onclick="zoomIn()">+</button>
			</div>
		</div>

        <!-- Old Editor area 
        <textarea id="fileContent" disabled></textarea> -->
		<!-- Editor area -->
		<div id="editor"></div>


		<!-- Buttons -->
		<div class="button-container">
			<button id="copyButton" onclick="copyContent()">Copy</button>
			<button id="renameButton" onclick="renameFile()" disabled>Rename</button>
			<button id="saveButton" onclick="saveFile()" disabled>Save</button>
			<button id="runButton" onclick="runFile()">Run</button>
			<button id="runNewWindowButton" onclick="runFileInNewWindow()">Run in New Window</button>
			<button id="fullEditorButton" onclick="openFullEditor()">Full Editor</button>
			<label>
				<input type="checkbox" id="autoRunCheckbox" checked> Automatic Run
			</label>
		</div>

        <!-- Horizontal draggable separator -->
        <div id="horizontal-separator"></div>

        <!-- Output frame -->
        <iframe id="outputFrame" src="about:blank"></iframe>
    </div>
	

    
	<script>
        // Password protection
        const password = 'Nicolas'; // Change this to your desired unlocking password

		function handleKeyPress(event) {
			if (event.key === 'Enter') {
				unlock();
			}
		}
        // Unlock functionality
        function unlock() {
            const passwordInput = document.getElementById('passwordInput').value;
            if (passwordInput === password) {
                setUnlocked(true);
                updateUI();
                //alert('Unlocked!');
				showTempPopup('Unlocked!');
            } else {
                alert('Incorrect password.');
            }
        }

        // Lock functionality
        function lock() {
            setUnlocked(false);
            updateUI();
            //alert('Locked!');
			showTempPopup('Locked!');
        }

        // Set unlocked state in a cookie
        function setUnlocked(unlocked) {
            document.cookie = `unlocked=${unlocked}; path=/`;
            location.reload(); // Refresh the page to apply changes
        }

		// Update UI based on unlock state
		function updateUI() {
			const unlocked = document.cookie.includes('unlocked=true');
			const fileSelected = document.querySelector('#fileList a.active') !== null;
			const filesChecked = document.querySelectorAll('#fileList input[type="checkbox"]:checked').length > 0;

			document.getElementById('addSubdirectoryButton').disabled = !unlocked;
			document.getElementById('newButton').disabled = !unlocked;
			document.getElementById('uploadButton').disabled = !unlocked; // Disable upload button when locked
			document.getElementById('renameButton').disabled = !unlocked || !fileSelected;
			document.getElementById('saveButton').disabled = !unlocked || !fileSelected;
			document.getElementById('deleteButton').disabled = !unlocked || !filesChecked;
			document.getElementById('deleteDirectoryButton').disabled = !unlocked;
			document.getElementById('downloadButton').disabled = !filesChecked; // Enable download button if files are selected
			document.getElementById('withPathCheckbox').disabled = !filesChecked; // Enable checkbox if files are selected
		}

		// Function to show a temporary popup message
		function showTempPopup(message) {
			const popup = document.getElementById('tempPopup');
			popup.textContent = message; // Set the message
			popup.style.display = 'block'; // Show the popup
			popup.style.opacity = '1'; // Fade in

			// Hide the popup after 1 second
			setTimeout(() => {
				popup.style.opacity = '0'; // Fade out
				setTimeout(() => {
					popup.style.display = 'none'; // Hide completely
				}, 300); // Wait for the fade-out transition to finish
			}, 1000); // Display duration
		}

		// Updated copyContent function
		function copyContent() {
			const content = editor.getValue(); // Get content from CodeMirror

			// Use the Clipboard API to copy the content
			navigator.clipboard.writeText(content)
				.then(() => {
					showTempPopup('Content copied to clipboard!'); // Show temporary popup
				})
				.catch((error) => {
					console.error('Failed to copy content: ', error);
					showTempPopup('Failed to copy content. Please try again.'); // Show error message
				});
		}

        // Navigate to a directory
        function navigateTo(dir) {
            window.location.href = `browser.php?dir=${dir}`;
        }

		// Rename the selected file
		function renameFile() {
			const currentDir = "<?php echo $currentDir; ?>";
			const file = document.querySelector('#fileList a.active').textContent;

			if (!file) {
				//alert('No file selected.');
				showTempPopup('No file selected.');
				return;
			}

			const newFileName = prompt('Enter the new name for the file (e.g., newname.php):', file);
			if (!newFileName || newFileName === file) return;

			fetch('rename_file.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					folder: currentDir,
					oldFile: file,
					newFile: newFileName,
				}),
			})
			.then(response => response.text())
			.then(message => {
				//alert(message);
				showTempPopup(message);
				window.location.reload(); // Refresh the page to reflect the changes
			})
			.catch(error => {
				console.error('Error:', error);
				//alert('An error occurred while renaming the file.');
				showTempPopup('An error occurred while renaming the file.');
			});
		}

		// Save edited content back to the file
		function saveFile() {
			const currentDir = "<?php echo $currentDir; ?>";
			const file = document.querySelector('#fileList a.active').textContent;
			const content = editor.getValue(); // Get content from CodeMirror

			fetch('save_file.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					folder: currentDir,
					file: file,
					content: content,
				}),
			})
			.then(response => response.text())
			//.then(message => alert(message));
			.then(message => showTempPopup(message));
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
                //alert('Please select a folder first.');
				showTempPopup('Please select a folder first.');
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
					showTempPopup(message);
                    window.location.reload(); // Refresh the page to show the new file
					loadFileContent(currentDir, fileName); // Open the new file in the editor
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
				showTempPopup(message);
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
                //alert('No directories selected.');
				showTempPopup('No directories selected.');
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
                //alert('No files selected.');
				showTempPopup('No files selected.');
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
				showTempPopup(message);
                window.location.reload(); // Refresh the page to reflect the changes
            });
        }


		// Highlight the selected file and update button states
		document.addEventListener('click', (e) => {
			if (e.target.tagName === 'A') {
				const links = document.querySelectorAll('#fileList a');
				links.forEach(link => link.classList.remove('active'));
				e.target.classList.add('active');
				updateUI(); // Update button states when a file is selected
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
		// Variables to store the heights of the editor and output panels
		let editorHeight = localStorage.getItem('editorHeight') || '50%'; // Default height (half of the available space)
		let outputHeight = localStorage.getItem('outputHeight') || '50%'; // Default height (half of the available space)

		// Initialize CodeMirror
		const editor = CodeMirror(document.getElementById('editor'), {
			lineNumbers: true, // Show line numbers
			theme: 'dracula',  // Use the Dracula theme
			mode: 'htmlmixed', // Default mode
			value: '',         // Initial content
			viewportMargin: Infinity, // Allow the editor to expand vertically
		});

		// Set initial heights
		document.getElementById('editor').style.height = editorHeight;
		document.getElementById('outputFrame').style.height = outputHeight;

		// Draggable horizontal separator functionality
		const horizontalSeparator = document.getElementById('horizontal-separator');
		const editorContainer = document.getElementById('editor');
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
			const newEditorHeight = offset - editorContainer.offsetTop;
			const newOutputHeight = document.getElementById('content').offsetHeight - (offset - editorContainer.offsetTop - horizontalSeparator.offsetHeight);

			// Update the stored heights
			editorHeight = `${newEditorHeight}px`;
			outputHeight = `${newOutputHeight}px`;

			// Store the heights in localStorage
			localStorage.setItem('editorHeight', editorHeight);
			localStorage.setItem('outputHeight', outputHeight);

			// Set the heights of the editor container and output frame
			editorContainer.style.height = editorHeight;
			outputFrame.style.height = outputHeight;

			// Refresh CodeMirror to adjust its height
			editor.refresh();
		}

		function onHorizontalMouseUp() {
			isHorizontalDragging = false;
			document.removeEventListener('mousemove', onHorizontalMouseMove);
			document.removeEventListener('mouseup', onHorizontalMouseUp);
		}

		// Load content of the selected file
		function loadFileContent(folder, file) {
			const outputFrame = document.getElementById('outputFrame');
			const autoRunCheckbox = document.getElementById('autoRunCheckbox');

			// List of file types that can be displayed in the editor
			const editableFileTypes = ['.html', '.htm', '.php', '.txt', '.cfg', '.yml', '.js', '.css', '.xml', '.yaml', '.c', '.cpp', '.sh', '.md'];

			// Get the file extension
			const fileExtension = file.substring(file.lastIndexOf('.')).toLowerCase();

			if (editableFileTypes.includes(fileExtension)) {
				// Load the file content into the editor
				fetch(`get_file.php?folder=${folder}&file=${file}`)
					.then(response => response.text())
					.then(content => {
						editor.setValue(content); // Set content in CodeMirror
						editor.setOption('mode', getModeFromExtension(fileExtension)); // Set mode based on file extension
						document.getElementById('saveButton').disabled = !document.cookie.includes('unlocked=true');
						document.getElementById('runButton').disabled = false;
						document.getElementById('runNewWindowButton').disabled = false;
						document.getElementById('currentFile').textContent = file;

						// Restore the stored heights
						editorContainer.style.height = editorHeight;
						outputFrame.style.height = outputHeight;

						// Refresh CodeMirror to adjust its height
						editor.refresh();

						// Automatically run the file if "Automatic Run" is checked
						if (autoRunCheckbox.checked) {
							runFile();
						}
					});
			} else {
				// For non-editable file types, clear the editor and render the file in the output panel
				editor.setValue(''); // Clear the editor
				editor.setOption('mode', 'plaintext'); // Set mode to plain text
				document.getElementById('saveButton').disabled = true;
				document.getElementById('runButton').disabled = false;
				document.getElementById('runNewWindowButton').disabled = false;
				document.getElementById('currentFile').textContent = file;

				// Restore the stored heights
				editorContainer.style.height = editorHeight;
				outputFrame.style.height = outputHeight;

				// Render the file directly in the output panel
				outputFrame.src = `${folder}/${file}`;
			}
		}

		// Refresh CodeMirror on window resize
		window.addEventListener('resize', () => {
			editor.refresh();
		});

		// Change the language mode based on the selected option
		function changeLanguage() {
			const languageSelect = document.getElementById('languageSelect');
			const mode = languageSelect.value;
			editor.setOption('mode', mode);
		}

		// Load content into the editor
		function loadFileContent(folder, file) {
			const outputFrame = document.getElementById('outputFrame');
			const autoRunCheckbox = document.getElementById('autoRunCheckbox');

			// List of file types that can be displayed in the editor
			const editableFileTypes = ['.html', '.htm', '.php', '.txt', '.cfg', '.yml', '.js', '.css', '.xml', '.yaml', '.c', '.cpp', '.sh', '.md'];

			// Get the file extension
			const fileExtension = file.substring(file.lastIndexOf('.')).toLowerCase();

			if (editableFileTypes.includes(fileExtension)) {
				// Load the file content into the editor
				fetch(`get_file.php?folder=${folder}&file=${file}`)
					.then(response => response.text())
					.then(content => {
						editor.setValue(content); // Set content in CodeMirror
						editor.setOption('mode', getModeFromExtension(fileExtension)); // Set mode based on file extension
						document.getElementById('saveButton').disabled = !document.cookie.includes('unlocked=true');
						document.getElementById('runButton').disabled = false;
						document.getElementById('runNewWindowButton').disabled = false;
						document.getElementById('currentFile').textContent = file;

						// Automatically run the file if "Automatic Run" is checked
						if (autoRunCheckbox.checked) {
							runFile();
						}
					});
			} else {
				// For non-editable file types, clear the editor and render the file in the output panel
				editor.setValue(''); // Clear the editor
				editor.setOption('mode', 'plaintext'); // Set mode to plain text
				document.getElementById('saveButton').disabled = true;
				document.getElementById('runButton').disabled = false;
				document.getElementById('runNewWindowButton').disabled = false;
				document.getElementById('currentFile').textContent = file;

				// Render the file directly in the output panel
				outputFrame.src = `${folder}/${file}`;
			}
		}

		// Get the CodeMirror mode based on the file extension
		function getModeFromExtension(extension) {
			switch (extension) {
				case '.html':
				case '.htm':
					return 'htmlmixed';
				case '.php':
					return 'php';
				case '.js':
					return 'javascript';
				case '.css':
					return 'css';
				case '.xml':
					return 'xml';
				case '.yml':
				case '.yaml':
					return 'yaml';
				case '.c':
					return 'text/x-csrc';
				case '.cpp':
					return 'text/x-c++src';
				case '.sh':
					return 'shell';
				case '.md':
					return 'plaintext';
				default:
					return 'plaintext';
			}
		}

		// Zoom functionality
		function zoomIn() {
			const currentSize = parseInt(window.getComputedStyle(editor.getWrapperElement()).fontSize);
			editor.getWrapperElement().style.fontSize = `${currentSize + 2}px`;
			editor.refresh(); // Refresh the editor to apply the new font size
		}

		function zoomOut() {
			const currentSize = parseInt(window.getComputedStyle(editor.getWrapperElement()).fontSize);
			editor.getWrapperElement().style.fontSize = `${Math.max(currentSize - 2, 10)}px`; // Minimum font size of 10px
			editor.refresh(); // Refresh the editor to apply the new font size
		}

		// Upload functionality
		function openUploadPopup() {
			document.getElementById('uploadPopup').style.display = 'block';
			document.getElementById('uploadOverlay').style.display = 'block';
		}

		function closeUploadPopup() {
			document.getElementById('uploadPopup').style.display = 'none';
			document.getElementById('uploadOverlay').style.display = 'none';
		}

		function uploadFiles() {
			const fileInput = document.getElementById('fileInput');
			const files = fileInput.files;
			if (files.length === 0) {
				alert('Please select at least one file to upload.');
				return;
			}

			const formData = new FormData();
			formData.append('folder', "<?php echo $currentDir; ?>"); // Add the current directory
			for (let i = 0; i < files.length; i++) {
				formData.append('files[]', files[i]);
			}

			fetch('upload.php', {
				method: 'POST',
				body: formData,
			})
			.then(response => response.text())
			.then(message => {
				showTempPopup(message);
				closeUploadPopup();
				window.location.reload(); // Refresh the page to show the new files
			})
			.catch(error => {
				console.error('Error:', error);
				showTempPopup('An error occurred while uploading files.');
			});
		}

		// Download functionality
		function downloadFiles() {
			const checkboxes = document.querySelectorAll('#fileList input[type="checkbox"]:checked');
			if (checkboxes.length === 0) {
				showTempPopup('No files selected.');
				return;
			}

			const currentDir = "<?php echo $currentDir; ?>";
			const filesToDownload = Array.from(checkboxes).map(checkbox => {
				const fileName = checkbox.value;
				return `${currentDir}/${fileName}`; // Include the full relative path
			});

			const withPath = document.getElementById('withPathCheckbox').checked;

			if (filesToDownload.length === 1 && !withPath) {
				// Single file download (no ZIP)
				const filePath = filesToDownload[0];
				window.location.href = `download.php?file=${encodeURIComponent(filePath)}`; // Encode the file path
			} else {
				// Multiple files or "With Path" selected: create a ZIP
				const script = withPath ? 'downloadzipwithfullpath.php' : 'download.php';

				// Generate the ZIP file name
				let zipFileName;
				if (withPath) {
					// Replace '/' with '-' for the full path
					zipFileName = currentDir.replace(/\//g, '-') + '.zip';
				} else {
					// Use the current directory name for the ZIP file
					zipFileName = currentDir.split('/').pop() + '.zip';
				}

				fetch(script, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify({
						folder: "<?php echo $rootDir; ?>", // Always use the root directory as the base
						files: filesToDownload,
					}),
				})
				.then(response => {
					if (response.ok) {
						return response.blob();
					} else {
						throw new Error('Failed to download files.');
					}
				})
				.then(blob => {
					const url = window.URL.createObjectURL(blob);
					const a = document.createElement('a');
					a.href = url;
					a.download = zipFileName; // Use the generated ZIP file name
					document.body.appendChild(a);
					a.click();
					document.body.removeChild(a);
					window.URL.revokeObjectURL(url);

					// Refresh button states after download
					updateUI();
				})
				.catch(error => {
					console.error('Error:', error);
					showTempPopup('An error occurred while downloading files.');
				});
			}
		}
function openFullEditor() {
			const currentDir = "<?php echo $currentDir; ?>";
			const currentFile = document.querySelector('#fileList a.active')?.textContent;

			if (!currentFile) {
				showTempPopup('No file selected.');
				return;
			}

			const url = `full_editor.php?dir=${currentDir}&file=${currentFile}`;
			window.open(url, '_blank', 'width=800,height=600');
		}

		function downloadFilesWithPath() {
			const checkboxes = document.querySelectorAll('#fileList input[type="checkbox"]:checked');
			if (checkboxes.length === 0) {
				showTempPopup('No files selected.');
				return;
			}

			const currentDir = "<?php echo $currentDir; ?>";
			const filesToDownload = Array.from(checkboxes).map(checkbox => {
				const fileName = checkbox.value;
				return `${currentDir}/${fileName}`; // Include the full relative path
			});

			fetch('downloadzipwithfullpath.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					folder: "<?php echo $rootDir; ?>", // Always use the root directory as the base
					files: filesToDownload,
				}),
			})
			.then(response => {
				if (response.ok) {
					return response.blob();
				} else {
					throw new Error('Failed to download files.');
				}
			})
			.then(blob => {
				const url = window.URL.createObjectURL(blob);
				const a = document.createElement('a');
				a.href = url;
				a.download = 'files_with_path.zip'; // Default name for the ZIP file
				document.body.appendChild(a);
				a.click();
				document.body.removeChild(a);
				window.URL.revokeObjectURL(url);
			})
			.catch(error => {
				console.error('Error:', error);
				showTempPopup('An error occurred while downloading files.');
			});
		}
		
		function downloadFilesWithPath() {
			const checkboxes = document.querySelectorAll('#fileList input[type="checkbox"]:checked');
			if (checkboxes.length === 0) {
				showTempPopup('No files selected.');
				return;
			}

			const currentDir = "<?php echo $currentDir; ?>";
			const filesToDownload = Array.from(checkboxes).map(checkbox => {
				const fileName = checkbox.value;
				return `${currentDir}/${fileName}`; // Include the full relative path
			});

			fetch('downloadzipwithfullpath.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					folder: "<?php echo $rootDir; ?>", // Always use the root directory as the base
					files: filesToDownload,
				}),
			})
			.then(response => {
				if (response.ok) {
					return response.blob();
				} else {
					throw new Error('Failed to download files.');
				}
			})
			.then(blob => {
				const url = window.URL.createObjectURL(blob);
				const a = document.createElement('a');
				a.href = url;
				a.download = 'files_with_path.zip'; // Default name for the ZIP file
				document.body.appendChild(a);
				a.click();
				document.body.removeChild(a);
				window.URL.revokeObjectURL(url);
			})
			.catch(error => {
				console.error('Error:', error);
				showTempPopup('An error occurred while downloading files.');
			});
		}

		// Update Download Button State
		function updateDeleteButtonState() {
			const fileCheckboxes = document.querySelectorAll('#fileList input[type="checkbox"]:checked');
			const dirCheckboxes = document.querySelectorAll('#directoryList input[type="checkbox"]:checked');

			const hasFilesSelected = fileCheckboxes.length > 0;
			const isUnlocked = document.cookie.includes('unlocked=true');

			document.getElementById('deleteButton').disabled = !hasFilesSelected || !isUnlocked;
			document.getElementById('downloadButton').disabled = !hasFilesSelected;
			document.getElementById('withPathCheckbox').disabled = !hasFilesSelected; // Enable/disable the checkbox
		}

        // Load files on page load
        updateUI();
        updateDeleteButtonState();
    </script>

	<!-- Upload Popup -->
	<div id="uploadPopup" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border: 1px solid #ccc; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); z-index: 1000;">
		<h3>Upload Files</h3>
		<form id="uploadForm" enctype="multipart/form-data">
			<input type="file" id="fileInput" name="files[]" multiple style="margin-bottom: 10px;">
			<button type="button" onclick="closeUploadPopup()">Cancel</button>
			<button type="button" onclick="uploadFiles()">Upload</button>
		</form>
	</div>
	<div id="uploadOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 999;"></div>


</body>
</html>
