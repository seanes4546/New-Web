<?php
session_start();
// PHP initializes or tracks the player's current night session
if (!isset($_SESSION['current_night'])) {
    $_SESSION['current_night'] = 1;
}

// Simple dynamic endpoint to reset or save high-scores
if (isset($_GET['reset'])) {
    $_SESSION['current_night'] = 1;
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Five Nights Nigga's</title>
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://jsdelivr.net">
    <!-- FontAwesome for UI Icons -->
    <link rel="stylesheet" href="https://cloudflare.com">
    
    <style>
        body {
            background-color: #050505;
            color: #ffffff;
            font-family: 'Courier New', Courier, monospace;
            user-select: none;
            overflow: hidden;
        }
        /* The Security Office View */
        #office {
            position: relative;
            width: 100%;
            height: 65vh;
            background: linear-gradient(0deg, #111 0%, #222 100%);
            border: 4px solid #333;
            overflow: hidden;
        }
        .office-wall {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.2rem;
            transition: background 0.2s;
        }
        /* Functional Door Buttons */
        .door-control {
            position: absolute;
            top: 40%;
            background: #222;
            border: 2px solid #555;
            padding: 10px;
            z-index: 10;
        }
        #left-control { left: 10px; }
        #right-control { right: 10px; }
        
        /* Visual Door Overlays */
        .door-wall {
            position: absolute;
            top: 0;
            width: 15%;
            height: 100%;
            background: #444;
            transition: transform 0.3s ease-in-out;
            z-index: 5;
        }
        #left-door { left: 0; transform: translateY(-100%); }
        #right-door { right: 0; transform: translateY(-100%); }
        #left-door.closed { transform: translateY(0); }
        #right-door.closed { transform: translateY(0); }

        /* Monitor Camera Overlays */
        #camera-monitor {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            z-index: 20;
            border: 4px solid #00ff00;
        }
        .static-effect {
            position: absolute;
            width: 100%;
            height: 100%;
            background: repeating-linear-gradient(0deg, rgba(0,0,0,0.15), rgba(0,0,0,0.15) 1px, transparent 1px, transparent 2px);
            pointer-events: none;
        }
        /* Jumpscare Screen Overlay */
        #jumpscare-screen {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: #000;
            z-index: 100;
            justify-content: center;
            align-items: center;
        }
        .jumpscare-text {
            font-size: 5rem;
            color: #ff0000;
            font-weight: bold;
            animation: shake 0.1s infinite;
        }
        @keyframes shake {
            0% { transform: translate(2px, 1px) rotate(0deg); }
            10% { transform: translate(-1px, -2px) rotate(-1px); }
            20% { transform: translate(-3px, 0px) rotate(1px); }
            100% { transform: translate(1px, -2px) rotate(-1px); }
        }
    </style>
</head>
<body class="container py-4">

    <!-- HUD Header Status -->
    <div class="row mb-3 bg-dark p-3 rounded border border-secondary shadow">
        <div class="col-6">
            <h4 class="mb-0 text-warning"><i class="fas fa-bolt"></i> Power: <span id="power-display">100</span>%</h4>
            <small class="text-muted">Usage: <span id="usage-display" class="text-danger">⚡</span></small>
        </div>
        <div class="col-6 text-right">
            <h4 class="mb-0 text-info"><i class="fas fa-clock"></i> Time: <span id="time-display">12</span> AM</h4>
            <small class="text-light">Night <?php echo $_SESSION['current_night']; ?></small>
        </div>
    </div>

    <!-- MAIN GAMEPLAY UNIT -->
    <div id="game-container" class="position-relative">
        
        <!-- Jumpscare Screen Layer -->
        <div id="jumpscare-screen">
            <div class="jumpscare-text">FREDDY GOT YOU!</div>
        </div>

        <!-- The Main Office Space -->
        <div id="office">
            <div id="left-door" class="door-wall"></div>
            <div id="right-door" class="door-wall"></div>

            <!-- Left Door Switches -->
            <div id="left-control" class="door-control text-center rounded">
                <button class="btn btn-sm btn-danger mb-2 btn-block" onclick="toggleDoor('left')">DOOR</button>
                <button class="btn btn-sm btn-secondary btn-block" onmousedown="toggleLight('left', true)" onmouseup="toggleLight('left', false)">LIGHT</button>
            </div>

            <!-- Right Door Switches -->
            <div id="right-control" class="door-control text-center rounded">
                <button class="btn btn-sm btn-danger mb-2 btn-block" onclick="toggleDoor('right')">DOOR</button>
                <button class="btn btn-sm btn-secondary btn-block" onmousedown="toggleLight('right', true)" onmouseup="toggleLight('right', false)">LIGHT</button>
            </div>

            <!-- Main Forward Window View -->
            <div id="office-view" class="office-wall">
                <div class="text-center">
                    <h5 class="text-muted">YOU ARE SAFE INSIDE THE OFFICE</h5>
                    <p id="window-status" class="text-success font-weight-bold">The hallways are dark...</p>
                </div>
            </div>

            <!-- CCTV Camera Feeds Monitor Panel Overlay -->
            <div id="camera-monitor">
                <div class="static-effect"></div>
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge badge-danger px-3 py-2 animate-pulse">🔴 LIVE FEED: CAM <span id="active-cam-id">1A</span></span>
                        <button class="btn btn-sm btn-success" onclick="toggleMonitor()">Close Monitor</button>
                    </div>
                    <div class="bg-dark p-4 rounded text-center border border-secondary" style="height: 250px; display: flex; align-items: center; justify-content: center;">
                        <h3 id="camera-display-text" class="text-success">Loading Feed...</h3>
                    </div>
                    <!-- Camera Switch Menu Mapping Layout -->
                    <div class="row mt-3 px-3 justify-content-center">
                        <button class="btn btn-outline-info m-1 col-3" onclick="switchCam('1A')">CAM 1A (Show Stage)</button>
                        <button class="btn btn-outline-info m-1 col-3" onclick="switchCam('1B')">CAM 1B (Left Hall)</button>
                        <button class="btn btn-outline-info m-1 col-3" onclick="switchCam('1C')">CAM 1C (Right Hall)</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monitor Flip Button Trigger -->
        <div class="text-center mt-3">
            <button class="btn btn-lg btn-info btn-block shadow-lg py-3 font-weight-bold" onclick="toggleMonitor()">
                <i class="fas fa-desktop"></i> FLIP SECURITY MONITOR
            </button>
        </div>
    </div>

    <!-- Win State Modal Popup -->
    <div class="modal fade" id="winModal" data-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white border-success">
                <div class="modal-body text-center py-5">
                    <h2 class="text-success font-weight-bold">6:00 AM</h2>
                    <p class="lead">You survived the shift!</p>
                    <a href="index.php?reset=1" class="btn btn-outline-success">Play Again</a>
                </div>
            </div>
        </div>
    </div>


     <!-- Core Game Engine JavaScript Framework -->
     <script src="https://jsdelivr.net"></script>
    <script src="https://jsdelivr.net"></script>
    
    <script>
        // 1. Existing Setup Variables (Leave these at the top of the script)
        let power = 100;
        let time = 12;
        let activeCam = '1A';
        let monitorOpen = false;
        let doors = { left: false, right: false };
        let lights = { left: false, right: false };
        let animatronicPosition = 'stage'; 

        const gameClock = setInterval(() => {
            time++;
            if (time === 13) time = 1;
            document.getElementById('time-display').innerText = time;
            if (time === 6) {
                clearInterval(gameClock);
                clearInterval(powerClock);
                clearInterval(aiClock);
                \$('#winModal').modal('show');
            }
        }, 12000);

        const powerClock = setInterval(() => {
            let usage = 1; 
            if (doors.left) usage++;
            if (doors.right) usage++;
            if (monitorOpen) usage++;

            // ==========================================
            // PASTE YOUR SELECTED TEXT BLOCK RIGHT HERE:
            // ==========================================
            if (lights.left || lights.right) usage++;
            
            document.getElementById('usage-display').innerText = "⚡".repeat(usage);
            power -= (0.5 * usage);
            document.getElementById('power-display').innerText = Math.max(0, Math.floor(power));

            if (power <= 0) {
                triggerGameOver("POWER OUTAGE");
            }
        }, 2000);

        const aiClock = setInterval(() => {
            let roll = Math.random();
            if (roll > 0.4) { 
                if (animatronicPosition === 'stage') {
                    animatronicPosition = Math.random() > 0.5 ? 'left_hall' : 'right_hall';
                } else if (animatronicPosition === 'left_hall') {
                    animatronicPosition = 'office_left_door';
                } else if (animatronicPosition === 'right_hall') {
                    animatronicPosition = 'office_right_door';
                } else if (animatronicPosition === 'office_left_door') {
                    if (doors.left) {
                        animatronicPosition = 'stage'; 
                    } else {
                        triggerGameOver("FREDDY JUMPSCARE");
                    }
                } else if (animatronicPosition === 'office_right_door') {
                    if (doors.right) {
                        animatronicPosition = 'stage'; 
                    } else {
                        triggerGameOver("FREDDY JUMPSCARE");
                    }
                }
                updateCameraViewText();
            }
        }, 7000);

        function toggleDoor(side) {
            doors[side] = !doors[side];
            document.getElementById(`${side}-door`).classList.toggle('closed');
        }

        function toggleLight(side, state) {
            lights[side] = state;
            const viewStatus = document.getElementById('window-status');
            const officeView = document.getElementById('office-view');
            if (state) {
                officeView.style.background = "#555";
                if (side === 'left' && animatronicPosition === 'office_left_door') {
                    viewStatus.innerText = "WARNING: SOMETHING IS STANDING AT THE LEFT WINDOW!";
                    viewStatus.className = "text-danger font-weight-bold animate-pulse";
                } else if (side === 'right' && animatronicPosition === 'office_right_door') {
                    viewStatus.innerText = "WARNING: SOMETHING IS STANDING AT THE RIGHT WINDOW!";
                    viewStatus.className = "text-danger font-weight-bold animate-pulse";
                } else {
                    viewStatus.innerText = "Clear space.";
                    viewStatus.className = "text-white";
                }
            } else {
                officeView.style.background = "linear-gradient(0deg, #111 0%, #222 100%)";
                viewStatus.innerText = "The hallways are dark...";
                viewStatus.className = "text-success font-weight-bold";
            }
        }

        function toggleMonitor() {
            monitorOpen = !monitorOpen;
            document.getElementById('camera-monitor').style.display = monitorOpen ? 'block' : 'none';
            if (monitorOpen) updateCameraViewText();
        }

        function switchCam(camName) {
            activeCam = camName;
            document.getElementById('active-cam-id').innerText = camName;
            updateCameraViewText();
        }

        function updateCameraViewText() {
            const feedText = document.getElementById('camera-display-text');
            if (activeCam === '1A') {
                feedText.innerText = (animatronicPosition === 'stage') ? "🐻 Freddy is standing on the main stage." : "Empty stage. Background silhouette shadows detected.";
                feedText.className = "text-warning";
            } else if (activeCam === '1B') {
                feedText.innerText = (animatronicPosition === 'left_hall' || animatronicPosition === 'office_left_door') ? "⚠️ Footsteps approaching down the Dark Left Corridor!" : "Quiet empty corridor walkway.";
                feedText.className = "text-danger";
            } else if (activeCam === '1C') {
                feedText.innerText = (animatronicPosition === 'right_hall' || animatronicPosition === 'office_right_door') ? "⚠️ Rapid breathing sound patterns near the East Wing!" : "Static feed view normal.";
                feedText.className = "text-danger";
            }
        }

        function triggerGameOver(reason) {
            clearInterval(gameClock);
            clearInterval(powerClock);
            clearInterval(aiClock);
            document.getElementById('jumpscare-screen').style.display = 'flex';
            setTimeout(() => {
                alert(`GAME OVER: ${reason}`);
                window.location.href = "index.php?reset=1";
            }, 3000);
        }
        // ==========================================
    </script>
</body>
</html>