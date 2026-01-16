<?php
/**
 * Thank You Page
 * Displayed after successful survey submission
 */

// Get score from URL (optional)
$score = isset($_GET['score']) ? (float)$_GET['score'] : null;

// Determine classification
$classification = '';
$emoji = '🎉';
$message = 'Obrigado por participar!';

if ($score !== null) {
    if ($score >= 90) {
        $classification = 'Excelência';
        $emoji = '🌟';
        $message = 'Sua avaliação indica excelência!';
    } elseif ($score >= 75) {
        $classification = 'Muito Bom';
        $emoji = '😊';
        $message = 'Sua avaliação foi muito positiva!';
    } elseif ($score >= 60) {
        $classification = 'Adequado';
        $emoji = '👍';
        $message = 'Sua avaliação foi registrada!';
    } else {
        $classification = 'A Melhorar';
        $emoji = '📝';
        $message = 'Sua opinião é muito importante para nós!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obrigado! - Pesquisa ESESP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e3a5f;
            --secondary: #2c5f8d;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .thank-you-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .thank-you-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }
        
        .thank-you-emoji {
            font-size: 5rem;
            margin-bottom: 1rem;
            animation: bounce 1s ease-in-out;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .thank-you-body {
            padding: 2.5rem 2rem;
            text-align: center;
        }
        
        .thank-you-body h2 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .thank-you-body p {
            color: #6b7280;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .score-display {
            background: #f0f9ff;
            border: 2px solid #3b82f6;
            border-radius: 16px;
            padding: 1.5rem;
            margin: 2rem 0;
        }
        
        .score-display .score-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .score-display .score-label {
            font-size: 1.25rem;
            color: #3b82f6;
            font-weight: 600;
        }
        
        .info-box {
            background: #f8fafc;
            border-left: 4px solid var(--primary);
            padding: 1rem 1.5rem;
            margin: 1.5rem 0;
            border-radius: 8px;
            text-align: left;
        }
        
        .info-box i {
            color: var(--primary);
            margin-right: 0.5rem;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            padding: 0.875rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 12px;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            color: white;
        }
        
        .social-links {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .social-links a {
            color: var(--primary);
            font-size: 1.5rem;
            margin: 0 0.75rem;
            transition: color 0.3s;
        }
        
        .social-links a:hover {
            color: var(--secondary);
        }
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #f0f;
            position: absolute;
            animation: confetti-fall 3s linear;
        }
        
        @keyframes confetti-fall {
            to {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="thank-you-card">
        <!-- Header -->
        <div class="thank-you-header">
            <div class="thank-you-emoji"><?= $emoji ?></div>
            <h1 class="mb-0">Pesquisa Enviada!</h1>
        </div>
        
        <!-- Body -->
        <div class="thank-you-body">
            <h2><?= htmlspecialchars($message) ?></h2>
            
            <p>
                Sua opinião é fundamental para continuarmos melhorando a qualidade dos nossos cursos 
                e proporcionando a melhor experiência de aprendizado possível.
            </p>
            
            <?php if ($score !== null): ?>
                <div class="score-display">
                    <div class="score-number"><?= number_format($score, 1) ?>%</div>
                    <div class="score-label"><?= htmlspecialchars($classification) ?></div>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <p class="mb-2">
                    <i class="bi bi-shield-check"></i>
                    <strong>Suas respostas são anônimas e confidenciais</strong>
                </p>
                <p class="mb-0">
                    <i class="bi bi-graph-up"></i>
                    Os resultados ajudarão a equipe da ESESP a aprimorar os programas de capacitação
                </p>
            </div>
            
            <div class="mt-4">
                <a href="https://esesp.es.gov.br" class="btn-primary-custom">
                    <i class="bi bi-house-door me-2"></i>Ir para o Site da ESESP
                </a>
            </div>
            
            <!-- Social Links -->
            <div class="social-links">
                <p class="text-muted mb-3"><small>Siga a ESESP nas redes sociais:</small></p>
                
                <a href="https://www.instagram.com/esespgoves/" target="_blank" title="Instagram">
                    <i class="bi bi-instagram"></i>
                </a>
                
                <a href="https://www.linkedin.com/company/esesp-es/" target="_blank" title="LinkedIn">
                    <i class="bi bi-linkedin"></i>
                </a>
                
                <a href="https://www.youtube.com/@esespgoves" target="_blank" title="YouTube">
                    <i class="bi bi-youtube"></i>
                </a>
                
                <a href="https://www.facebook.com/esespgoves" target="_blank" title="Facebook">
                    <i class="bi bi-facebook"></i>
                </a>
            </div>
            
            <div class="mt-4">
                <p class="text-muted small">
                    <i class="bi bi-building me-1"></i>
                    <strong>ESESP</strong> - Escola de Serviço Público do Espírito Santo<br>
                    Desenvolvendo Talentos, Transformando o Serviço Público
                </p>
            </div>
        </div>
    </div>
    
    <script>
        // Create confetti effect
        function createConfetti() {
            const colors = ['#667eea', '#764ba2', '#f59e0b', '#10b981', '#3b82f6', '#ef4444'];
            
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + '%';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
                    confetti.style.animationDelay = Math.random() * 0.5 + 's';
                    document.body.appendChild(confetti);
                    
                    setTimeout(() => confetti.remove(), 3500);
                }, i * 30);
            }
        }
        
        // Trigger confetti on page load
        window.addEventListener('load', () => {
            setTimeout(createConfetti, 300);
        });
        
        // Auto-redirect after 15 seconds (optional)
        setTimeout(() => {
            const redirect = confirm('Deseja ser redirecionado para o site da ESESP?');
            if (redirect) {
                window.location.href = 'https://esesp.es.gov.br';
            }
        }, 15000);
    </script>
</body>
</html>
