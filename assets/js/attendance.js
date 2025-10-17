document.addEventListener('DOMContentLoaded', function() {
    // ------------------------------------
    // DOM ELEMENTS
    // ------------------------------------
    const membersIdInput = document.getElementById('members_id');
    const messageDiv = document.getElementById('message');
    const attendanceBody = document.getElementById('attendanceBody');
    const startScannerBtn = document.getElementById('startScannerBtn');
    
    // Declare the scanner variable
    let qrCodeReader; 

    // Focus on the input field when the page loads
    membersIdInput.focus();

    // ------------------------------------
    // HELPER FUNCTIONS
    // ------------------------------------

    function displayMessage(msg, isSuccess) {
        messageDiv.textContent = ''; 
        messageDiv.className = isSuccess ? 'message success' : 'message error';
        messageDiv.textContent = msg;
        
        setTimeout(() => {
            messageDiv.textContent = '';
            messageDiv.className = '';
        }, 3000);
    }

    function sendAttendance(action) {
        const members_id = membersIdInput.value.trim();

        if (!members_id) {
            displayMessage('⚠️ Please enter or scan a Member ID.', false);
            membersIdInput.focus();
            return;
        }

        fetch('../backend/attendance_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, 
            body: new URLSearchParams({
                members_id: members_id,
                action: action
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMessage(data.message, true);
                membersIdInput.value = ''; 
                membersIdInput.focus(); 
                loadAttendanceLogs(); 
            } else {
                displayMessage('🛑 ' + data.message, false);
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            displayMessage('🚫 Connection error. Check server logs.', false);
        });
    }

    function loadAttendanceLogs() {
        fetch('../backend/attendance_action.php?action=get_logs')
        .then(response => response.json())
        .then(data => {
            attendanceBody.innerHTML = ''; 

            if (data.success && data.logs && data.logs.length > 0) {
                data.logs.forEach(log => {
                    const row = attendanceBody.insertRow();
                    
                    if (!log.time_out || log.time_out === 'N/A') {
                        row.classList.add('active-log');
                    }

                    row.insertCell().textContent = log.member_id;
                    row.insertCell().textContent = log.name; 
                    row.insertCell().textContent = log.time_in;
                    row.insertCell().textContent = log.time_out || 'Active'; 
                });
            } else {
                const row = attendanceBody.insertRow();
                const cell = row.insertCell();
                cell.colSpan = 4;
                cell.textContent = 'No attendance logs recorded for today.';
                cell.style.textAlign = 'center';
            }
        })
        .catch(error => {
            console.error('Error loading logs:', error);
        });
    }
    
    // ------------------------------------
    // QR SCANNER LOGIC
    // ------------------------------------

    const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        rememberLastUsedCamera: true
    };

    const onScanSuccess = (decodedText) => {
        
        // ⭐ FIX: CLEAN THE SCANNED ID TO ONLY INCLUDE DIGITS (0-9)
        // This ensures any potential prefix (like "GM-") or extra data is stripped.
        const cleanedId = decodedText.trim().replace(/\D/g, ''); 
        
        // 1. Stop the scanner
        qrCodeReader.stop().then(() => {
            // 2. Populate input with the CLEANED NUMERIC ID
            membersIdInput.value = cleanedId;
            startScannerBtn.textContent = 'Start QR Scanner 📸';
            startScannerBtn.classList.remove('timeout'); 
            startScannerBtn.classList.add('timein'); 

            // 3. Automatically trigger Time In
            sendAttendance('time_in'); 

        }).catch((err) => {
            console.error("Failed to stop the scanner after successful scan:", err);
            displayMessage('🛑 Scan successful but could not stop camera.', false);
        });
    };

    /**
     * Function to handle the button click (Start/Stop Scanner)
     */
    startScannerBtn.addEventListener('click', () => {
        // Test if the scanner object was successfully initialized
        if (!qrCodeReader) {
            displayMessage("Scanner failed to initialize. Check browser console.", false);
            return;
        }

        if (startScannerBtn.textContent.includes('Stop')) {
            // STOP SCANNER LOGIC
            qrCodeReader.stop().then(() => {
                startScannerBtn.textContent = 'Start QR Scanner ';
                startScannerBtn.classList.remove('timeout');
                startScannerBtn.classList.add('timein');
            }).catch((err) => {
                displayMessage("Error stopping camera. Please refresh.", false);
                console.error("Unable to stop scanning.", err);
            });
        } else {
            // START SCANNER LOGIC
            qrCodeReader.start({ facingMode: "environment" }, config, onScanSuccess)
            .then(() => {
                startScannerBtn.textContent = 'Stop Scanner ';
                startScannerBtn.classList.remove('timein');
                startScannerBtn.classList.add('timeout');
                displayMessage("Scanner active. Point camera at QR code.", true);
            })
            .catch((err) => {
                // IMPORTANT: This catch often triggers if the user denies camera access or if HTTPS is not used.
                displayMessage("Error: Camera not found or permission denied. (Requires HTTPS/localhost)", false);
                console.error("Error starting QR scanner: ", err);
            });
        }
    });

    // ------------------------------------
    // EXISTING EVENT LISTENERS & INITIALIZATION
    // ------------------------------------
    
    document.getElementById('timeInBtn').addEventListener('click', () => {
        sendAttendance('time_in');
    });

    document.getElementById('timeOutBtn').addEventListener('click', () => {
        sendAttendance('time_out');
    });

    membersIdInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault(); 
            sendAttendance('time_in'); 
        }
    });

    loadAttendanceLogs();
    setInterval(loadAttendanceLogs, 30000); 

    // FINAL & CRITICAL STEP: INITIALIZE THE SCANNER OBJECT
    try {
        qrCodeReader = new Html5Qrcode("reader");
    } catch (e) {
        console.error("Html5Qrcode library not found. Check the script link in your HTML.", e);
        startScannerBtn.disabled = true;
        startScannerBtn.textContent = 'Scanner Failed to Load';
        displayMessage("QR Scanner module failed to load. Check console for details.", false);
    }
});