<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'API Gateway') }} - Projects</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            width: 100%;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 3rem;
        }
        
        .header h1 {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .project-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }
        
        .project-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        
        .project-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .project-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .project-description {
            font-size: 1rem;
            color: #666;
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }
        
        .project-links {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .project-link {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background: #f0f0f0;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #555;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .project-link:hover {
            background: #667eea;
            color: white;
        }
        
        .project-link svg {
            width: 16px;
            height: 16px;
            margin-right: 0.5rem;
        }
        
        .footer {
            text-align: center;
            color: white;
            padding: 2rem 0;
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            
            .projects-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 {{ config('app.name', 'API Gateway') }}</h1>
            <p>Select a project to get started</p>
        </div>
        
        <div class="projects-grid">
            @foreach($projects as $project)
            <a href="{{ $project['url'] }}" class="project-card">
                <div class="project-icon">{{ $project['icon'] }}</div>
                <h2 class="project-title">{{ $project['name'] }}</h2>
                <p class="project-description">{{ $project['description'] }}</p>
                <div class="project-links">
                    <span class="project-link">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        Open App
                    </span>
                    @if(isset($project['api']))
                    <a href="{{ $project['api'] }}" class="project-link" onclick="event.stopPropagation()">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        API Docs
                    </a>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
