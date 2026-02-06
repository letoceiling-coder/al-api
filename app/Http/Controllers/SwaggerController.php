<?php

namespace App\Http\Controllers;

use L5Swagger\Http\Controllers\SwaggerController as BaseSwaggerController;

class SwaggerController extends BaseSwaggerController
{
    /**
     * Override to fix route name issue
     */
    protected function generateDocumentationFileURL(string $documentation): string
    {
        $config = config('l5-swagger.documentations.'.$documentation);
        
        $fileUsedForDocs = $config['paths']['docs_json'];
        
        if (! empty($config['paths']['format_to_use_for_docs'])
            && $config['paths']['format_to_use_for_docs'] === 'yaml'
            && $config['paths']['docs_yaml']
        ) {
            $fileUsedForDocs = $config['paths']['docs_yaml'];
        }

        // Use direct URL instead of route name to avoid RouteNotFoundException
        return url('/docs') . '?' . $fileUsedForDocs;
    }
}
