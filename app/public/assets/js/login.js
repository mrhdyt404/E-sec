        // Matrix Rain Effect
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.className = 'matrix-bg';
        document.getElementById('matrixCanvas').appendChild(canvas);
        
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        
        const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789$#%&@*';
        const fontSize = 14;
        const columns = canvas.width / fontSize;
        const drops = Array(Math.floor(columns)).fill(1);
        
        function drawMatrix() {
            ctx.fillStyle = 'rgba(10, 14, 23, 0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            ctx.fillStyle = '#0ff';
            ctx.font = `${fontSize}px 'Share Tech Mono'`;
            
            for (let i = 0; i < drops.length; i++) {
                const text = letters[Math.floor(Math.random() * letters.length)];
                const x = i * fontSize;
                const y = drops[i] * fontSize;
                
                // Gradient effect
                const gradient = ctx.createLinearGradient(x, y - fontSize, x, y);
                gradient.addColorStop(0, '#00f3ff');
                gradient.addColorStop(0.5, '#00ff9d');
                gradient.addColorStop(1, '#ffffff');
                ctx.fillStyle = gradient;
                
                ctx.fillText(text, x, y);
                
                if (y > canvas.height && Math.random() > 0.975) {
                    drops[i] = 0;
                }
                drops[i]++;
            }
        }
        
        setInterval(drawMatrix, 50);
        
        // Terminal typing effect
        document.addEventListener('DOMContentLoaded', function() {
            const terminalOutput = document.querySelector('.terminal-output');
            const messages = [
                "Initializing security protocols... OK",
                "Checking connection integrity... OK",
                "Verifying SSL certificate... OK",
                "Loading encryption modules... OK",
                "Ready for authentication"
            ];
            
            let messageIndex = 0;
            let charIndex = 0;
            let isDeleting = false;
            
            function typeEffect() {
                const currentMessage = messages[messageIndex];
                
                if (!isDeleting && charIndex <= currentMessage.length) {
                    terminalOutput.innerHTML = `
                        <div class="mb-2">
                            <span class="text-cyber-green">$</span> 
                            <span class="text-white">${currentMessage.substring(0, charIndex)}</span>
                            <span class="blink text-cyber-green">█</span>
                        </div>
                        <div class="mb-2">
                            <span class="text-cyber-green">$</span> 
                            <span class="text-white">Connection established from:</span>
                            <span class="text-cyber-blue">${'<?php echo $_SERVER["REMOTE_ADDR"] ?? "UNKNOWN"; ?>'}</span>
                        </div>
                        <div>
                            <span class="text-cyber-green">$</span> 
                            <span class="text-white">Awaiting authentication </span>
                            <span class="blink text-cyber-green">█</span>
                        </div>
                    `;
                    charIndex++;
                    setTimeout(typeEffect, 50);
                } else if (isDeleting && charIndex >= 0) {
                    charIndex--;
                    setTimeout(typeEffect, 30);
                } else {
                    isDeleting = !isDeleting;
                    if (!isDeleting) {
                        messageIndex = (messageIndex + 1) % messages.length;
                    }
                    setTimeout(typeEffect, 1000);
                }
            }
            
            setTimeout(typeEffect, 1000);
            
            // Security scan animation
            const securityLevel = document.querySelector('.security-level');
            setInterval(() => {
                securityLevel.style.animation = 'none';
                setTimeout(() => {
                    securityLevel.style.animation = 'security-scan 3s infinite ease-in-out';
                }, 10);
            }, 3000);
            
            // Input field focus effects
            const inputs = document.querySelectorAll('.cyber-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('neon-border-active');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('neon-border-active');
                });
            });
            
            // Form submission animation
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const button = this.querySelector('button[type="submit"]');
                button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>VERIFYING CREDENTIALS...';
                button.disabled = true;
            });
        });
        
        // Resize canvas on window resize
        window.addEventListener('resize', function() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });