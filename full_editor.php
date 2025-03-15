<?php
// Define the root directory (starting point)
$rootDir = 'files'; // Change this to your desired starting directory
$currentDir = isset($_GET['dir']) ? $_GET['dir'] : $rootDir;
$currentFile = isset($_GET['file']) ? $_GET['file'] : '';

// Ensure the current directory is within the root directory
if (strpos($currentDir, $rootDir) !== 0) {
    $currentDir = $rootDir;
}

// Password protection
$password = 'Nicolas'; // Change this to your desired password
$unlocked = isset($_COOKIE['unlocked']) && $_COOKIE['unlocked'] === 'true';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Full Editor</title>
    <!-- CodeMirror CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        #editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: #f4f4f4;
            border-bottom: 1px solid #ccc;
        }
        #editor-header h3 {
            margin: 0;
        }
        #editor {
            flex: 1;
            overflow: hidden;
        }
        .CodeMirror {
            height: 100%;
        }
        #editor-footer {
            padding: 10px;
            background: #f4f4f4;
            border-top: 1px solid #ccc;
            display: flex;
            justify-content: space-between; /* Align buttons to the left and right */
        }
        #editor-footer button {
            padding: 5px 10px;
            margin: 0 5px;
            cursor: pointer;
            background-color: #4CAF50; /* Green background */
            color: white; /* White text */
            border: none;
            border-radius: 3px;
            font-size: 14px;
        }
        #editor-footer button:hover {
            background-color: #45a049; /* Darker green on hover */
        }
        #editor-footer button:active {
            background-color: #3d8b40; /* Even darker green on click */
        }
        #editor-footer button:disabled {
            background-color: #ccc; /* Grey background for disabled buttons */
            cursor: not-allowed; /* Change cursor for disabled buttons */
        }
    </style>
</head>
<body>
    <!-- Editor Header -->
    <div id="editor-header">
        <h3><?php echo $currentDir . '/' . $currentFile; ?></h3>
        <div>
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
            <button id="zoomOutButton" onclick="zoomOut()">-</button>
            <button id="zoomInButton" onclick="zoomIn()">+</button>
        </div>
    </div>

    <!-- Editor Area -->
    <div id="editor"></div>

    <!-- Editor Footer -->
    <div id="editor-footer">
        <button id="copyButton" onclick="copyContent()">Copy</button>
        <button id="saveButton" onclick="saveFile()" <?php echo $unlocked ? '' : 'disabled'; ?>>Save</button>
    </div>

    <!-- CodeMirror JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/yaml/yaml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/clike/clike.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/shell/shell.min.js"></script>
    <script>
        // Initialize CodeMirror
        const editor = CodeMirror(document.getElementById('editor'), {
            lineNumbers: true,
            theme: 'dracula',
            mode: 'htmlmixed',
            value: '',
            viewportMargin: Infinity,
        });

        // Load the file content
        function loadFileContent() {
            const currentDir = "<?php echo $currentDir; ?>";
            const currentFile = "<?php echo $currentFile; ?>";

            fetch(`get_file.php?folder=${currentDir}&file=${currentFile}`)
                .then(response => response.text())
                .then(content => {
                    editor.setValue(content);
                    editor.setOption('mode', getModeFromExtension(currentFile.split('.').pop()));
                });
        }

        // Save the file content
        function saveFile() {
            const currentDir = "<?php echo $currentDir; ?>";
            const currentFile = "<?php echo $currentFile; ?>";
            const content = editor.getValue();

            fetch('save_file.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    folder: currentDir,
                    file: currentFile,
                    content: content,
                }),
            })
            .then(response => response.text())
            .then(message => {
                alert(message);
            });
        }

        // Copy the editor content to the clipboard
        function copyContent() {
            const content = editor.getValue();
            navigator.clipboard.writeText(content)
                .then(() => {
                    alert('Content copied to clipboard!');
                })
                .catch((error) => {
                    console.error('Failed to copy content: ', error);
                    alert('Failed to copy content. Please try again.');
                });
        }

        // Check unlock status and update the Save button
        function updateSaveButtonState() {
            const isUnlocked = document.cookie.includes('unlocked=true');
            document.getElementById('saveButton').disabled = !isUnlocked;
        }

        // Change the language mode
        function changeLanguage() {
            const languageSelect = document.getElementById('languageSelect');
            const mode = languageSelect.value;
            editor.setOption('mode', mode);
        }

        // Zoom functionality
        function zoomIn() {
            const currentSize = parseInt(window.getComputedStyle(editor.getWrapperElement()).fontSize);
            editor.getWrapperElement().style.fontSize = `${currentSize + 2}px`;
            editor.refresh();
        }

        function zoomOut() {
            const currentSize = parseInt(window.getComputedStyle(editor.getWrapperElement()).fontSize);
            editor.getWrapperElement().style.fontSize = `${Math.max(currentSize - 2, 10)}px`;
            editor.refresh();
        }

        // Get the CodeMirror mode based on the file extension
        function getModeFromExtension(extension) {
            switch (extension) {
                case 'html':
                case 'htm':
                    return 'htmlmixed';
                case 'php':
                    return 'php';
                case 'js':
                    return 'javascript';
                case 'css':
                    return 'css';
                case 'xml':
                    return 'xml';
                case 'yml':
                case 'yaml':
                    return 'yaml';
                case 'c':
                    return 'text/x-csrc';
                case 'cpp':
                    return 'text/x-c++src';
                case 'sh':
                    return 'shell';
                default:
                    return 'plaintext';
            }
        }

        // Load the file content on page load
        loadFileContent();
        updateSaveButtonState(); // Add this line
    </script>
</body>
</html>