<?php
session_start();
require_once 'db_connect.php';

$token = $_GET['token'] ?? '';
$message = '';
$messageType = '';
$validToken = false;

// Validate token
if ($token) {
    $stmt = $mysqli->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $validToken = true;
        $resetData = $result->fetch_assoc();
    } else {
        $message = 'Invalid or expired reset link. Please request a new password reset.';
        $messageType = 'error';
    }
} else {
    $message = 'Invalid reset link.';
    $messageType = 'error';
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $validToken) {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validation
    if (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters long.';
        $messageType = 'error';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match.';
        $messageType = 'error';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>]).{8,}$/', $password)) {
        $message = 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.';
        $messageType = 'error';
    } else {
        try {
            // Hash the new password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Update user password
            $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashedPassword, $resetData['email']);
            $stmt->execute();
            
            // Mark token as used
            $stmt = $mysqli->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            
            $message = 'Your password has been successfully reset. You can now login with your new password.';
            $messageType = 'success';
            $validToken = false; // Hide form after successful reset
            
        } catch (Exception $e) {
            $message = 'An error occurred while resetting your password. Please try again.';
            $messageType = 'error';
            error_log($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | BOOKMYFLIGHT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .password-strength {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        .strength-bar {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
            width: 0%;
        }
        .strength-weak { background: #ef4444; width: 25%; }
        .strength-fair { background: #f59e0b; width: 50%; }
        .strength-good { background: #10b981; width: 75%; }
        .strength-strong { background: #059669; width: 100%; }
    </style>
</head>
<body class="min-h-screen gradient-bg flex items-center justify-center p-4">
    <!-- Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-white opacity-5 rounded-full animate-float"></div>
        <div class="absolute top-3/4 right-1/4 w-48 h-48 bg-white opacity-5 rounded-full animate-float" style="animation-delay: -1s;"></div>
        <div class="absolute top-1/2 left-3/4 w-32 h-32 bg-white opacity-5 rounded-full animate-float" style="animation-delay: -2s;"></div>
    </div>

    <div class="w-full max-w-md relative z-10">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white bg-opacity-20 rounded-full mb-4">
                <i class="fas fa-plane-departure text-2xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-white">BOOKMYFLIGHT</h1>
            <p class="text-white text-opacity-80 mt-2">Reset Your Password</p>
        </div>

        <!-- Reset Password Form -->
        <div class="glass-effect rounded-2xl p-8 shadow-2xl">
            <div class="text-center mb-6">
                <i class="fas fa-lock text-4xl text-indigo-300 mb-4"></i>
                <h2 class="text-2xl font-semibold text-white mb-2">Create New Password</h2>
                <p class="text-gray-300 text-sm">
                    Enter your new password below. Make sure it's strong and secure.
                </p>
            </div>

            <?php if ($message): ?>
                <div class="mb-4 p-3 rounded-lg <?php echo $messageType == 'error' ? 'bg-red-500 bg-opacity-20 border border-red-400 text-red-300' : 'bg-green-500 bg-opacity-20 border border-green-400 text-green-300'; ?>">
                    <div class="flex items-center">
                        <i class="fas <?php echo $messageType == 'error' ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?> mr-2"></i>
                        <span class="text-sm"><?php echo htmlspecialchars($message); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($validToken): ?>
                <form method="POST" id="resetPasswordForm" class="space-y-6">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-300 mb-2">New Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required 
                                minlength="8"
                                class="w-full pl-10 pr-12 py-3 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition-all duration-200"
                                placeholder="Enter new password"
                            >
                            <button type="button" onclick="togglePassword('password')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i class="fas fa-eye text-gray-400 hover:text-white" id="password-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar" id="strength-bar"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1" id="strength-text">Password strength: <span id="strength-level">-</span></p>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-300 mb-2">Confirm New Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                required 
                                class="w-full pl-10 pr-12 py-3 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition-all duration-200"
                                placeholder="Confirm new password"
                            >
                            <button type="button" onclick="togglePassword('confirm_password')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i class="fas fa-eye text-gray-400 hover:text-white" id="confirm_password-eye"></i>
                            </button>
                        </div>
                        <p class="text-xs mt-1" id="match-text"></p>
                    </div>

                    <!-- Password Requirements -->
                    <div class="bg-white bg-opacity-5 rounded-lg p-3">
                        <p class="text-xs text-gray-300 mb-2">Password must contain:</p>
                        <ul class="text-xs text-gray-400 space-y-1">
                            <li id="req-length"><i class="fas fa-times text-red-400 mr-2"></i>At least 8 characters</li>
                            <li id="req-upper"><i class="fas fa-times text-red-400 mr-2"></i>One uppercase letter</li>
                            <li id="req-lower"><i class="fas fa-times text-red-400 mr-2"></i>One lowercase letter</li>
                            <li id="req-number"><i class="fas fa-times text-red-400 mr-2"></i>One number</li>
                            <li id="req-special"><i class="fas fa-times text-red-400 mr-2"></i>One special character</li>
                        </ul>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 focus:ring-offset-transparent disabled:opacity-50 disabled:cursor-not-allowed"
                        id="submitBtn"
                        disabled
                    >
                        <i class="fas fa-save mr-2"></i>
                        Reset Password
                    </button>
                </form>
            <?php else: ?>
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-6xl text-yellow-400 mb-4"></i>
                    <p class="text-gray-300 mb-6">
                        <?php if ($messageType === 'success'): ?>
                            Your password has been successfully reset!
                        <?php else: ?>
                            This reset link has expired or is invalid.
                        <?php endif; ?>
                    </p>
                    <?php if ($messageType === 'success'): ?>
                        <a href="login.php" class="inline-block bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Go to Login
                        </a>
                    <?php else: ?>
                        <a href="forgot-password.php" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200">
                            <i class="fas fa-redo mr-2"></i>
                            Request New Reset Link
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Back to Login -->
            <div class="mt-6 text-center">
                <p class="text-gray-300 text-sm">
                    <a href="login.php" class="text-indigo-300 hover:text-indigo-200 font-medium transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-1"></i>
                        Back to Login
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const eye = document.getElementById(fieldId + '-eye');
            
            if (field.type === 'password') {
                field.type = 'text';
                eye.classList.remove('fa-eye');
                eye.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                eye.classList.remove('fa-eye-slash');
                eye.classList.add('fa-eye');
            }
        }

        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('strength-bar');
            const strengthLevel = document.getElementById('strength-level');
            
            // Check requirements
            const requirements = {
                length: password.length >= 8,
                upper: /[A-Z]/.test(password),
                lower: /[a-z]/.test(password),
                number: /\d/.test(password),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };
            
            // Update requirement indicators
            updateRequirement('req-length', requirements.length);
            updateRequirement('req-upper', requirements.upper);
            updateRequirement('req-lower', requirements.lower);
            updateRequirement('req-number', requirements.number);
            updateRequirement('req-special', requirements.special);
            
            // Calculate strength
            const metRequirements = Object.values(requirements).filter(req => req).length;
            let strengthText = '';
            let strengthClass = '';
            
            if (password.length === 0) {
                strengthText = '-';
                strengthClass = '';
            } else if (metRequirements <= 2) {
                strengthText = 'Weak';
                strengthClass = 'strength-weak';
            } else if (metRequirements === 3) {
                strengthText = 'Fair';
                strengthClass = 'strength-fair';
            } else if (metRequirements === 4) {
                strengthText = 'Good';
                strengthClass = 'strength-good';
            } else if (metRequirements === 5) {
                strengthText = 'Strong';
                strengthClass = 'strength-strong';
            }
            
            // Update strength bar
            strengthBar.className = 'strength-bar ' + strengthClass;
            strengthLevel.textContent = strengthText;
            
            // Check if form can be submitted
            checkFormValidity();
        }

        function updateRequirement(reqId, met) {
            const element = document.getElementById(reqId);
            const icon = element.querySelector('i');
            
            if (met) {
                icon.classList.remove('fa-times', 'text-red-400');
                icon.classList.add('fa-check', 'text-green-400');
                element.classList.add('text-green-400');
                element.classList.remove('text-gray-400');
            } else {
                icon.classList.remove('fa-check', 'text-green-400');
                icon.classList.add('fa-times', 'text-red-400');
                element.classList.add('text-gray-400');
                element.classList.remove('text-green-400');
            }
        }

        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchText = document.getElementById('match-text');
            
            if (confirmPassword.length === 0) {
                matchText.textContent = '';
                matchText.className = 'text-xs mt-1';
            } else if (password === confirmPassword) {
                matchText.textContent = '✓ Passwords match';
                matchText.className = 'text-xs mt-1 text-green-400';
            } else {
                matchText.textContent = '✗ Passwords do not match';
                matchText.className = 'text-xs mt-1 text-red-400';
            }
            
            checkFormValidity();
        }

        function checkFormValidity() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const submitBtn = document.getElementById('submitBtn');
            
            // Check all requirements
            const requirements = {
                length: password.length >= 8,
                upper: /[A-Z]/.test(password),
                lower: /[a-z]/.test(password),
                number: /\d/.test(password),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };
            
            const allRequirementsMet = Object.values(requirements).every(req => req);
            const passwordsMatch = password === confirmPassword && confirmPassword.length > 0;
            
            if (allRequirementsMet && passwordsMatch) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        // Form submission handler
        document.getElementById('resetPasswordForm')?.addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Final validation
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return;
            }
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Resetting Password...';
            submitBtn.disabled = true;
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('confirm_password');
            
            if (passwordField && confirmPasswordField) {
                // Initialize form state
                checkFormValidity();
                
                // Add event listeners
                passwordField.addEventListener('input', function() {
                    checkPasswordStrength(this.value);
                });
                
                confirmPasswordField.addEventListener('input', function() {
                    checkPasswordMatch();
                });
            }

            // Auto-hide success messages after 5 seconds
            const successMessages = document.querySelectorAll('.bg-green-500');
            successMessages.forEach(function(element) {
                setTimeout(function() {
                    element.style.transition = 'opacity 0.5s';
                    element.style.opacity = '0';
                    setTimeout(function() {
                        if (element.parentNode) {
                            element.remove();
                        }
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>