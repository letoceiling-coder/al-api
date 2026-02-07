<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrendAgent API - Swagger Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.5/swagger-ui.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin:0;
            background: #fafafa;
        }
        .error-message {
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
            margin: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.5/swagger-ui-bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const swaggerUrl = "{{ url('/trendagent/swagger.json') }}";
            console.log('Loading Swagger from:', swaggerUrl);
            
            // Проверяем доступность файла перед загрузкой
            fetch(swaggerUrl)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Swagger JSON loaded successfully');
                    console.log('OpenAPI version:', data.openapi);
                    console.log('Paths count:', Object.keys(data.paths || {}).length);
                })
                .catch(error => {
                    console.error('Error loading swagger.json:', error);
                    document.getElementById('swagger-ui').innerHTML = 
                        '<div class="error-message">' +
                        '<h2>Ошибка загрузки документации</h2>' +
                        '<p>Не удалось загрузить swagger.json: ' + error.message + '</p>' +
                        '<p>URL: ' + swaggerUrl + '</p>' +
                        '<p>Проверьте консоль браузера (F12) для деталей.</p>' +
                        '</div>';
                });
            
            const ui = SwaggerUIBundle({
                url: swaggerUrl,
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                validatorUrl: null,
                docExpansion: "list",
                filter: true,
                showExtensions: true,
                showCommonExtensions: true,
                tryItOutEnabled: true,
                onComplete: function() {
                    console.log('Swagger UI loaded successfully');
                },
                onFailure: function(data) {
                    console.error('Swagger UI load error:', data);
                }
            });
        };
    </script>
</body>
</html>
