<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 500px;
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        h1 {
            color: #e74c3c;
            margin-bottom: 20px;
        }
        p {
            margin-bottom: 20px;
        }
        .contact {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>System Maintenance</h1>
        <p>The system is currently undergoing maintenance. Please try again later or contact support if this persists.</p>
        
        <div class="contact">
            <p><strong>Contact Information:</strong></p>
            <p>Email: {{ config('system.support_email', 'support@yourcompany.com') }}</p>
            <p>Phone: {{ config('system.support_phone', '+123456789') }}</p>
        </div>
    </div>
</body>
</html>