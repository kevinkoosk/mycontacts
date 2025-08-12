<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="5; url=./app.php">
    <title>ASCII Landing Page</title>
    
    <!-- Bootstrap CSS -->

    <link href="./css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        .ascii-art {
            font-family: 'Courier New', monospace;
            white-space: pre;
            text-align: center;
            color: #0f0;
            text-shadow: 0 0 10px #0f0;
            background-color: #000;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        body {
            background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
            min-height: 100vh;
            display: flex;
            align-items: center;
            color: #fff;
        }
        
        .container {
            animation: fadeIn 1.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @media (max-width: 768px) {
            .ascii-art {
                font-size: 0.6rem;
            }
        }
    </style>
</head>
<body>
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <pre class="ascii-art">
  _____ _             _   _               _____      _            
 / ____| |           | | | |             / ____|    | |           
| (___ | |_ __ _ _ __| |_| |__  _ __ ___| |    _   _| |__   ___   
 \___ \| __/ _` | '__| __| '_ \| '__/ _ \ |   | | | | '_ \ / _ \  
 ____) | || (_| | |  | |_| | | | | |  __/ |___| |_| | |_) |  __/  
|_____/ \__\__,_|_|   \__|_| |_|_|  \___|\_____\__,_|_.__/ \___|  
                </pre>
                
                <h2 class="mb-4">Welcome to the Matrix</h2>
                <div class="progress mb-4">
                    <div class="progress-bar progress-bar-striped bg-success" 
                         role="progressbar" 
                         style="width: 0%" 
                         aria-valuenow="0" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>
                <p class="lead">Redirecting to application in 5 seconds...</p>
                <p class="text-muted">If you are not redirected automatically, 
                    <a href="./app.php" class="text-success">click here</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->

    <script src="./js/bootstrap.bundle.min.js"></script>
    
    <script>
        window.addEventListener('load', function() {
            const progressBar = document.querySelector('.progress-bar');
            let width = 0;
            
            const interval = setInterval(() => {
                if (width >= 100) {
                    clearInterval(interval);
                } else {
                    width += 1;
                    progressBar.style.width = width + '%';
                    progressBar.setAttribute('aria-valuenow', width);
                }
            }, 50);
        });
    </script>
</body>
</html>