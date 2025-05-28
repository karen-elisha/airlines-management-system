<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        try {
            // Check if email exists in database
            $stmt = $mysqli->prepare("SELECT user_id, full_name FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Generate reset token
                $token = bin2hex(random_bytes(50));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store token in database
                $stmt = $mysqli->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at), created_at = NOW()");
                $stmt->bind_param("sss", $email, $token, $expiry);
                $stmt->execute();
                
                // Send email
                $mail = new PHPMailer(true);
                
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; // Change this to your SMTP server
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'your_email@gmail.com'; // Your email
                    $mail->Password   = 'your_app_password';    // Your app password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    
                    // Recipients
                    $mail->setFrom('your_email@gmail.com', 'BookMyFlight');
                    $mail->addAddress($email, $user['full_name']);
                    
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request - BookMyFlight';
                    
                    $resetLink = "http://yourdomain.com/reset-password.php?token=" . $token;
                    
                    $mail->Body = "
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                            .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>🛫 BookMyFlight</h1>
                                <h2>Password Reset Request</h2>
                            </div>
                            <div class='content'>
                                <p>Hello " . htmlspecialchars($user['full_name']) . ",</p>
                                <p>We received a request to reset your password. If you didn't make this request, you can safely ignore this email.</p>
                                <p>To reset your password, click the button below:</p>
                                <div style='text-align: center;'>
                                    <a href='" . $resetLink . "' class='button'>Reset Password</a>
                                </div>
                                <p><strong>This link will expire in 1 hour.</strong></p>
                                <p>If the button doesn't work, copy and paste this link into your browser:</p>
                                <p style='word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 5px;'>" . $resetLink . "</p>
                            </div>
                            <div class='footer'>
                                <p>This is an automated email. Please do not reply.</p>
                                <p>&copy; 2024 BookMyFlight. All rights reserved.</p>
                            </div>
                        </div>
                    </body>
                    </html>";
                    
                    $mail->send();
                    $message = 'Password reset instructions have been sent to your email address.';
                    $messageType = 'success';
                    
                } catch (Exception $e) {
                    $message = 'Failed to send email. Please try again later.';
                    $messageType = 'error';
                    error_log("Mail Error: {$mail->ErrorInfo}");
                }
                
            } else {
                // Don't reveal that email doesn't exist for security
                $message = 'If an account with that email exists, password reset instructions have been sent.';
                $messageType = 'success';
            }
            
        } catch (Exception $e) {
            $message = 'An error occurred. Please try again later.';
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
    <title>Forgot Password | BOOKMYFLIGHT</title>
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
        .loading {
            display: none;
        }
        .loading.show {
            display: inline-block;
        }
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

        <!-- Forgot Password Form -->
        <div class="glass-effect rounded-2xl p-8 shadow-2xl">
            <div class="text-center mb-6">
                <i class="fas fa-key text-4xl text-indigo-300 mb-4"></i>
                <h2 class="text-2xl font-semibold text-white mb-2">Forgot Password?</h2>
                <p class="text-gray-300 text-sm">
                    No worries! Enter your email address and we'll send you instructions to reset your password.
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

            <form method="POST" id="forgotPasswordForm" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required 
                            class="w-full pl-10 pr-4 py-3 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition-all duration-200"
                            placeholder="Enter your email address"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        >
                    </div>
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 focus:ring-offset-transparent"
                    id="submitBtn"
                >
                    <span id="submitText">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Send Reset Instructions
                    </span>
                    <span id="loadingText" class="loading">
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                        Sending...
                    </span>
                </button>
            </form>

            <!-- Back to Login -->
            <div class="mt-6 text-center">
                <p class="text-gray-300 text-sm">
                    Remember your password? 
                    <a href="login.php" class="text-indigo-300 hover:text-indigo-200 font-medium transition-colors duration-200">
                        Back to Login
                    </a>
                </p>
            </div>

            <!-- Additional Help -->
            <div class="mt-6 pt-6 border-t border-white border-opacity-10">
                <div class="text-center">
                    <p class="text-gray-400 text-xs mb-2">Need more help?</p>
                    <a href="mailto:support@bookmyflight.com" class="text-indigo-300 hover:text-indigo-200 text-sm transition-colors duration-200">
                        <i class="fas fa-envelope mr-1"></i>
                        Contact Support
                    </a>
                </div>
            </div>
        </div>

        <!-- Security Notice -->
        <div class="mt-6 text-center">
            <p class="text-white text-opacity-60 text-xs">
                <i class="fas fa-shield-alt mr-1"></i>
                Your security is important to us. Reset links expire after 1 hour.
            </p>
        </div>
    </div>

    <script>
        document.getElementById('forgotPasswordForm').addEventListener('submit', function() {
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const loadingText = document.getElementById('loadingText');
            
            submitBtn.disabled = true;
            submitText.style.display = 'none';
            loadingText.classList.add('show');
            
            // Re-enable after 5 seconds in case of issues
            setTimeout(() => {
                submitBtn.disabled = false;
                submitText.style.display = 'inline';
                loadingText.classList.remove('show');
            }, 5000);
        });

        // Auto-hide success/error messages after 5 seconds
        <?php if ($message && $messageType == 'success'): ?>
        setTimeout(() => {
            const messageEl = document.querySelector('.bg-green-500');
            if (messageEl) {
                messageEl.style.transition = 'opacity 0.5s';
                messageEl.style.opacity = '0';
                setTimeout(() => messageEl.remove(), 500);
            }
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>