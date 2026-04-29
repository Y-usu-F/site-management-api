<?php

namespace App\Controllers;

use Config\ApiDocsConfig;

class DocsController extends BaseController
{
    private ApiDocsConfig $docsConfig;

    public function __construct(?ApiDocsConfig $docsConfig = null)
    {
        $this->docsConfig = $docsConfig ?? new ApiDocsConfig();
    }

    public function swagger()
    {
        if (! $this->docsConfig->enabled) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        $html = <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>API v1 Docs</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
  <script>
    window.ui = SwaggerUIBundle({
      url: '/docs/openapi.yaml',
      dom_id: '#swagger-ui'
    });
  </script>
</body>
</html>
HTML;

        return $this->response->setHeader('Content-Type', 'text/html; charset=utf-8')->setBody($html);
    }

    public function openapi()
    {
        if (! $this->docsConfig->enabled) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        if (! is_file($this->docsConfig->specFile)) {
            return $this->response->setStatusCode(404)->setBody('Spec file not found');
        }

        return $this->response
            ->setHeader('Content-Type', 'application/yaml; charset=utf-8')
            ->setBody((string) file_get_contents($this->docsConfig->specFile));
    }
}
