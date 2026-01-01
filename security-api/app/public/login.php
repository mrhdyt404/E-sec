<?php
session_start();

$error = '';
if ($_POST) {
    if ($_POST['user'] == 'admin' && $_POST['pass'] == 'admin123') {
        $_SESSION['auth'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['login_ip'] = $_SERVER['REMOTE_ADDR'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = 'Access Denied: Invalid credentials';
        // Log percobaan login gagal
        $log_entry = date('Y-m-d H:i:s') . " - Failed login attempt from IP: " . $_SERVER['REMOTE_ADDR'] . " - Username: " . $_POST['user'];
        error_log($log_entry, 3, "security.log");
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyber Security Portal v1.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/login.css">
</head>
<body class="scanlines">
    <!-- Matrix Background Effect -->
    <div class="matrix-bg" id="matrixCanvas"></div>
    
    <!-- Main Login Container -->
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Security Header -->
            <div class="text-center mb-8">
                <div class="flex items-center justify-center gap-3 mb-4">
                    <div class="w-3 h-3 rounded-full bg-red-500 pulse-warning"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                </div>
                
                <h1 class="terminal-text text-3xl md:text-4xl font-bold mb-2 glitch">
                    <i class="fas fa-shield-alt text-cyber-blue mr-2"></i>
                    CYBER SECURITY PORTAL
                </h1>
                <p class="text-cyber-blue text-sm tracking-wider">v1.0 • ACCESS RESTRICTED</p>
                
                <div class="security-level mt-4"></div>
            </div>
            
            <!-- Login Terminal -->
            <div class="neon-border rounded-xl p-6 md:p-8 bg-gradient-to-b from-cyber-gray/80 to-cyber-dark/90 backdrop-blur-sm">
                <!-- Security Status -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-lock text-cyber-green"></i>
                        <span class="terminal-text text-sm">ENCRYPTED CONNECTION</span>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-cyber-blue">
                            <i class="fas fa-clock mr-1"></i>
                            <?php echo date('H:i:s'); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Terminal Output -->
                <div class="terminal-output mb-6 rounded">
                    <div class="mb-2">
                        <span class="text-cyber-green">$</span> 
                        <span class="text-white">Initializing security protocols...</span>
                    </div>
                    <div class="mb-2">
                        <span class="text-cyber-green">$</span> 
                        <span class="text-white">Connection established from:</span>
                        <span class="text-cyber-blue"><?php echo $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'; ?></span>
                    </div>
                    <div>
                        <span class="text-cyber-green">$</span> 
                        <span class="text-white">Awaiting authentication </span>
                        <span class="blink text-cyber-green">█</span>
                    </div>
                </div>
                
                <!-- Login Form -->
                <form method="post" class="space-y-6">
                    <!-- Username Field -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="terminal-text text-sm text-cyber-blue">
                                <i class="fas fa-user-secret mr-2"></i>USER IDENTIFICATION
                            </label>
                            <span class="text-xs text-gray-500">REQUIRED</span>
                        </div>
                        <div class="relative">
                            <input 
                                type="text" 
                                name="user" 
                                required 
                                placeholder="ENTER USERNAME" 
                                class="cyber-input w-full rounded-lg terminal-text"
                                autocomplete="off"
                                autocorrect="off"
                                spellcheck="false">
                            <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <i class="fas fa-terminal text-cyber-blue/50"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Password Field -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="terminal-text text-sm text-cyber-blue">
                                <i class="fas fa-key mr-2"></i>ENCRYPTION KEY
                            </label>
                            <span class="text-xs text-gray-500">AES-256</span>
                        </div>
                        <div class="relative">
                            <input 
                                type="password" 
                                name="pass" 
                                required 
                                placeholder="••••••••••••" 
                                class="cyber-input w-full rounded-lg terminal-text"
                                autocomplete="off"
                                autocorrect="off"
                                spellcheck="false">
                            <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <i class="fas fa-fingerprint text-cyber-blue/50"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Error Message -->
                    <?php if($error): ?>
                    <div class="p-3 border border-cyber-red/50 bg-cyber-red/10 rounded-lg">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle text-cyber-red"></i>
                            <span class="terminal-text text-sm text-cyber-red"><?php echo htmlspecialchars($error); ?></span>
                        </div>
                        <div class="text-xs text-gray-400 mt-1">
                            Attempt logged to security system
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Submit Button -->
                    <div class="pt-4">
                        <button type="submit" class="cyber-btn w-full py-4 rounded-lg terminal-text text-lg">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            INITIATE ACCESS SEQUENCE
                        </button>
                    </div>
                </form>
                
                <!-- Security Footer -->
                <div class="mt-8 pt-6 border-t border-cyber-gray">
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div class="text-center">
                            <div class="text-cyber-blue mb-1">
                                <i class="fas fa-server"></i>
                            </div>
                            <div class="text-gray-400">SERVER: ONLINE</div>
                        </div>
                        <div class="text-center">
                            <div class="text-cyber-green mb-1">
                                <i class="fas fa-shield-check"></i>
                            </div>
                            <div class="text-gray-400">FIREWALL: ACTIVE</div>
                        </div>
                    </div>
                    <div class="text-center mt-4 text-gray-500 text-xs">
                        <i class="fas fa-info-circle mr-1"></i>
                        All access attempts are monitored and logged
                    </div>
                </div>
            </div>
            
            <!-- Copyright -->
            <div class="text-center mt-8">
                <p class="text-gray-600 text-xs tracking-widest">
                    © 2024 CYBERSEC CORP • CLASSIFIED SYSTEM
                </p>
                <p class="text-gray-700 text-xs mt-1">
                    UNAUTHORIZED ACCESS PROHIBITED
                </p>
            </div>
        </div>
    </div>
    
    <!-- Matrix Rain Effect Script -->
    <script src="./assets/js/login.js"></script>
</body>
</html>