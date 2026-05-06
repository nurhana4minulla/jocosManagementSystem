<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Page</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        header {
            background-color: #0F172A;
            color: #ffffff;
            padding: 1rem;
            text-align: center;
            height: 70px;
        }
        main {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            height: calc(100vh - 70px);
            background-color: #F4F6F9;
            overflow: hidden;
        }

        main::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('assets/img/logo.png');
            background-repeat: no-repeat;
            background-position: center;
            background-size: 250px; 
            opacity: 0.2; 
            z-index: 0;
        }

        main * {
            position: relative;
            z-index: 1;
        }
        main h2{
            color: #0F172A;
            align-self:start;
        }
        h1 {
            color: #0F172A;
        }
    </style>
</head>
<body>
    <header>
        <div class="header">
            <nav class="navbar">
                <div class="logo"></div>
                
            </nav>
        </div>
    </header>
    <main>
        <!-- <h2>JO/COS Personnel Management System</h2> -->
    </main>
</body>
</html>